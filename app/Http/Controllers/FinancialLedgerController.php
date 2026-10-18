<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PaymentReceiptVoucher;
use App\Models\CashClearanceSettlement;
use App\Models\User;
use App\Services\B2bFinancialLedgerService;
use App\Services\FinancialPaymentService;
use App\Services\B2bTenantAuthorizationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialLedgerController extends Controller
{
    public function __construct(
        protected B2bFinancialLedgerService $financialService,
        protected FinancialPaymentService $paymentService,
        protected B2bTenantAuthorizationService $authService
    ) {}

    /**
     * Store a payment receipt voucher (سند قبض) from the UI.
     */
    public function storeReceiptVoucher(Request $request)
    {
        $validated = $request->validate([
            'supplier_company_id' => 'required|exists:companies,id',
            'retailer_company_id' => 'required|exists:companies,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,fib_fastpay,cheque',
            'collected_by_user_id' => 'nullable|exists:users,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'cash_van_sale_id' => 'nullable|exists:cash_van_sales,id',
            'amount_iqd' => 'nullable|numeric|min:0',
            'exchange_rate' => 'nullable|numeric|min:0',
            'cheque_number' => 'nullable|string|max:50',
            'cheque_due_date' => 'nullable|date',
            'transaction_reference' => 'nullable|string|max:100',
            'receipt_date' => 'nullable|string|max:50',
            'custody_status' => 'nullable|in:in_driver_custody,deposited_to_safe',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->authService->authorizeFinancialAction(
            $request,
            'record_voucher',
            (int)$validated['supplier_company_id'],
            (int)$validated['retailer_company_id']
        );

        $idempotencyKey = $request->header('X-Idempotency-Key') ?: $request->input('idempotency_key') ?: ($validated['transaction_reference'] ?? null);
        if ($idempotencyKey) {
            $existing = PaymentReceiptVoucher::where('transaction_reference', $idempotencyKey)->first();
            if ($existing) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'idempotent' => true,
                        'message' => "ئەم پسوولەیە پێشتر تۆمارکراوە: {$existing->voucher_number}",
                        'voucher' => $existing->load(['supplier', 'retailer', 'collector']),
                    ]);
                }
                return redirect()->back()->with('success', "ئەم پسوولەیە پێشتر تۆمارکراوە: {$existing->voucher_number}.");
            }
        }

        $voucher = $this->financialService->recordReceiptVoucher(
            (int)$validated['supplier_company_id'],
            (int)$validated['retailer_company_id'],
            (float)$validated['amount'],
            $validated['payment_method'],
            isset($validated['collected_by_user_id']) ? (int)$validated['collected_by_user_id'] : null,
            isset($validated['purchase_order_id']) ? (int)$validated['purchase_order_id'] : null,
            isset($validated['cash_van_sale_id']) ? (int)$validated['cash_van_sale_id'] : null,
            isset($validated['amount_iqd']) ? (float)$validated['amount_iqd'] : null,
            isset($validated['exchange_rate']) ? (float)$validated['exchange_rate'] : null,
            $validated['cheque_number'] ?? null,
            $validated['cheque_due_date'] ?? null,
            $validated['transaction_reference'] ?? null,
            $validated['notes'] ?? null,
            auth()->user(),
            true,
            $validated['receipt_date'] ?? null,
            $validated['custody_status'] ?? null
        );

        $statusMsg = $voucher->custody_status === 'in_driver_custody'
            ? " (پارەکە لە ئەمانەتی وەرگر/مەندوبە تا تەسلیمی خەزێنەی گشتی دەکرێت)"
            : " (ڕاستەوخۆ خرایە خەزێنەی سەرەکی کۆمپانیا)";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "پسوولەی وەرگرتنی پارە بە سەرکەوتوویی تۆمارکرا: {$voucher->voucher_number}{$statusMsg}",
                'voucher' => $voucher->load(['supplier', 'retailer', 'collector']),
            ]);
        }

        return redirect()->back()->with('success', "پسوولەی وەرگرتنی پارە (سند قبض) بە ژمارەی {$voucher->voucher_number} بە بڕی \${$voucher->amount} بە سەرکەوتوویی تۆمارکرا{$statusMsg}.");
    }

    /**
     * Display printable / thermal receipt voucher view.
     */
    public function printVoucher(Request $request, int $id)
    {
        $voucher = PaymentReceiptVoucher::with(['supplier', 'retailer', 'collector', 'purchaseOrder', 'cashVanSale'])->findOrFail($id);
        
        $actor = $this->authService->getActorContext($request);
        if ($actor['is_authenticated'] && !$actor['is_platform_admin']) {
            $companyId = (int)$actor['company_id'];
            if ($companyId !== (int)$voucher->supplier_company_id && $companyId !== (int)$voucher->retailer_company_id) {
                abort(403, "ڕێگەت پێنەدراوە بۆ بینینی ئەم پسوولەیە.");
            }
        }

        return view('financial.voucher_print', compact('voucher'));
    }

    /**
     * Web view for Unified Statement of Account (کەشف حیساب).
     */
    public function showStatement(Request $request, int $retailerCompanyId)
    {
        $actor = $this->authService->getActorContext($request);
        if (!$actor['is_authenticated']) {
            abort(403, 'تکایە سەرەتا بچۆ ژوورەوە.');
        }

        if ($actor['user']?->isDriver()) {
            abort(403, 'مۆڵەتی بینینی کەشف حیسابی ژمێریاری تەنها لای خاوەن، بەڕێوەبەر یان ژمێریارە.');
        }

        $retailer = Company::where('id', $retailerCompanyId)->firstOrFail();

        // Determine supplier with strict tenant isolation
        if ($actor['is_platform_admin']) {
            $supplierId = $request->query('supplier_id') 
                ?: ($actor['company_id'] ?? null) 
                ?: session('active_supplier_id') 
                ?: Company::where('business_type', 'supplier')->value('id');
        } else {
            $userCompanyId = (int)$actor['company_id'];
            $userCompany = $actor['user']?->company;

            if ($userCompany && $userCompany->business_type === 'retailer') {
                if ($userCompanyId !== (int)$retailerCompanyId) {
                    \App\Models\SecurityAuditLog::logViolation(
                        'idor_financial_statement_access',
                        "هەوڵی نەشیاوی بینینی کەشف حیسابی کۆمپانیای تر ({$retailerCompanyId}) لەلایەن بەکارهێنەر ({$actor['user']?->id})",
                        $request,
                        $actor['company_id'],
                        $actor['user']?->id,
                        'critical'
                    );
                    abort(403, 'مۆڵەتی بینینی ئەم کەشف حیسابەت نییە.');
                }
                $supplierId = (int)$request->query('supplier_id', Company::where('business_type', 'supplier')->value('id'));
            } else {
                // Supplier viewing statement of their customer
                $supplierId = $userCompanyId;
            }

            if ($userCompanyId !== (int)$retailerCompanyId && $userCompanyId !== (int)$supplierId) {
                \App\Models\SecurityAuditLog::logViolation(
                    'idor_financial_statement_access',
                    "هەوڵی نەشیاوی بینینی کەشف حیسابی کۆمپانیای تر ({$retailerCompanyId}) لەلایەن بەکارهێنەر ({$actor['user']?->id})",
                    $request,
                    $actor['company_id'],
                    $actor['user']?->id,
                    'critical'
                );
                abort(403, 'مۆڵەتی بینینی ئەم کەشف حیسابەت نییە.');
            }
        }

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $data = $this->financialService->getUnifiedStatementData((int)$supplierId, (int)$retailerCompanyId, $fromDate, $toDate);

        return view('financial.statement_view', $data);
    }

    /**
     * Clear driver cash collections into company treasury.
     */
    public function storeCashClearance(Request $request)
    {
        $validated = $request->validate([
            'supplier_company_id' => 'required|exists:companies,id',
            'driver_user_id' => 'required|exists:users,id',
            'actual_cash_deposited' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->authService->authorizeFinancialAction(
            $request,
            'safe_clearance',
            (int)$validated['supplier_company_id']
        );

        $settlement = $this->financialService->clearDriverCash(
            (int)$validated['supplier_company_id'],
            (int)$validated['driver_user_id'],
            (float)$validated['actual_cash_deposited'],
            auth()->user(),
            $validated['notes'] ?? null
        );

        return redirect()->back()->with('success', "تەسلیماتی نەقدینەی شۆفێر پاکتاو کرا: ژمارە {$settlement->settlement_number}. کۆکراوە: \${$settlement->total_cash_collected}، تەسلیمکراو: \${$settlement->actual_cash_deposited}.");
    }

    /**
     * Verify and deposit an individual receipt voucher directly into company treasury.
     */
    public function verifyReceiptVoucher(Request $request, int $id)
    {
        $voucher = PaymentReceiptVoucher::findOrFail($id);

        $this->authService->authorizeFinancialAction(
            $request,
            'verify_voucher',
            (int)$voucher->supplier_company_id
        );

        $this->financialService->verifyVoucherIntoSafe($id, auth()->user(), $request->input('notes'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "پارەی وەسڵی {$voucher->voucher_number} بە سەرکەوتوویی خرایە خەزێنەی گشتی کۆمپانیا.",
            ]);
        }

        return redirect()->back()->with('success', "پارەی وەسڵی {$voucher->voucher_number} بە بڕی \${$voucher->amount} بە سەرکەوتوویی لە خەزێنەی گشتی کۆمپانیا وەرگیرا و جێگیرکرا.");
    }

    /**
     * Store Opening Balance (قەرزی پێش سیستم / باڵانسی سەرەتایی).
     */
    public function storeOpeningBalance(Request $request)
    {
        $validated = $request->validate([
            'supplier_company_id' => 'required|exists:companies,id',
            'retailer_company_id' => 'required|exists:companies,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'nullable|in:debit,credit',
            'entry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->authService->authorizeFinancialAction(
            $request,
            'opening_balance',
            (int)$validated['supplier_company_id'],
            (int)$validated['retailer_company_id']
        );

        $entry = $this->financialService->recordOpeningBalance(
            (int)$validated['supplier_company_id'],
            (int)$validated['retailer_company_id'],
            (float)$validated['amount'],
            $validated['type'] ?? 'debit',
            $validated['entry_date'] ?? null,
            $validated['notes'] ?? null,
            auth()->user()
        );

        $typeDesc = ($validated['type'] ?? 'debit') === 'debit' ? 'قەرزی پێش سیستم (مەدین)' : 'پێشەکی سەرەتایی (دائین)';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "باڵانسی سەرەتایی بە سەرکەوتوویی جێگیر کرا: {$typeDesc} بڕی \${$entry->debit_amount}",
                'entry' => $entry,
            ]);
        }

        return redirect()->back()->with('success', "باڵانسی سەرەتایی ({$typeDesc}) بە بڕی \${$validated['amount']} بۆ مارکێتی دیاریکراو بە سەرکەوتوویی لە دەفتەری ژمێریاری جێگیر کرا.");
    }

    /**
     * Reverse a verified or recorded voucher (پێچەوانەکردنەوە).
     */
    public function reverseReceiptVoucher(Request $request, int $id)
    {
        $voucher = PaymentReceiptVoucher::findOrFail($id);
        $this->authService->authorizeFinancialAction(
            $request,
            'reverse_voucher',
            (int)$voucher->supplier_company_id,
            (int)$voucher->retailer_company_id
        );

        $validated = $request->validate([
            'reversal_reason' => 'required|string|max:500',
        ]);

        $reversed = $this->paymentService->reversePayment($voucher, $validated['reversal_reason'], auth()->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "پسوولەی {$reversed->voucher_number} بە سەرکەوتوویی پێچەوانە (Reversed) کرایەوە و تۆماری یەکسانکەر لە دەفتەری ژمێریاری دروستکرا.",
                'voucher' => $reversed,
            ]);
        }

        return redirect()->back()->with('success', "پسوولەی {$reversed->voucher_number} پێچەوانە کرایەوە و قەرزەکە گەڕێنرایەوە سەر حسابی کڕیار.");
    }

    /**
     * Create installment schedule for an order.
     */
    public function createInstallments(Request $request, int $id)
    {
        $po = \App\Models\PurchaseOrder::findOrFail($id);
        $this->authService->authorizeFinancialAction(
            $request,
            'manage_installments',
            (int)$po->supplier_company_id,
            (int)$po->retailer_company_id
        );

        $validated = $request->validate([
            'number_of_installments' => 'required|integer|min:2|max:36',
            'frequency' => 'nullable|in:weekly,biweekly,monthly',
            'start_date' => 'nullable|date',
        ]);

        $installments = $this->paymentService->createInstallmentPlan(
            $po,
            (int)$validated['number_of_installments'],
            $validated['frequency'] ?? 'monthly',
            $validated['start_date'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "پلانی قیست بە سەرکەوتوویی بۆ فەرمانی {$po->po_number} دانرا.",
                'installments' => $installments,
            ]);
        }

        return redirect()->back()->with('success', "پلانی {$validated['number_of_installments']} قیست بۆ فەرمانی {$po->po_number} دانرا.");
    }

    /**
     * Settle single installment.
     */
    public function payInstallment(Request $request, int $id)
    {
        $installment = \App\Models\OrderInstallment::with('purchaseOrder')->findOrFail($id);
        $this->authService->authorizeFinancialAction(
            $request,
            'record_voucher',
            (int)$installment->purchaseOrder->supplier_company_id,
            (int)$installment->purchaseOrder->retailer_company_id
        );

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $installment->remaining_amount,
            'payment_method' => 'required|in:cash,bank_transfer,fib_fastpay',
        ]);

        $idempotencyKey = $request->header('X-Idempotency-Key') ?: $request->input('idempotency_key');
        $voucher = $this->paymentService->settleOrderInstallment(
            $installment,
            (float)$validated['amount'],
            $validated['payment_method'],
            auth()->user(),
            $idempotencyKey
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "پارەدانی قیست بە سەرکەوتوویی تۆمارکرا بە وەسڵی {$voucher->voucher_number}.",
                'voucher' => $voucher,
                'installment' => $installment->fresh(),
            ]);
        }

        return redirect()->back()->with('success', "پارەدانی قیست بە سەرکەوتوویی تۆمارکرا بە وەسڵی {$voucher->voucher_number}.");
    }

    /**
     * Check financial balance and ledger integrity.
     */
    public function checkIntegrity(Request $request, int $supplierId, int $retailerId)
    {
        $this->authService->authorizeFinancialAction($request, 'view_integrity', $supplierId, $retailerId);
        $data = $this->paymentService->verifyAccountBalanceIntegrity($supplierId, $retailerId);

        return response()->json([
            'success' => true,
            'supplier_company_id' => $supplierId,
            'retailer_company_id' => $retailerId,
            'integrity' => $data,
        ]);
    }
}
