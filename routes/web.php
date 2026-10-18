<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketLinkController;
use App\Http\Controllers\CashVanController;
use App\Http\Controllers\Auth\LoginController;

// Public Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/csrf-token', fn() => response()->json(['token' => csrf_token()]))->name('csrf.token');

Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['ku', 'ar', 'en'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('lang.switch');

// Protected Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [MarketLinkController::class, 'index'])->name('marketlink.index');
    Route::get('/retailer/barcode/search', [MarketLinkController::class, 'barcodeSearch'])->name('retailer.barcode.search');

    // Platform Operator / Admin
    Route::post('/operator/verifications/{id}/approve', [MarketLinkController::class, 'approveVerification'])->name('operator.verify.approve');
    Route::post('/operator/companies', [MarketLinkController::class, 'storeCompany'])->name('operator.company.store');

    // User & Team Management (RBAC & Tenancy)
    Route::post('/users', [MarketLinkController::class, 'storeUser'])->name('users.store');
    Route::post('/users/{id}/toggle-status', [MarketLinkController::class, 'toggleUserStatus'])->name('users.toggle_status');
    Route::post('/users/{id}/role', [MarketLinkController::class, 'updateUserRole'])->name('users.role.update');

    // Platform Operator Backup & Disaster Recovery
    Route::post('/operator/backups', [MarketLinkController::class, 'createBackup'])->name('operator.backups.create');
    Route::post('/operator/backups/restore', [MarketLinkController::class, 'restoreBackup'])->name('operator.backups.restore');
    Route::get('/operator/backups/{filename}/download', [MarketLinkController::class, 'downloadBackup'])->name('operator.backups.download');
    Route::post('/operator/backups/delete', [MarketLinkController::class, 'deleteBackup'])->name('operator.backups.delete');

    // Supplier
    Route::post('/supplier/products', [MarketLinkController::class, 'storeProduct'])->name('supplier.product.store');
    Route::post('/supplier/products/{id}/stock', [MarketLinkController::class, 'updateProductStock'])->name('supplier.product.stock');

    // Retailer
    Route::post('/retailer/orders', [MarketLinkController::class, 'createPurchaseOrder'])->name('retailer.order.store');
    Route::post('/retailer/demands', [MarketLinkController::class, 'storeDemand'])->name('retailer.demand.store');
    Route::post('/retailer/cart/checkout', [MarketLinkController::class, 'multiSupplierCheckout'])->name('retailer.cart.checkout');

    // Orders Lifecycle & Invoices & Debts & Audit Trail
    Route::post('/orders/{id}/status', [MarketLinkController::class, 'updateOrderStatus'])->name('orders.status.update');
    Route::post('/orders/{id}/approve', [MarketLinkController::class, 'orderApprove'])->name('orders.approve');
    Route::post('/orders/{id}/process', [MarketLinkController::class, 'orderProcess'])->name('orders.process');
    Route::post('/orders/{id}/dispatch', [MarketLinkController::class, 'dispatchOrder'])->name('orders.dispatch');
    Route::post('/orders/{id}/receive', [MarketLinkController::class, 'receiveOrder'])->name('orders.receive');
    Route::post('/orders/{id}/deliver-verify', [MarketLinkController::class, 'orderDeliverVerify'])->name('orders.deliver_verify');
    Route::post('/orders/{id}/complete-settle', [MarketLinkController::class, 'orderCompleteSettle'])->name('orders.complete_settle');
    Route::get('/orders/{id}/complete-settle', fn() => redirect()->route('marketlink.index', ['role' => 'supplier']));
    Route::get('/orders/{id}/approve', fn() => redirect()->route('marketlink.index', ['role' => 'supplier']));
    Route::get('/orders/{id}/process', fn() => redirect()->route('marketlink.index', ['role' => 'supplier']));
    Route::get('/orders/{id}/dispatch', fn() => redirect()->route('marketlink.index', ['role' => 'supplier']));
    Route::get('/orders/{id}/receive', fn() => redirect()->route('marketlink.index', ['role' => 'retailer']));
    Route::get('/orders/{id}/deliver-verify', fn() => redirect()->route('marketlink.index', ['role' => 'driver']));
    Route::post('/orders/{id}/cancel', [MarketLinkController::class, 'orderCancel'])->name('orders.cancel');
    Route::get('/orders/{id}/audit-logs', [MarketLinkController::class, 'getOrderAuditLogs'])->name('orders.audit_logs');
    Route::post('/orders/{id}/pay-debt', [MarketLinkController::class, 'settleDebt'])->name('orders.pay_debt');
    Route::get('/orders/{id}/invoice', [MarketLinkController::class, 'showInvoice'])->name('orders.invoice');

    // B2B Real-time Communication & Notifications
    Route::post('/b2b/messages', [MarketLinkController::class, 'sendMessage'])->name('messages.store');
    Route::post('/b2b/proposals', [MarketLinkController::class, 'sendPriceProposal'])->name('messages.proposal');
    Route::get('/b2b/notifications/check', [MarketLinkController::class, 'checkNotifications'])->name('notifications.check');
    Route::post('/b2b/notifications/mark-read', [MarketLinkController::class, 'markNotificationsRead'])->name('notifications.mark_read');

    // Financial Reporting & Account Statements (کەشف حیساب)
    Route::get('/reports/orders/export', [MarketLinkController::class, 'exportOrdersReport'])->name('reports.orders.export');
    Route::get('/reports/statement/{companyId}/export', [MarketLinkController::class, 'exportStatementReport'])->name('reports.statement.export');

    // Advanced B2B Financial Ledger & Vouchers
    Route::post('/financial/vouchers', [\App\Http\Controllers\FinancialLedgerController::class, 'storeReceiptVoucher'])->name('financial.vouchers.store');
    Route::post('/financial/vouchers/{id}/verify', [\App\Http\Controllers\FinancialLedgerController::class, 'verifyReceiptVoucher'])->name('financial.voucher.verify');
    Route::post('/financial/vouchers/{id}/reverse', [\App\Http\Controllers\FinancialLedgerController::class, 'reverseReceiptVoucher'])->name('financial.voucher.reverse');
    Route::get('/financial/vouchers/{id}/print', [\App\Http\Controllers\FinancialLedgerController::class, 'printVoucher'])->name('financial.voucher.print');
    Route::get('/financial/statement/{retailerId}', [\App\Http\Controllers\FinancialLedgerController::class, 'showStatement'])->name('financial.statement.show');
    Route::post('/financial/clearance', [\App\Http\Controllers\FinancialLedgerController::class, 'storeCashClearance'])->name('financial.clearance.store');
    Route::post('/financial/opening-balance', [\App\Http\Controllers\FinancialLedgerController::class, 'storeOpeningBalance'])->name('financial.opening_balance.store');
    Route::post('/orders/{id}/installments', [\App\Http\Controllers\FinancialLedgerController::class, 'createInstallments'])->name('orders.installments.store');
    Route::post('/installments/{id}/pay', [\App\Http\Controllers\FinancialLedgerController::class, 'payInstallment'])->name('installments.pay');
    Route::get('/financial/integrity/{supplierId}/{retailerId}', [\App\Http\Controllers\FinancialLedgerController::class, 'checkIntegrity'])->name('financial.integrity.check');

    // Cash Van Module Routes
    Route::post('/cash-van/vehicles', [CashVanController::class, 'storeVan'])->name('cash_van.vehicles.store');
    Route::post('/cash-van/{vanId}/load-stock', [CashVanController::class, 'loadStock'])->name('cash_van.stock.load');
    Route::post('/cash-van/{vanId}/return-stock', [CashVanController::class, 'returnStock'])->name('cash_van.stock.return');
    Route::post('/cash-van/{vanId}/sales', [CashVanController::class, 'storeSale'])->name('cash_van.sales.store');
    Route::post('/cash-van/sales/{saleId}/returns', [CashVanController::class, 'storeReturn'])->name('cash_van.returns.store');
    Route::post('/cash-van/{vanId}/expenses', [CashVanController::class, 'storeExpense'])->name('cash_van.expenses.store');
    Route::post('/cash-van/{vanId}/reconcile', [CashVanController::class, 'reconcileShift'])->name('cash_van.reconcile.store');
    Route::post('/cash-van/{vanId}/routes', [CashVanController::class, 'storeRoute'])->name('cash_van.routes.store');
    Route::post('/cash-van/stops/{stopId}/status', [CashVanController::class, 'updateRouteStopStatus'])->name('cash_van.stops.status');
    Route::get('/cash-van/{vanId}/export-sales', [CashVanController::class, 'exportSalesReport'])->name('cash_van.export.sales');
});

