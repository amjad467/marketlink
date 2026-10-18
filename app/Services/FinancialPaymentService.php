<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PaymentReceiptVoucher;
use App\Models\CustomerLedgerEntry;
use App\Models\OrderInstallment;
use App\Models\OrderStatusLog;
use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Models\Product;
use App\Models\B2bConversation;
use App\Models\B2bMessage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FinancialPaymentService
{
    public function __construct(
        protected B2bFinancialLedgerService $ledgerService
    ) {}

    /**
     * Process Order Reception Payment with options: Full, Partial, or Full Credit (Net 30/Debt).
     */
    public function processOrderReceptionPayment(
        PurchaseOrder $po,
        float $paidAmount,
        string $paymentMethod,
        ?User $actor = null,
        ?string $idempotencyKey = null,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($po, $paidAmount, $paymentMethod, $actor, $idempotencyKey, $notes) {
            $lockedPo = PurchaseOrder::where('id', $po->id)->lockForUpdate()->firstOrFail();

            // Idempotency check: if transaction_reference matches idempotency key, return existing
            if ($idempotencyKey) {
                $existingVoucher = PaymentReceiptVoucher::where('transaction_reference', $idempotencyKey)->first();
                if ($existingVoucher) {
                    return [
                        'success' => true,
                        'idempotent' => true,
                        'purchase_order' => $lockedPo,
                        'voucher' => $existingVoucher,
                        'paid_amount' => (float)$lockedPo->paid_amount,
                        'remaining_debt' => (float)$lockedPo->remaining_debt,
                        'payment_status' => $lockedPo->payment_status,
                    ];
                }
            }

            // Ensure baseline Order Sales Invoice exists in customer ledger
            $this->ledgerService->recordOrderInvoiceLedger($lockedPo, $actor);

            $paid = max(0.0, round($paidAmount, 2));
            if ($paid > (float)$lockedPo->total_amount) {
                throw new InvalidArgumentException("بڕی پارەدان (\${$paid}) ناتوانێت زیاتر بێت لە کۆی گشتی پسوولە (\${$lockedPo->total_amount}).");
            }

            $voucher = null;
            $finalPaid = $paid;
            $remainingDebt = max(0.0, round((float)$lockedPo->total_amount - $finalPaid, 2));
            $paymentStatus = $remainingDebt == 0 ? 'paid' : ($finalPaid > 0 ? 'partially_paid' : 'unpaid');

            // Determine custody status for cash vs direct bank
            $custodyStatus = 'deposited_to_safe';
            if ($paymentMethod === 'cash') {
                $custodyStatus = 'in_driver_custody';
                if ($actor && in_array($actor->role, ['owner', 'manager', 'platform_admin']) && !$actor->isDriver()) {
                    $custodyStatus = 'deposited_to_safe';
                }
            }

            if ($finalPaid > 0) {
                $voucher = $this->ledgerService->recordReceiptVoucher(
                    supplierCompanyId: $lockedPo->supplier_company_id,
                    retailerCompanyId: $lockedPo->retailer_company_id,
                    amount: $finalPaid,
                    paymentMethod: $paymentMethod === 'cash' ? 'cash' : 'bank_transfer',
                    collectedByUserId: $actor?->id,
                    purchaseOrderId: $lockedPo->id,
                    transactionReference: $idempotencyKey,
                    notes: $notes ?: 'پارەی دراو لە کاتی وەرگرتنی فەرمان لەلایەن مارکێتەوە',
                    actor: $actor,
                    updateLinkedDocument: false,
                    custodyStatus: $custodyStatus
                );
            }

            $lockedPo->update([
                'status' => 'delivered',
                'paid_amount' => $finalPaid,
                'remaining_debt' => $remainingDebt,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'actual_delivery_date' => now()->toDateString(),
                'received_at' => now(),
            ]);

            // Deduct delivered stock from warehouse immediately upon delivery verification
            $this->deductDeliveredStockFromWarehouse($lockedPo);

            // Sync any existing installment schedule
            if ($lockedPo->installments()->exists()) {
                $this->syncInstallmentsAfterPayment($lockedPo, $finalPaid, $voucher);
            }

            return [
                'success' => true,
                'idempotent' => false,
                'purchase_order' => $lockedPo,
                'voucher' => $voucher,
                'paid_amount' => $finalPaid,
                'remaining_debt' => $remainingDebt,
                'payment_status' => $paymentStatus,
                'custody_status' => $custodyStatus,
            ];
        });
    }

    /**
     * Process Delivery Verification (OTP, Damage/Discrepancy adjustment, and Driver Cash On Delivery).
     */
    public function processDeliveryVerification(
        PurchaseOrder $po,
        float $cashCollected,
        float $damageAmount,
        ?string $damageNotes = null,
        ?User $actor = null,
        ?int $driverUserId = null,
        ?string $idempotencyKey = null
    ): array {
        return DB::transaction(function () use (
            $po, $cashCollected, $damageAmount, $damageNotes, $actor, $driverUserId, $idempotencyKey
        ) {
            $lockedPo = PurchaseOrder::where('id', $po->id)->lockForUpdate()->firstOrFail();

            // Idempotency check
            if ($idempotencyKey) {
                $existingVoucher = PaymentReceiptVoucher::where('transaction_reference', $idempotencyKey)->first();
                if ($existingVoucher) {
                    return [
                        'success' => true,
                        'idempotent' => true,
                        'purchase_order' => $lockedPo,
                        'voucher' => $existingVoucher,
                    ];
                }
            }

            // Ensure baseline Order Sales Invoice exists in ledger
            $this->ledgerService->recordOrderInvoiceLedger($lockedPo, $actor);

            // Handle damages & discrepancies: deduct from payable total and record discrepancy credit
            if ($damageAmount > 0) {
                if (!$lockedPo->subtotal_amount || (float)$lockedPo->subtotal_amount <= 0) {
                    $lockedPo->subtotal_amount = (float)$lockedPo->total_amount;
                }
                $damageAmount = min($damageAmount, (float)$lockedPo->total_amount);
                $newTotal = max(0.0, round((float)$lockedPo->total_amount - $damageAmount, 2));
                $newRemaining = max(0.0, round((float)$lockedPo->remaining_debt - $damageAmount, 2));

                $lockedPo->total_amount = $newTotal;
                $lockedPo->remaining_debt = $newRemaining;
                $lockedPo->damage_amount = $damageAmount;
                $lockedPo->damage_notes = $damageNotes;

                $this->ledgerService->recordDiscrepancyCreditLedger($lockedPo, $damageAmount, $damageNotes, $actor);
            }

            $voucher = null;
            if ($cashCollected > 0) {
                $collectorId = $driverUserId ?? ($actor?->id);
                $voucher = $this->ledgerService->recordReceiptVoucher(
                    supplierCompanyId: $lockedPo->supplier_company_id,
                    retailerCompanyId: $lockedPo->retailer_company_id,
                    amount: $cashCollected,
                    paymentMethod: 'cash',
                    collectedByUserId: $collectorId,
                    purchaseOrderId: $lockedPo->id,
                    transactionReference: $idempotencyKey,
                    notes: 'پارەی نەختی وەرگیراو لە کاتی گەیاندنی بار (COD)',
                    actor: $actor,
                    updateLinkedDocument: false,
                    custodyStatus: 'in_driver_custody'
                );

                $newPaid = round((float)$lockedPo->paid_amount + $cashCollected, 2);
                $newDebt = max(0.0, round((float)$lockedPo->total_amount - $newPaid, 2));
                $paymentStatus = $newDebt <= 0 ? 'paid' : 'partially_paid';

                $lockedPo->paid_amount = $newPaid;
                $lockedPo->remaining_debt = $newDebt;
                $lockedPo->payment_status = $paymentStatus;
            }

            $lockedPo->status = 'delivered';
            $lockedPo->received_at = now();
            $lockedPo->actual_delivery_date = now()->toDateString();
            $lockedPo->save();

            // Deduct delivered stock from warehouse immediately upon delivery verification
            $this->deductDeliveredStockFromWarehouse($lockedPo);

            // Sync installments if applicable
            if ($cashCollected > 0 && $lockedPo->installments()->exists()) {
                $this->syncInstallmentsAfterPayment($lockedPo, $cashCollected, $voucher);
            }

            return [
                'success' => true,
                'idempotent' => false,
                'purchase_order' => $lockedPo,
                'voucher' => $voucher,
                'damage_adjusted' => $damageAmount,
                'cash_collected' => $cashCollected,
            ];
        });
    }

    /**
     * Process Order Final Settlement & Reconciliation (Complete & Settle).
     */
    public function processOrderCompleteSettlement(
        PurchaseOrder $po,
        ?float $downPayment = 0.0,
        string $paymentMethod = 'cash',
        ?User $actor = null,
        ?string $idempotencyKey = null
    ): array {
        return DB::transaction(function () use ($po, $downPayment, $paymentMethod, $actor, $idempotencyKey) {
            $lockedPo = PurchaseOrder::where('id', $po->id)->lockForUpdate()->firstOrFail();

            // Ensure baseline Order Sales Invoice exists in ledger
            $this->ledgerService->recordOrderInvoiceLedger($lockedPo, $actor);

            $isCash = str_contains(strtolower($paymentMethod), 'cash') || str_contains(strtolower($paymentMethod), 'cod');
            $voucher = null;

            if ($isCash) {
                // If settling as COD Cash, collect any remaining unpaid balance
                $remainingToPay = max(0.0, round((float)$lockedPo->total_amount - (float)$lockedPo->paid_amount, 2));
                if ($remainingToPay > 0) {
                    $voucher = $this->ledgerService->recordReceiptVoucher(
                        supplierCompanyId: $lockedPo->supplier_company_id,
                        retailerCompanyId: $lockedPo->retailer_company_id,
                        amount: $remainingToPay,
                        paymentMethod: 'cash',
                        collectedByUserId: $actor?->id,
                        purchaseOrderId: $lockedPo->id,
                        transactionReference: $idempotencyKey,
                        notes: 'پاکتاوی کۆتایی وەسڵی کاش (COD Final Settlement)',
                        actor: $actor,
                        updateLinkedDocument: false,
                        custodyStatus: 'deposited_to_safe'
                    );
                }

                $lockedPo->paid_amount = (float)$lockedPo->total_amount;
                $lockedPo->remaining_debt = 0.00;
                $lockedPo->payment_status = 'paid';
                $lockedPo->payment_method = 'COD Cash';
            } else {
                // Credit or installment settlement
                $downPayment = max(0.0, (float)$downPayment);
                if ($downPayment > 0) {
                    $additionalPay = min($downPayment, (float)$lockedPo->remaining_debt);
                    if ($additionalPay > 0) {
                        $voucher = $this->ledgerService->recordReceiptVoucher(
                            supplierCompanyId: $lockedPo->supplier_company_id,
                            retailerCompanyId: $lockedPo->retailer_company_id,
                            amount: $additionalPay,
                            paymentMethod: 'bank_transfer',
                            collectedByUserId: $actor?->id,
                            purchaseOrderId: $lockedPo->id,
                            transactionReference: $idempotencyKey,
                            notes: 'پێشەکی یان قیستی سەرەتایی لە کاتی یەکلاکردنەوەدا',
                            actor: $actor,
                            updateLinkedDocument: false,
                            custodyStatus: 'deposited_to_safe'
                        );

                        $newPaid = round((float)$lockedPo->paid_amount + $additionalPay, 2);
                        $newDebt = max(0.0, round((float)$lockedPo->total_amount - $newPaid, 2));
                        $lockedPo->paid_amount = $newPaid;
                        $lockedPo->remaining_debt = $newDebt;
                        $lockedPo->payment_status = $newDebt <= 0 ? 'paid' : 'partially_paid';
                    }
                } else {
                    $lockedPo->remaining_debt = max(0.0, round((float)$lockedPo->total_amount - (float)$lockedPo->paid_amount, 2));
                    $lockedPo->payment_status = $lockedPo->remaining_debt == 0 ? 'paid' : ($lockedPo->paid_amount > 0 ? 'partially_paid' : 'unpaid');
                }

                $lockedPo->payment_method = 'Credit Net 30 Days';
                $lockedPo->credit_due_date = now()->addDays(30)->toDateString();
            }

            $lockedPo->status = 'completed';
            $lockedPo->stock_reserved = false;
            $lockedPo->save();

            // Ensure stock is deducted if not already done at delivery
            $this->deductDeliveredStockFromWarehouse($lockedPo);

            return [
                'success' => true,
                'purchase_order' => $lockedPo,
                'voucher' => $voucher,
                'paid_amount' => (float)$lockedPo->paid_amount,
                'remaining_debt' => (float)$lockedPo->remaining_debt,
            ];
        });
    }

    /**
     * Process Order Cancellation with Accounting Integrity & Counter-Reversal.
     */
    public function processOrderCancellation(PurchaseOrder $po, string $reason, ?User $actor = null): void
    {
        DB::transaction(function () use ($po, $reason, $actor) {
            $lockedPo = PurchaseOrder::where('id', $po->id)->lockForUpdate()->firstOrFail();

            // Check if order was already recorded in customer ledger
            $invoiceEntry = CustomerLedgerEntry::where('supplier_company_id', $lockedPo->supplier_company_id)
                ->where('retailer_company_id', $lockedPo->retailer_company_id)
                ->where('entry_type', 'order_invoice')
                ->where('reference_id', $lockedPo->id)
                ->first();

            if ($invoiceEntry && (float)$lockedPo->remaining_debt > 0) {
                // Record reversing credit entry to offset the remaining unpaid debt
                $currentBalance = $this->ledgerService->getCustomerRunningBalance($lockedPo->supplier_company_id, $lockedPo->retailer_company_id);
                $creditAdjustment = (float)$lockedPo->remaining_debt;
                $newRunningBalance = round($currentBalance - $creditAdjustment, 2);

                CustomerLedgerEntry::create([
                    'supplier_company_id' => $lockedPo->supplier_company_id,
                    'retailer_company_id' => $lockedPo->retailer_company_id,
                    'entry_type' => 'discount_credit',
                    'reference_id' => $lockedPo->id,
                    'reference_number' => 'CAN-' . $lockedPo->po_number,
                    'entry_date' => now(),
                    'description' => "پوچەڵکردنەوەی فەرمانی {$lockedPo->po_number} بەهۆی: {$reason}",
                    'debit_amount' => 0.00,
                    'credit_amount' => $creditAdjustment,
                    'running_balance' => $newRunningBalance,
                    'recorded_by_user_id' => $actor?->id,
                ]);
            }

            // Restock to warehouse if order was already deducted upon delivery, or release reserved stock
            if ($lockedPo->stock_deducted) {
                foreach ($lockedPo->items as $item) {
                    $prod = Product::find($item->product_id);
                    if ($prod) {
                        $qtyDelivered = $item->quantity_delivered !== null ? (int)$item->quantity_delivered : (int)$item->quantity_ordered;
                        if ($qtyDelivered > 0) {
                            $prod->increment('current_stock', $qtyDelivered);
                        }
                    }
                }
                $lockedPo->stock_deducted = false;
            } elseif ($lockedPo->stock_reserved) {
                foreach ($lockedPo->items as $item) {
                    $prod = Product::find($item->product_id);
                    if ($prod) {
                        $qtyOrdered = (int)$item->quantity_ordered;
                        if ($prod->reserved_stock >= $qtyOrdered) {
                            $prod->decrement('reserved_stock', $qtyOrdered);
                        } else {
                            $prod->update(['reserved_stock' => 0]);
                        }
                    }
                }
            }

            $lockedPo->update([
                'status' => 'cancelled',
                'remaining_debt' => 0.00,
                'payment_status' => 'cancelled',
                'stock_reserved' => false,
                'stock_deducted' => false,
            ]);

            // Mark any pending installments as cancelled
            $lockedPo->installments()->where('status', '!=', 'paid')->update([
                'status' => 'overdue',
                'notes' => 'داواکاری هەڵوەشێنرایەوە',
            ]);
        });
    }

    /**
     * Deduct delivered items from warehouse current_stock and release reserved_stock.
     * Idempotent: checks stock_deducted flag so stock is deducted only once.
     */
    public function deductDeliveredStockFromWarehouse(PurchaseOrder $po): void
    {
        if ($po->stock_deducted) {
            return;
        }

        $po->loadMissing('items');

        foreach ($po->items as $item) {
            $prod = Product::find($item->product_id);
            if (!$prod) {
                continue;
            }

            $qtyDelivered = $item->quantity_delivered !== null ? (int)$item->quantity_delivered : (int)$item->quantity_ordered;
            $qtyOrdered = (int)$item->quantity_ordered;

            if ($qtyDelivered > 0) {
                $prod->decrement('current_stock', $qtyDelivered);
            }

            if ($prod->reserved_stock >= $qtyOrdered) {
                $prod->decrement('reserved_stock', $qtyOrdered);
            } else {
                $prod->update(['reserved_stock' => 0]);
            }
        }

        $po->stock_reserved = false;
        $po->stock_deducted = true;
        $po->save();
    }

    /**
     * Reverse a previously verified or recorded Payment Receipt Voucher (Counter-Entry / پێچەوانەکردنەوە).
     */
    public function reversePayment(
        PaymentReceiptVoucher $voucher,
        string $reversalReason,
        User $actor
    ): PaymentReceiptVoucher {
        return DB::transaction(function () use ($voucher, $reversalReason, $actor) {
            $lockedVoucher = PaymentReceiptVoucher::where('id', $voucher->id)->lockForUpdate()->firstOrFail();

            if ($lockedVoucher->custody_status === 'reversed') {
                throw new InvalidArgumentException("ئەم پسوولەیە پێشتر پێچەوانە (Reversed) کراوەتەوە و ناتوانرێت دووبارە دەستکاری بکرێت.");
            }

            $amount = (float)$lockedVoucher->amount;

            // 1. Mark original voucher as reversed without deleting
            $lockedVoucher->update([
                'custody_status' => 'reversed',
                'notes' => trim(($lockedVoucher->notes ? $lockedVoucher->notes . " | " : "") . "[پێچەوانەکراوە / REVERSED: {$reversalReason}]"),
            ]);

            // 2. Create compensatory debit entry in customer ledger
            $currentBalance = $this->ledgerService->getCustomerRunningBalance($lockedVoucher->supplier_company_id, $lockedVoucher->retailer_company_id);
            $newRunningBalance = round($currentBalance + $amount, 2);

            CustomerLedgerEntry::create([
                'supplier_company_id' => $lockedVoucher->supplier_company_id,
                'retailer_company_id' => $lockedVoucher->retailer_company_id,
                'entry_type' => 'opening_balance',
                'reference_id' => $lockedVoucher->id,
                'reference_number' => 'REV-' . $lockedVoucher->voucher_number,
                'entry_date' => now(),
                'description' => "پێچەوانەکردنەوەی وەسڵی {$lockedVoucher->voucher_number}: {$reversalReason}",
                'debit_amount' => $amount,
                'credit_amount' => 0.00,
                'running_balance' => $newRunningBalance,
                'recorded_by_user_id' => $actor->id,
            ]);

            // 3. Re-instate purchase order debt if linked
            if ($lockedVoucher->purchase_order_id) {
                $po = PurchaseOrder::where('id', $lockedVoucher->purchase_order_id)->lockForUpdate()->first();
                if ($po) {
                    $newPaid = max(0.0, round((float)$po->paid_amount - $amount, 2));
                    $newDebt = round((float)$po->remaining_debt + $amount, 2);
                    $newStatus = $newDebt >= (float)$po->total_amount ? 'unpaid' : 'partially_paid';

                    $po->update([
                        'paid_amount' => $newPaid,
                        'remaining_debt' => $newDebt,
                        'payment_status' => $newStatus,
                    ]);

                    OrderStatusLog::create([
                        'purchase_order_id' => $po->id,
                        'from_status' => $po->status,
                        'to_status' => $po->status,
                        'changed_by_name' => $actor->name,
                        'role' => $actor->role,
                        'notes' => "پسوولەی وەرگرتنی \${$amount} پێچەوانە کرایەوە: {$reversalReason}. قەرزی نوێ: \${$newDebt}.",
                    ]);
                }
            }

            // 4. Log Security & Financial Audit
            SecurityAuditLog::logViolation(
                'payment_reversal',
                "پێچەوانەکردنەوەی وەسڵی دارایی ({$lockedVoucher->voucher_number}) بە بڕی \${$amount}. هۆکار: {$reversalReason}",
                request(),
                $lockedVoucher->supplier_company_id,
                $actor->id,
                'medium'
            );

            return $lockedVoucher;
        });
    }

    /**
     * Create a structured Installment Plan for a purchase order's remaining debt.
     */
    public function createInstallmentPlan(
        PurchaseOrder $po,
        int $numberOfInstallments,
        string $frequency = 'monthly',
        ?string $startDate = null
    ): Collection {
        return DB::transaction(function () use ($po, $numberOfInstallments, $frequency, $startDate) {
            $lockedPo = PurchaseOrder::where('id', $po->id)->lockForUpdate()->firstOrFail();
            $debt = (float)$lockedPo->remaining_debt;

            if ($debt <= 0) {
                throw new InvalidArgumentException("قەرزی ئەم داواکارییە سفرە؛ ناتوانرێت پلانی قسط دابنرێت.");
            }
            if ($numberOfInstallments <= 0) {
                throw new InvalidArgumentException("ژمارەی قسطەکان دەبێت لە ١ کەمتر نەبێت.");
            }

            // Remove any incomplete old installments
            $lockedPo->installments()->where('status', '!=', 'paid')->delete();

            $baseAmount = round($debt / $numberOfInstallments, 2);
            $accumulated = 0.0;
            $start = $startDate ? Carbon::parse($startDate) : now()->addMonth();
            $created = collect();

            for ($i = 1; $i <= $numberOfInstallments; $i++) {
                $due = match ($frequency) {
                    'weekly' => (clone $start)->addWeeks($i - 1),
                    'biweekly' => (clone $start)->addWeeks(($i - 1) * 2),
                    default => (clone $start)->addMonths($i - 1),
                };

                $amount = ($i === $numberOfInstallments)
                    ? round($debt - $accumulated, 2)
                    : $baseAmount;
                $accumulated += $amount;

                $inst = OrderInstallment::create([
                    'purchase_order_id' => $lockedPo->id,
                    'installment_number' => $i,
                    'due_date' => $due->toDateString(),
                    'amount' => $amount,
                    'paid_amount' => 0.00,
                    'remaining_amount' => $amount,
                    'status' => 'pending',
                    'notes' => "قسطی {$i} لە کۆی {$numberOfInstallments} بە وادەی {$due->toDateString()}",
                ]);

                $created->push($inst);
            }

            $lockedPo->update([
                'payment_terms' => "پلانی قسط ({$numberOfInstallments} قسط)",
                'credit_due_date' => $start->toDateString(),
            ]);

            return $created;
        });
    }

    /**
     * Settle an installment payment with idempotency protection.
     */
    public function settleOrderInstallment(
        OrderInstallment $installment,
        float $amount,
        string $paymentMethod = 'cash',
        ?User $actor = null,
        ?string $idempotencyKey = null
    ): PaymentReceiptVoucher {
        return DB::transaction(function () use ($installment, $amount, $paymentMethod, $actor, $idempotencyKey) {
            $lockedInst = OrderInstallment::where('id', $installment->id)->lockForUpdate()->firstOrFail();
            $lockedPo = PurchaseOrder::where('id', $lockedInst->purchase_order_id)->lockForUpdate()->firstOrFail();

            $pay = min($amount, (float)$lockedInst->remaining_amount);
            if ($pay <= 0) {
                throw new InvalidArgumentException("بڕی پارەدان دەبێت لە سفر گەورەتر بێت.");
            }

            $voucher = $this->ledgerService->recordReceiptVoucher(
                supplierCompanyId: $lockedPo->supplier_company_id,
                retailerCompanyId: $lockedPo->retailer_company_id,
                amount: $pay,
                paymentMethod: $paymentMethod,
                collectedByUserId: $actor?->id,
                purchaseOrderId: $lockedPo->id,
                transactionReference: $idempotencyKey,
                notes: "دانەوەی قسطی {$lockedInst->installment_number} بۆ فەرمانی {$lockedPo->po_number}",
                actor: $actor,
                updateLinkedDocument: true
            );

            $newPaid = round((float)$lockedInst->paid_amount + $pay, 2);
            $newRemaining = max(0.0, round((float)$lockedInst->amount - $newPaid, 2));

            $lockedInst->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $newRemaining,
                'status' => $newRemaining <= 0 ? 'paid' : 'partially_paid',
                'paid_at' => $newRemaining <= 0 ? now() : null,
                'payment_receipt_voucher_id' => $voucher->id,
            ]);

            return $voucher;
        });
    }

    /**
     * Distribute general payments against existing installment records.
     */
    protected function syncInstallmentsAfterPayment(PurchaseOrder $po, float $amount, ?PaymentReceiptVoucher $voucher = null): void
    {
        $remainingPayment = $amount;
        $unpaidInstallments = $po->installments()
            ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
            ->orderBy('installment_number', 'asc')
            ->get();

        foreach ($unpaidInstallments as $inst) {
            if ($remainingPayment <= 0) {
                break;
            }

            $needed = (float)$inst->remaining_amount;
            if ($needed <= 0) {
                continue;
            }

            $apply = min($remainingPayment, $needed);
            $newPaid = round((float)$inst->paid_amount + $apply, 2);
            $newRemaining = max(0.0, round((float)$inst->amount - $newPaid, 2));

            $inst->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $newRemaining,
                'status' => $newRemaining <= 0 ? 'paid' : 'partially_paid',
                'paid_at' => $newRemaining <= 0 ? now() : null,
                'payment_receipt_voucher_id' => $voucher?->id ?? $inst->payment_receipt_voucher_id,
            ]);

            $remainingPayment -= $apply;
        }
    }

    /**
     * Verify complete accounting balance and reconciliation integrity between ledgers, orders and vouchers.
     */
    public function verifyAccountBalanceIntegrity(int $supplierCompanyId, int $retailerCompanyId): array
    {
        $ledgerEntries = CustomerLedgerEntry::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalDebits = (float)$ledgerEntries->sum('debit_amount');
        $totalCredits = (float)$ledgerEntries->sum('credit_amount');
        $calculatedBalance = round($totalDebits - $totalCredits, 2);
        $lastRunningBalance = $ledgerEntries->isNotEmpty() ? (float)$ledgerEntries->last()->running_balance : 0.0;

        $totalActiveOrderDebt = (float)PurchaseOrder::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->whereNotIn('status', ['cancelled'])
            ->sum('remaining_debt');

        $driverCashInCustody = (float)PaymentReceiptVoucher::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->where('custody_status', 'in_driver_custody')
            ->where('payment_method', 'cash')
            ->sum('amount');

        $depositedToSafe = (float)PaymentReceiptVoucher::where('supplier_company_id', $supplierCompanyId)
            ->where('retailer_company_id', $retailerCompanyId)
            ->where('custody_status', 'deposited_to_safe')
            ->sum('amount');

        $isLedgerConsistent = abs($calculatedBalance - $lastRunningBalance) < 0.05;

        return [
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'calculated_ledger_balance' => $calculatedBalance,
            'running_ledger_balance' => $lastRunningBalance,
            'total_active_order_debt' => $totalActiveOrderDebt,
            'driver_cash_in_custody' => $driverCashInCustody,
            'deposited_to_safe' => $depositedToSafe,
            'is_ledger_consistent' => $isLedgerConsistent,
        ];
    }
}
