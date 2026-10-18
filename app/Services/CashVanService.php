<?php

namespace App\Services;

use App\Models\CashVan;
use App\Models\CashVanInventory;
use App\Models\CashVanStockTransfer;
use App\Models\CashVanStockTransferItem;
use App\Models\CashVanSale;
use App\Models\CashVanSaleItem;
use App\Models\CashVanReturn;
use App\Models\CashVanReturnItem;
use App\Models\CashVanExpense;
use App\Models\CashVanReconciliation;
use App\Models\CashVanRouteStop;
use App\Models\Company;
use App\Models\Product;
use App\Models\RetailerProfile;
use App\Models\User;
use App\Models\SecurityAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CashVanService
{
    /**
     * Load stock from supplier's warehouse into the vehicle inventory.
     */
    public function loadStockToVan(CashVan $van, array $items, User $user, ?string $notes = null): CashVanStockTransfer
    {
        return DB::transaction(function () use ($van, $items, $user, $notes) {
            $transferNumber = 'TRF-LOAD-' . strtoupper(Str::random(8));

            $transfer = CashVanStockTransfer::create([
                'transfer_number' => $transferNumber,
                'supplier_company_id' => $van->company_id,
                'cash_van_id' => $van->id,
                'transfer_type' => 'load_to_van',
                'status' => 'completed',
                'created_by_user_id' => $user->id,
                'approved_by_user_id' => $user->id,
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    continue;
                }

                $product = Product::where('id', $productId)
                    ->where('supplier_company_id', $van->company_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->current_stock < $quantity) {
                    throw new InvalidArgumentException(
                        "ڕێژەی پێویست لە مەخزەنی سەرەکی بەردەست نییە بۆ کاڵای: {$product->title}. بەردەست: {$product->current_stock}, داواکراو: {$quantity}"
                    );
                }

                // Deduct from warehouse
                $product->decrement('current_stock', $quantity);

                // Add to van inventory
                $vanInv = CashVanInventory::firstOrCreate(
                    ['cash_van_id' => $van->id, 'product_id' => $product->id],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );
                $vanInv->increment('quantity', $quantity);

                CashVanStockTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
            }

            return $transfer;
        });
    }

    /**
     * Return unsold stock from vehicle back to warehouse.
     */
    public function returnStockToWarehouse(CashVan $van, array $items, User $user, ?string $notes = null): CashVanStockTransfer
    {
        return DB::transaction(function () use ($van, $items, $user, $notes) {
            $transferNumber = 'TRF-RET-' . strtoupper(Str::random(8));

            $transfer = CashVanStockTransfer::create([
                'transfer_number' => $transferNumber,
                'supplier_company_id' => $van->company_id,
                'cash_van_id' => $van->id,
                'transfer_type' => 'return_to_warehouse',
                'status' => 'completed',
                'created_by_user_id' => $user->id,
                'approved_by_user_id' => $user->id,
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    continue;
                }

                $vanInv = CashVanInventory::where('cash_van_id', $van->id)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($vanInv->quantity < $quantity) {
                    throw new InvalidArgumentException("ڕێژەی داواکراو لەناو سەیارەکە بەردەست نییە بۆ گەڕاندنەوە.");
                }

                $vanInv->decrement('quantity', $quantity);

                $product = Product::where('id', $productId)
                    ->where('supplier_company_id', $van->company_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $product->increment('current_stock', $quantity);

                CashVanStockTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ]);
            }

            return $transfer;
        });
    }

    /**
     * Record a direct van sale or mobile order taking.
     */
    public function recordSale(
        CashVan $van,
        int $retailerCompanyId,
        array $items,
        string $paymentMethod,
        float $paidAmount,
        float $discountAmount,
        User $driver,
        string $saleType = 'direct_sale',
        ?int $routeStopId = null,
        ?string $notes = null
    ): CashVanSale {
        return DB::transaction(function () use (
            $van, $retailerCompanyId, $items, $paymentMethod, $paidAmount, $discountAmount, $driver, $saleType, $routeStopId, $notes
        ) {
            $retailer = Company::where('id', $retailerCompanyId)
                ->where('business_type', 'retailer')
                ->firstOrFail();

            $subtotal = 0.0;
            $saleItemsData = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (float) $item['quantity'];

                if ($qty <= 0) {
                    continue;
                }

                $product = Product::where('id', $productId)
                    ->where('supplier_company_id', $van->company_id)
                    ->firstOrFail();

                // If direct sale, deduct from van stock
                if ($saleType === 'direct_sale') {
                    $vanInv = CashVanInventory::where('cash_van_id', $van->id)
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();

                    if (!$vanInv || $vanInv->quantity < $qty) {
                        $available = $vanInv ? $vanInv->quantity : 0;
                        throw new InvalidArgumentException(
                            "بڕی بەردەست لەناو سەیارەکە بەش ناکات بۆ: {$product->title}. بەردەست: {$available}, داواکراو: {$qty}"
                        );
                    }

                    $vanInv->decrement('quantity', $qty);
                }

                $unitPrice = (float) $product->wholesale_price;
                $lineSubtotal = round($qty * $unitPrice, 2);
                $subtotal += $lineSubtotal;

                $saleItemsData[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ];
            }

            if (empty($saleItemsData)) {
                throw new InvalidArgumentException("هیچ کاڵایەک هەڵنەبژێردراوە بۆ فرۆشتن.");
            }

            $discountAmount = max(0, min($discountAmount, $subtotal));
            $totalAmount = round(max(0, $subtotal - $discountAmount), 2);

            if ($paymentMethod === 'cash') {
                $finalPaidAmount = $totalAmount;
                $balanceDue = 0.0;
            } elseif ($paymentMethod === 'credit') {
                $finalPaidAmount = 0.0;
                $balanceDue = $totalAmount;
            } else { // split
                $finalPaidAmount = max(0, min($paidAmount, $totalAmount));
                $balanceDue = round(max(0, $totalAmount - $finalPaidAmount), 2);
            }

            // Check retailer credit limit if balance due > 0
            if ($balanceDue > 0) {
                $profile = RetailerProfile::firstOrCreate(['company_id' => $retailer->id]);
                if ($profile->credit_limit > 0) {
                    $currentDebt = (float) \App\Models\PurchaseOrder::where('retailer_company_id', $retailer->id)
                        ->where('status', '!=', 'cancelled')
                        ->sum('remaining_debt')
                        + (float) CashVanSale::where('retailer_company_id', $retailer->id)
                        ->where('status', '!=', 'cancelled')
                        ->sum('balance_due');

                    if (($currentDebt + $balanceDue) > (float)$profile->credit_limit) {
                        throw new InvalidArgumentException(
                            "فرۆشتن بە قەرز لە سنوری ڕێگەپێدراوی ئەم مارکێتە زیاترە (Credit Limit Exceeded). سنور: {$profile->credit_limit}, قەرزی ئێستا: {$currentDebt}"
                        );
                    }
                }
            }

            $saleNumber = 'VS-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            $sale = CashVanSale::create([
                'sale_number' => $saleNumber,
                'supplier_company_id' => $van->company_id,
                'cash_van_id' => $van->id,
                'retailer_company_id' => $retailer->id,
                'driver_user_id' => $driver->id,
                'route_stop_id' => $routeStopId,
                'sale_type' => $saleType,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'paid_amount' => $finalPaidAmount,
                'balance_due' => $balanceDue,
                'status' => 'completed',
                'notes' => $notes,
            ]);

            foreach ($saleItemsData as $itemData) {
                CashVanSaleItem::create(array_merge($itemData, ['sale_id' => $sale->id]));
            }

            if ($routeStopId) {
                CashVanRouteStop::where('id', $routeStopId)->update([
                    'status' => $saleType === 'order_taking' ? 'order_taken' : 'sale_completed',
                    'departure_time' => now(),
                ]);
            }

            app(\App\Services\B2bFinancialLedgerService::class)->recordCashVanSaleLedger($sale, $driver);

            return $sale;
        });
    }

    /**
     * Record a partial or full sales return.
     */
    public function recordReturn(
        CashVanSale $sale,
        array $items,
        string $reason,
        string $destination,
        User $driver
    ): CashVanReturn {
        return DB::transaction(function () use ($sale, $items, $reason, $destination, $driver) {
            $returnNumber = 'RET-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            $totalRefund = 0.0;
            $returnItemsData = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (float) $item['quantity'];

                if ($qty <= 0) {
                    continue;
                }

                $saleItem = CashVanSaleItem::where('sale_id', $sale->id)
                    ->where('product_id', $productId)
                    ->firstOrFail();

                // Compute already returned quantity for this item
                $alreadyReturned = CashVanReturnItem::whereHas('return', function ($q) use ($sale) {
                    $q->where('sale_id', $sale->id)->where('status', 'approved');
                })->where('product_id', $productId)->sum('quantity');

                $maxReturnable = $saleItem->quantity - $alreadyReturned;
                if ($qty > $maxReturnable) {
                    throw new InvalidArgumentException(
                        "ڕێژەی گەڕاندنەوە نابێت زیاتر بێت لە بڕی فرۆشراو. زۆرترین: {$maxReturnable}, داواکراو: {$qty}"
                    );
                }

                $lineRefund = round($qty * $saleItem->unit_price, 2);
                $totalRefund += $lineRefund;

                // Restock
                if ($destination === 'van') {
                    $vanInv = CashVanInventory::firstOrCreate(
                        ['cash_van_id' => $sale->cash_van_id, 'product_id' => $productId],
                        ['quantity' => 0, 'reserved_quantity' => 0]
                    );
                    $vanInv->increment('quantity', $qty);
                } else {
                    $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();
                    $product->increment('current_stock', $qty);
                }

                $returnItemsData[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_price' => $saleItem->unit_price,
                    'subtotal' => $lineRefund,
                ];
            }

            if (empty($returnItemsData)) {
                throw new InvalidArgumentException("هیچ کاڵایەک دیاری نەکراوە بۆ گەڕاندنەوە.");
            }

            // Adjust sale balance_due if sale had balance due
            if ($sale->balance_due > 0) {
                $debtReduction = min($totalRefund, (float) $sale->balance_due);
                $sale->decrement('balance_due', $debtReduction);
            }

            $returnRecord = CashVanReturn::create([
                'return_number' => $returnNumber,
                'sale_id' => $sale->id,
                'cash_van_id' => $sale->cash_van_id,
                'supplier_company_id' => $sale->supplier_company_id,
                'retailer_company_id' => $sale->retailer_company_id,
                'driver_user_id' => $driver->id,
                'total_refund_amount' => $totalRefund,
                'return_reason' => $reason,
                'restock_destination' => $destination,
                'status' => 'approved',
            ]);

            foreach ($returnItemsData as $itemData) {
                CashVanReturnItem::create(array_merge($itemData, ['return_id' => $returnRecord->id]));
            }

            app(\App\Services\B2bFinancialLedgerService::class)->recordCashVanReturnLedger($returnRecord, $driver);

            return $returnRecord;
        });
    }

    /**
     * Record operating expense during route.
     */
    public function recordExpense(
        CashVan $van,
        string $type,
        float $amount,
        User $driver,
        ?string $notes = null
    ): CashVanExpense {
        if ($amount <= 0) {
            throw new InvalidArgumentException("بڕی خەرجی دەبێت لە سفر زیاتر بێت.");
        }

        return CashVanExpense::create([
            'cash_van_id' => $van->id,
            'driver_user_id' => $driver->id,
            'supplier_company_id' => $van->company_id,
            'expense_type' => $type,
            'amount' => $amount,
            'notes' => $notes,
        ]);
    }

    /**
     * Perform daily cash & shift reconciliation.
     */
    public function reconcileShift(
        CashVan $van,
        User $driver,
        float $openingCash,
        float $actualCashHanded,
        ?string $notes = null
    ): CashVanReconciliation {
        return DB::transaction(function () use ($van, $driver, $openingCash, $actualCashHanded, $notes) {
            $today = now()->toDateString();

            $cashSales = (float) CashVanSale::where('cash_van_id', $van->id)
                ->whereDate('created_at', $today)
                ->where('status', 'completed')
                ->sum('paid_amount');

            $creditSales = (float) CashVanSale::where('cash_van_id', $van->id)
                ->whereDate('created_at', $today)
                ->where('status', 'completed')
                ->sum('balance_due');

            $expenses = (float) CashVanExpense::where('cash_van_id', $van->id)
                ->whereDate('created_at', $today)
                ->sum('amount');

            $expectedCash = round($openingCash + $cashSales - $expenses, 2);
            $discrepancy = round($actualCashHanded - $expectedCash, 2);

            $reconciliationNumber = 'REC-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            return CashVanReconciliation::create([
                'reconciliation_number' => $reconciliationNumber,
                'cash_van_id' => $van->id,
                'driver_user_id' => $driver->id,
                'supplier_company_id' => $van->company_id,
                'shift_date' => $today,
                'opening_cash' => $openingCash,
                'total_cash_sales' => $cashSales,
                'total_credit_sales' => $creditSales,
                'total_expenses' => $expenses,
                'expected_cash' => $expectedCash,
                'actual_cash_handed' => $actualCashHanded,
                'cash_discrepancy' => $discrepancy,
                'status' => 'submitted',
                'notes' => $notes,
            ]);
        });
    }
}
