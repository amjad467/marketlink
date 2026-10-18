<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CustomerLedgerEntry;
use App\Models\PaymentReceiptVoucher;
use App\Models\PurchaseOrder;
use App\Models\CashVanSale;
use App\Models\CashVanReturn;
use App\Models\CashClearanceSettlement;
use App\Models\User;
use App\Models\B2bConversation;
use App\Models\B2bMessage;
use App\Models\B2bNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use InvalidArgumentException;

class B2bFinancialLedgerService
{
    /**
     * Record a formal Payment Receipt Voucher (سند قبض) and update ledger.
     */
    public function recordReceiptVoucher(
        int $supplierCompanyId,
        int $retailerCompanyId,
        float $amount,
        string $paymentMethod = 'cash',
        ?int $collectedByUserId = null,
        ?int $purchaseOrderId = null,
        ?int $cashVanSaleId = null,
        ?float $amountIqd = null,
        ?float $exchangeRate = null,
        ?string $chequeNumber = null,
        ?string $chequeDueDate = null,
        ?string $transactionReference = null,
        ?string $notes = null,
        ?User $actor = null,
        bool $updateLinkedDocument = true,
        ?string $receiptDate = null,
        ?string $custodyStatus = null
    ): PaymentReceiptVoucher {
        if ($amount <= 0) {
            throw new InvalidArgumentException("بڕی پارەی وەرگیراو دەبێت لە سفر گەورەتر بێت.");
        }

        return DB::transaction(function () use (
            $supplierCompanyId, $retailerCompanyId, $amount, $paymentMethod,
            $collectedByUserId, $purchaseOrderId, $cashVanSaleId, $amountIqd,
            $exchangeRate, $chequeNumber, $chequeDueDate, $transactionReference,
            $notes, $actor, $updateLinkedDocument, $receiptDate, $custodyStatus
        ) {
            $supplier = Company::findOrFail($supplierCompanyId);
            $retailer = Company::findOrFail($retailerCompanyId);

            $po = $purchaseOrderId ? PurchaseOrder::where('id', $purchaseOrderId)->where('supplier_company_id', $supplierCompanyId)->lockForUpdate()->first() : null;
            $vanSale = $cashVanSaleId ? CashVanSale::where('id', $cashVanSaleId)->where('supplier_company_id', $supplierCompanyId)->lockForUpdate()->first() : null;

            // Determine custody status:
            // If explicit custodyStatus passed, use it. Otherwise, if collected in cash by driver or field agent, in_driver_custody.
            $finalCustody = $custodyStatus;
            if (!$finalCustody) {
                $finalCustody = 'deposited_to_safe';
                if ($paymentMethod === 'cash') {
                    $finalCustody = 'in_driver_custody';
                    if ($collectedByUserId) {
                        $collector = User::find($collectedByUserId);
                        if ($collector && in_array($collector->role, ['owner', 'manager', 'platform_admin']) && !$collector->isDriver()) {
                            $finalCustody = 'deposited_to_safe';
                        }
                    }
                }
            }

            $voucherNumber = 'RV-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            $rDate = $receiptDate ? Carbon::parse($receiptDate)->toDateString() : now()->toDateString();
            $entryDateTime = $receiptDate ? Carbon::parse($receiptDate) : now();

            $voucher = PaymentReceiptVoucher::create([
                'voucher_number' => $voucherNumber,
                'supplier_company_id' => $supplierCompanyId,
                'retailer_company_id' => $retailerCompanyId,
                'collected_by_user_id' => $collectedByUserId ?? $actor?->id,
                'purchase_order_id' => $purchaseOrderId,
                'cash_van_sale_id' => $cashVanSaleId,
                'amount' => $amount,
                'currency' => 'USD',
                'amount_iqd' => $amountIqd,
                'exchange_rate' => $exchangeRate,
                'payment_method' => $paymentMethod,
                'custody_status' => $finalCustody,
                'verified_by_user_id' => $finalCustody === 'deposited_to_safe' ? ($actor?->id ?? null) : null,
                'verified_at' => $finalCustody === 'deposited_to_safe' ? now() : null,
                'cheque_number' => $chequeNumber,
                'cheque_due_date' => $chequeDueDate,
                'transaction_reference' => $transactionReference,
                'receipt_date' => $rDate,
                'notes' => $notes,
            ]);

            // Update PurchaseOrder if linked and requested
            if ($po && $updateLinkedDocument) {
                $newPaid = round($po->paid_amount + $amount, 2);
                $newDebt = max(0.0, round($po->remaining_debt - $amount, 2));
                $newStatus = $newDebt <= 0 ? 'paid' : 'partially_paid';

                $po->update([
                    'paid_amount' => $newPaid,
                    'remaining_debt' => $newDebt,
                    'payment_status' => $newStatus,
                ]);
            }

            // Update CashVanSale if linked and requested
            if ($vanSale && $updateLinkedDocument) {
                $newPaid = round($vanSale->paid_amount + $amount, 2);
                $newBalance = max(0.0, round($vanSale->balance_due - $amount, 2));
                $newStatus = $newBalance <= 0 ? 'paid' : 'partially_paid';

                $vanSale->update([
                    'paid_amount' => $newPaid,
                    'balance_due' => $newBalance,
                    'payment_status' => $newStatus,
                ]);
            }

            // Record Credit Entry in Customer Ledger
            $currentBalance = $this->getCustomerRunningBalance($supplierCompanyId, $retailerCompanyId);
            $newRunningBalance = round($currentBalance - $amount, 2);

            $refDesc = "پسوولەی وەرگرتنی پارە (RV): " . match ($paymentMethod) {
                'cash' => 'نەقد',
                'bank_transfer' => 'حەواڵەی بانکی',
                'fib_fastpay' => 'ئەلیکترۆنی (FIB/FastPay)',
                'cheque' => 'چەک (' . ($chequeNumber ?? '') . ')',
                default => $paymentMethod
            };
            if ($po) {
                $refDesc .= " — فەرمانی {$po->po_number}";
            } elseif ($vanSale) {
                $refDesc .= " — کاش ڤان {$vanSale->sale_number}";
            }

            CustomerLedgerEntry::create([
                'supplier_company_id' => $supplierCompanyId,
                'retailer_company_id' => $retailerCompanyId,
                'entry_type' => 'receipt_voucher',
                'reference_id' => $voucher->id,
                'reference_number' => $voucher->voucher_number,
                'entry_date' => $entryDateTime,
                'description' => $refDesc,
                'debit_amount' => 0.00,
                'credit_amount' => $amount,
                'running_balance' => $newRunningBalance,
                'recorded_by_user_id' => $actor?->id,
            ]);

            // Real-time B2B Communication Notice
            $conv = B2bConversation::firstOrCreate([
                'supplier_company_id' => $supplierCompanyId,
                'retailer_company_id' => $retailerCompanyId,
            ]);

            $methodKu = match($paymentMethod) {
                'cash' => 'کاش / نەقد',
                'bank_transfer' => 'حەواڵەی بانکی',
                'fib_fastpay' => 'فاست پەی / FIB',
                'cheque' => 'چەکی بانکی',
                default => $paymentMethod
            };

            B2bMessage::create([
                'conversation_id' => $conv->id,
                'sender_company_id' => $supplierCompanyId,
                'message_type' => 'po_notice',
                'body' => "🧾 **پسوولەی وەرگرتنی پارە ({$voucher->voucher_number})**:\nبڕی \${$amount} بە سەرکەوتوویی وەرگیرا بە شێوازی [{$methodKu}].\nڕەسیدی ماوەی کڕیار: \${$newRunningBalance}.",
            ]);
            $conv->update(['last_message_at' => now()]);

            B2bNotification::send(
                $retailerCompanyId,
                '🧾 پسوولەی وەرگرتنی پارە',
                "بڕی \${$amount} وەکو وەسڵی فەرمی ژمارە {$voucher->voucher_number} تۆمارکرا.",
                'payment',
                route('financial.voucher.print', $voucher->id),
                null,
                ['voucher_id' => $voucher->id, 'amount' => $amount]
            );

            return $voucher;
        });
    }

    /**
     * Record Order Sales Invoice in Customer Ledger (Debit: Increases Retailer Debt).
     */
    public function recordOrderInvoiceLedger(PurchaseOrder $po, ?User $actor = null): CustomerLedgerEntry
    {
        return DB::transaction(function () use ($po, $actor) {
            $existing = CustomerLedgerEntry::where('supplier_company_id', $po->supplier_company_id)
                ->where('retailer_company_id', $po->retailer_company_id)
                ->where('entry_type', 'order_invoice')
                ->where('reference_id', $po->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $currentBalance = $this->getCustomerRunningBalance($po->supplier_company_id, $po->retailer_company_id);
            $newRunningBalance = round($currentBalance + (float)$po->total_amount, 2);

            $entry = CustomerLedgerEntry::create([
                'supplier_company_id' => $po->supplier_company_id,
                'retailer_company_id' => $po->retailer_company_id,
                'entry_type' => 'order_invoice',
                'reference_id' => $po->id,
                'reference_number' => $po->po_number,
                'entry_date' => $po->created_at ?? now(),
                'description' => "فەرمانی کڕینی فەرمی (PO): {$po->po_number} — {$po->items()->count()} بەرهەم",
                'debit_amount' => (float)$po->total_amount,
                'credit_amount' => 0.00,
                'running_balance' => $newRunningBalance,
                'recorded_by_user_id' => $actor?->id,
            ]);

            // If the order had an initial paid amount, record its credit entry and voucher
            if ((float)$po->paid_amount > 0) {
                $this->recordReceiptVoucher(
                    $po->supplier_company_id,
                    $po->retailer_company_id,
                    (float)$po->paid_amount,
                    $po->payment_method ?? 'cash',
                    null,
                    $po->id,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    'پێشەکی یان پارەدانی سەرەتایی لە کاتی فەرماندا',
                    $actor,
                    false
                );
            }

            return $entry;
        });
    }

    /**
     * Record Cash Van Sale in Customer Ledger (Debit for total, and Credit if partial/full cash paid).
     */
    public function recordCashVanSaleLedger(CashVanSale $sale, ?User $actor = null): void
    {
        DB::transaction(function () use ($sale, $actor) {
            $existing = CustomerLedgerEntry::where('supplier_company_id', $sale->supplier_company_id)
                ->where('retailer_company_id', $sale->retailer_company_id)
                ->where('entry_type', 'cash_van_sale')
                ->where('reference_id', $sale->id)
                ->first();

            if ($existing) {
                return;
            }

            // 1. Debit entry for full sale amount
            $currentBalance = $this->getCustomerRunningBalance($sale->supplier_company_id, $sale->retailer_company_id);
            $newRunningBalance = round($currentBalance + (float)$sale->total_amount, 2);

            CustomerLedgerEntry::create([
                'supplier_company_id' => $sale->supplier_company_id,
                'retailer_company_id' => $sale->retailer_company_id,
                'entry_type' => 'cash_van_sale',
                'reference_id' => $sale->id,
                'reference_number' => $sale->sale_number,
                'entry_date' => $sale->created_at ?? now(),
                'description' => "فرۆشتنی ڕاستەوخۆی کاش ڤان ({$sale->van?->vehicle_name}): وەسڵی {$sale->sale_number}",
                'debit_amount' => (float)$sale->total_amount,
                'credit_amount' => 0.00,
                'running_balance' => $newRunningBalance,
                'recorded_by_user_id' => $actor?->id ?? $sale->driver_user_id,
            ]);

            // 2. If paid at point of sale, issue Receipt Voucher and credit ledger
            if ((float)$sale->paid_amount > 0) {
                $this->recordReceiptVoucher(
                    $sale->supplier_company_id,
                    $sale->retailer_company_id,
                    (float)$sale->paid_amount,
                    $sale->payment_method === 'cash' ? 'cash' : 'split',
                    $sale->driver_user_id,
                    null,
                    $sale->id,
                    null,
                    null,
                    null,
                    null,
                    null,
                    'پارەی وەرگیراوی نەقد لە کاتی فرۆشتنی کاش ڤاندا',
                    $actor ?? $sale->driver,
                    false
                );
            }
        });
    }

    /**
     * Record Cash Van Return in Customer Ledger (Credit: Decreases Retailer Debt).
     */
    public function recordCashVanReturnLedger(CashVanReturn $return, ?User $actor = null): CustomerLedgerEntry
    {
        return DB::transaction(function () use ($return, $actor) {
            $sale = $return->sale;
            $supplierId = $sale->supplier_company_id;
            $retailerId = $sale->retailer_company_id;
            $amount = (float)($return->total_refund_amount ?? $return->refund_amount ?? 0);

            $currentBalance = $this->getCustomerRunningBalance($supplierId, $retailerId);
            $newRunningBalance = round($currentBalance - $amount, 2);

            return CustomerLedgerEntry::create([
                'supplier_company_id' => $supplierId,
                'retailer_company_id' => $retailerId,
                'entry_type' => 'sale_return',
                'reference_id' => $return->id,
                'reference_number' => $return->return_number,
                'entry_date' => $return->created_at ?? now(),
                'description' => "گەڕانەوەی کاڵا لە وەسڵی {$sale->sale_number} — هۆکار: {$return->return_reason}",
                'debit_amount' => 0.00,
                'credit_amount' => $amount,
                'running_balance' => $newRunningBalance,
                'recorded_by_user_id' => $actor?->id,
            ]);
        });
    }

    /**
     * Record Discrepancy Credit / Return in Customer Ledger (Credit: Decreases Retailer Debt for Damaged/Missing/Expired/Returned goods).
     */
    public function recordDiscrepancyCreditLedger(PurchaseOrder $po, float $damageAmount, ?string $damageNotes = null, ?User $actor = null): ?CustomerLedgerEntry
    {
        if ($damageAmount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($po, $damageAmount, $damageNotes, $actor) {
            $existing = CustomerLedgerEntry::where('supplier_company_id', $po->supplier_company_id)
                ->where('retailer_company_id', $po->retailer_company_id)
                ->where('entry_type', 'order_return')
                ->where('reference_id', $po->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $currentBalance = $this->getCustomerRunningBalance($po->supplier_company_id, $po->retailer_company_id);
            $newRunningBalance = round($currentBalance - $damageAmount, 2);

            return CustomerLedgerEntry::create([
                'supplier_company_id' => $po->supplier_company_id,
                'retailer_company_id' => $po->retailer_company_id,
                'entry_type' => 'order_return',
                'reference_id' => $po->id,
                'reference_number' => $po->po_number,
                'entry_date' => now(),
                'description' => "داشکاندنی کەمی، تەلەف، ئێکسپایەر و گەڕاوەی کاڵا ({$po->po_number})" . ($damageNotes ? ": {$damageNotes}" : ""),
                'debit_amount' => 0.00,
                'credit_amount' => $damageAmount,
                'running_balance' => $newRunningBalance,
                'recorded_by_user_id' => $actor?->id,
            ]);
        });
    }

    /**
     * Get real-time running balance of a customer relative to a supplier.
     */
    public function getCustomerRunningBalance(int $supplierCompanyId, int $retailerCompanyId): float
    {
        $lastEntry = CustomerLedgerEntry::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastEntry) {
            return (float)$lastEntry->running_balance;
        }

        return 0.0;
    }

    /**
     * Settle driver / mandub cash clearance at end of shift/day into company treasury.
     */
    public function clearDriverCash(
        int $supplierCompanyId,
        int $driverUserId,
        float $actualCashDeposited,
        User $verifier,
        ?string $notes = null
    ): CashClearanceSettlement {
        return DB::transaction(function () use (
            $supplierCompanyId, $driverUserId, $actualCashDeposited, $verifier, $notes
        ) {
            // Find all unverified vouchers collected by this driver
            $vouchers = PaymentReceiptVoucher::where('supplier_company_id', $supplierCompanyId)
                ->where('collected_by_user_id', $driverUserId)
                ->where('custody_status', 'in_driver_custody')
                ->get();

            $totalCash = (float) $vouchers->sum('amount');
            $discrepancy = round($actualCashDeposited - $totalCash, 2);

            $settlementNumber = 'CS-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $settlement = CashClearanceSettlement::create([
                'settlement_number' => $settlementNumber,
                'supplier_company_id' => $supplierCompanyId,
                'driver_user_id' => $driverUserId,
                'verified_by_user_id' => $verifier->id,
                'settlement_date' => now()->toDateString(),
                'total_cash_collected' => $totalCash,
                'total_iqd_collected' => 0.00,
                'actual_cash_deposited' => $actualCashDeposited,
                'discrepancy_amount' => $discrepancy,
                'status' => abs($discrepancy) < 0.01 ? 'verified' : 'disputed',
                'notes' => $notes,
            ]);

            // Mark vouchers as verified in main safe
            PaymentReceiptVoucher::whereIn('id', $vouchers->pluck('id'))
                ->update([
                    'custody_status' => 'deposited_to_safe',
                    'verified_by_user_id' => $verifier->id,
                    'verified_at' => now(),
                ]);

            return $settlement;
        });
    }

    /**
     * Verify and deposit a single collected voucher into company safe/treasury.
     */
    public function verifyVoucherIntoSafe(int $voucherId, User $verifier, ?string $notes = null): PaymentReceiptVoucher
    {
        return DB::transaction(function () use ($voucherId, $verifier, $notes) {
            $voucher = PaymentReceiptVoucher::lockForUpdate()->findOrFail($voucherId);
            $voucher->update([
                'custody_status' => 'deposited_to_safe',
                'verified_by_user_id' => $verifier->id,
                'verified_at' => now(),
                'notes' => $notes ? trim(($voucher->notes ? $voucher->notes . " | " : "") . $notes) : $voucher->notes,
            ]);
            return $voucher;
        });
    }

    /**
     * Record Opening Balance (قەرزی پێش سیستم / باڵانسی سەرەتایی) for a retailer.
     */
    public function recordOpeningBalance(
        int $supplierCompanyId,
        int $retailerCompanyId,
        float $amount,
        string $type = 'debit', // debit: قەرز لەسەر کڕیار، credit: پێشەکی کڕیار
        ?string $entryDate = null,
        ?string $notes = null,
        ?User $actor = null
    ): CustomerLedgerEntry {
        if ($amount <= 0) {
            throw new InvalidArgumentException("بڕی باڵانسی سەرەتایی دەبێت لە سفر گەورەتر بێت.");
        }

        return DB::transaction(function () use (
            $supplierCompanyId, $retailerCompanyId, $amount, $type, $entryDate, $notes, $actor
        ) {
            $currentBalance = $this->getCustomerRunningBalance($supplierCompanyId, $retailerCompanyId);

            $debitAmount = $type === 'debit' ? $amount : 0.00;
            $creditAmount = $type === 'credit' ? $amount : 0.00;
            $newRunningBalance = round($currentBalance + $debitAmount - $creditAmount, 2);

            $refNumber = 'OPB-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            $description = $notes ?: ($type === 'debit' ? 'باڵانسی سەرەتایی / قەرزی پێش سیستم' : 'باڵانسی سەرەتایی پێشەکی کڕیار');

            return CustomerLedgerEntry::create([
                'supplier_company_id' => $supplierCompanyId,
                'retailer_company_id' => $retailerCompanyId,
                'entry_type' => 'opening_balance',
                'reference_id' => null,
                'reference_number' => $refNumber,
                'entry_date' => $entryDate ? Carbon::parse($entryDate) : now(),
                'description' => $description,
                'debit_amount' => $debitAmount,
                'credit_amount' => $creditAmount,
                'running_balance' => $newRunningBalance,
                'recorded_by_user_id' => $actor?->id,
            ]);
        });
    }

    /**
     * Generate comprehensive Statement of Account (کەشف حیساب) with Aging Analysis.
     */
    public function getUnifiedStatementData(
        int $supplierCompanyId,
        int $retailerCompanyId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $supplier = Company::findOrFail($supplierCompanyId);
        $retailer = Company::findOrFail($retailerCompanyId);

        // Synchronize any un-ledgered orders or cash van sales first
        $this->syncHistoricalTransactions($supplierCompanyId, $retailerCompanyId);

        $query = CustomerLedgerEntry::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc');

        if ($fromDate) {
            $query->where('entry_date', '>=', $fromDate . ' 00:00:00');
        }
        if ($toDate) {
            $query->where('entry_date', '<=', $toDate . ' 23:59:59');
        }

        $entries = $query->get();

        $openingBalance = 0.0;
        if ($fromDate) {
            $prevEntry = CustomerLedgerEntry::where('supplier_company_id', $supplierCompanyId)
                ->where('retailer_company_id', $retailerCompanyId)
                ->where('entry_date', '<', $fromDate . ' 00:00:00')
                ->orderBy('entry_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($prevEntry) {
                $openingBalance = (float)$prevEntry->running_balance;
            }
        }

        $totalDebit = (float)$entries->sum('debit_amount');
        $totalCredit = (float)$entries->sum('credit_amount');
        $netBalance = $entries->isNotEmpty() ? (float)$entries->last()->running_balance : $openingBalance;

        // Aging Analysis Calculation (0-30, 31-60, 61-90, 90+ days)
        $aging = $this->calculateDebtAging($supplierCompanyId, $retailerCompanyId);

        return [
            'supplier' => $supplier,
            'retailer' => $retailer,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'opening_balance' => $openingBalance,
            'entries' => $entries,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'net_balance' => $netBalance,
            'aging' => $aging,
        ];
    }

    /**
     * Compute Debt Aging Buckets for a Customer.
     */
    protected function calculateDebtAging(int $supplierCompanyId, int $retailerCompanyId): array
    {
        $now = now();
        $buckets = [
            'current_30' => 0.0, // 0 - 30 days
            'overdue_60' => 0.0, // 31 - 60 days
            'overdue_90' => 0.0, // 61 - 90 days
            'overdue_90_plus' => 0.0, // > 90 days
        ];

        // 1. Purchase Orders with remaining debt
        $orders = PurchaseOrder::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->where('remaining_debt', '>', 0)
            ->whereNotIn('status', ['cancelled'])
            ->get();

        foreach ($orders as $o) {
            $days = $o->created_at ? $o->created_at->diffInDays($now) : 0;
            $debt = (float)$o->remaining_debt;
            if ($days <= 30) {
                $buckets['current_30'] += $debt;
            } elseif ($days <= 60) {
                $buckets['overdue_60'] += $debt;
            } elseif ($days <= 90) {
                $buckets['overdue_90'] += $debt;
            } else {
                $buckets['overdue_90_plus'] += $debt;
            }
        }

        // 2. Cash Van Sales with balance due
        $sales = CashVanSale::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', ['cancelled'])
            ->get();

        foreach ($sales as $s) {
            $days = $s->created_at ? $s->created_at->diffInDays($now) : 0;
            $debt = (float)$s->balance_due;
            if ($days <= 30) {
                $buckets['current_30'] += $debt;
            } elseif ($days <= 60) {
                $buckets['overdue_60'] += $debt;
            } elseif ($days <= 90) {
                $buckets['overdue_90'] += $debt;
            } else {
                $buckets['overdue_90_plus'] += $debt;
            }
        }

        return $buckets;
    }

    /**
     * Sync any un-ledgered existing orders and sales into ledger entries to ensure seamless continuity.
     */
    public function syncHistoricalTransactions(int $supplierCompanyId, int $retailerCompanyId): void
    {
        $hasEntries = CustomerLedgerEntry::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->exists();

        if ($hasEntries) {
            return;
        }

        // Seed initial ledger entries from historical orders
        $orders = PurchaseOrder::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('created_at', 'asc')
            ->get();

        $running = 0.0;
        foreach ($orders as $po) {
            $running += (float)$po->total_amount;
            CustomerLedgerEntry::create([
                'supplier_company_id' => $supplierCompanyId,
                'retailer_company_id' => $retailerCompanyId,
                'entry_type' => 'order_invoice',
                'reference_id' => $po->id,
                'reference_number' => $po->po_number,
                'entry_date' => $po->created_at,
                'description' => "فەرمانی کڕینی پێشوو: {$po->po_number}",
                'debit_amount' => (float)$po->total_amount,
                'credit_amount' => 0.00,
                'running_balance' => $running,
            ]);

            if ((float)$po->paid_amount > 0) {
                $running -= (float)$po->paid_amount;
                $voucherNumber = 'RV-' . $po->created_at->format('Ymd') . '-' . strtoupper(Str::random(5));
                $voucher = PaymentReceiptVoucher::create([
                    'voucher_number' => $voucherNumber,
                    'supplier_company_id' => $supplierCompanyId,
                    'retailer_company_id' => $retailerCompanyId,
                    'purchase_order_id' => $po->id,
                    'amount' => (float)$po->paid_amount,
                    'currency' => 'USD',
                    'payment_method' => $po->payment_method ?? 'cash',
                    'custody_status' => 'deposited_to_safe',
                    'receipt_date' => $po->created_at->toDateString(),
                    'notes' => 'دانەوەی سەرەتایی قەرزی فەرمان',
                ]);

                CustomerLedgerEntry::create([
                    'supplier_company_id' => $supplierCompanyId,
                    'retailer_company_id' => $retailerCompanyId,
                    'entry_type' => 'receipt_voucher',
                    'reference_id' => $voucher->id,
                    'reference_number' => $voucherNumber,
                    'entry_date' => $po->created_at,
                    'description' => "دانەوەی قەرزی فەرمان: {$po->po_number}",
                    'debit_amount' => 0.00,
                    'credit_amount' => (float)$po->paid_amount,
                    'running_balance' => $running,
                ]);
            }
        }

        // Seed van sales if any
        $vanSales = CashVanSale::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($vanSales as $sale) {
            $running += (float)$sale->total_amount;
            CustomerLedgerEntry::create([
                'supplier_company_id' => $supplierCompanyId,
                'retailer_company_id' => $retailerCompanyId,
                'entry_type' => 'cash_van_sale',
                'reference_id' => $sale->id,
                'reference_number' => $sale->sale_number,
                'entry_date' => $sale->created_at,
                'description' => "فرۆشتنی کاش ڤان: {$sale->sale_number}",
                'debit_amount' => (float)$sale->total_amount,
                'credit_amount' => 0.00,
                'running_balance' => $running,
            ]);

            if ((float)$sale->paid_amount > 0) {
                $running -= (float)$sale->paid_amount;
                $voucherNumber = 'RV-' . $sale->created_at->format('Ymd') . '-' . strtoupper(Str::random(5));
                $voucher = PaymentReceiptVoucher::create([
                    'voucher_number' => $voucherNumber,
                    'supplier_company_id' => $supplierCompanyId,
                    'retailer_company_id' => $retailerCompanyId,
                    'cash_van_sale_id' => $sale->id,
                    'collected_by_user_id' => $sale->driver_user_id,
                    'amount' => (float)$sale->paid_amount,
                    'currency' => 'USD',
                    'payment_method' => $sale->payment_method === 'cash' ? 'cash' : 'split',
                    'custody_status' => 'in_driver_custody',
                    'receipt_date' => $sale->created_at->toDateString(),
                    'notes' => 'پارەی نەقدی کاتی فرۆشتنی کاش ڤان',
                ]);

                CustomerLedgerEntry::create([
                    'supplier_company_id' => $supplierCompanyId,
                    'retailer_company_id' => $retailerCompanyId,
                    'entry_type' => 'receipt_voucher',
                    'reference_id' => $voucher->id,
                    'reference_number' => $voucherNumber,
                    'entry_date' => $sale->created_at,
                    'description' => "پارەدانی نەقدی کاش ڤان: {$sale->sale_number}",
                    'debit_amount' => 0.00,
                    'credit_amount' => (float)$sale->paid_amount,
                    'running_balance' => $running,
                ]);
            }
        }
    }
}
