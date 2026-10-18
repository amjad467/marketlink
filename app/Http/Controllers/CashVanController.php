<?php

namespace App\Http\Controllers;

use App\Models\CashVan;
use App\Models\CashVanInventory;
use App\Models\CashVanRoute;
use App\Models\CashVanRouteStop;
use App\Models\CashVanSale;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Services\CashVanService;
use App\Services\B2bTenantAuthorizationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashVanController extends Controller
{
    public function __construct(
        protected CashVanService $cashVanService,
        protected B2bTenantAuthorizationService $authService
    ) {}

    /**
     * Store a new Cash Van vehicle in the fleet.
     */
    public function storeVan(Request $request)
    {
        $validated = $request->validate([
            'vehicle_name' => 'required|string|max:100',
            'plate_number' => 'required|string|max:50',
            'model' => 'nullable|string|max:100',
            'driver_user_id' => 'nullable|exists:users,id',
            'operating_mode' => 'required|in:direct_sale,order_taking,hybrid',
        ]);

        $actor = $this->authService->getActorContext($request);
        if (!$actor['is_authenticated']) {
            abort(403, "تکایە سەرەتا بچۆ ژوورەوە.");
        }

        if ($actor['is_platform_admin']) {
            $companyId = $request->input('company_id') ?: session('active_supplier_id') ?: Company::where('business_type', 'supplier')->value('id');
        } else {
            if (!$actor['company_id'] || in_array($actor['role'], ['retailer', 'driver'])) {
                abort(403, "تەنها بەڕێوەبەری دابینکەر دەتوانێت کاش ڤان تۆمار بکات؛ شۆفێر مۆڵەتی تۆمارکردنی سەیارەی نییە.");
            }
            $companyId = (int)$actor['company_id'];
        }

        if (!$companyId) {
            abort(403, "تکایە دابینکەرێک دیاری بکە بۆ تۆمارکردنی کاش ڤان.");
        }

        $van = CashVan::create([
            'company_id' => $companyId,
            'vehicle_name' => $validated['vehicle_name'],
            'plate_number' => $validated['plate_number'],
            'model' => $validated['model'] ?? null,
            'driver_user_id' => $validated['driver_user_id'] ?? null,
            'operating_mode' => $validated['operating_mode'],
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', "سەیارەی کاش ڤان بە سەرکەوتوویی تۆمارکرا: {$van->vehicle_name}");
    }

    /**
     * Transfer stock from warehouse into vehicle.
     */
    public function loadStock(Request $request, int $vanId)
    {
        $van = CashVan::findOrFail($vanId);
        $this->authService->authorizeSupplierVanAccess($request, $van);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->cashVanService->loadStockToVan($van, $validated['items'], auth()->user(), $validated['notes'] ?? null);
            return redirect()->back()->with('success', "مەواد بە سەرکەوتوویی لە کۆگاوە بارکرا بۆ ناو سەیارەکە.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['load_error' => $e->getMessage()]);
        }
    }

    /**
     * Return unsold stock from vehicle to warehouse.
     */
    public function returnStock(Request $request, int $vanId)
    {
        $van = CashVan::findOrFail($vanId);
        $this->authService->authorizeSupplierVanAccess($request, $van);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->cashVanService->returnStockToWarehouse($van, $validated['items'], auth()->user(), $validated['notes'] ?? null);
            return redirect()->back()->with('success', "مەوادی ماوە بە سەرکەوتوویی گەڕێنرایەوە بۆ کۆگای سەرەکی.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['return_error' => $e->getMessage()]);
        }
    }

    /**
     * Record a direct sale or order taking from van to a retailer.
     */
    public function storeSale(Request $request, int $vanId)
    {
        $van = CashVan::findOrFail($vanId);
        $this->authService->authorizeDriverVanAccess($request, $van);

        $validated = $request->validate([
            'retailer_company_id' => 'required|exists:companies,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,credit,split',
            'paid_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'sale_type' => 'required|in:direct_sale,order_taking',
            'route_stop_id' => 'nullable|exists:cash_van_route_stops,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $sale = $this->cashVanService->recordSale(
                $van,
                (int) $validated['retailer_company_id'],
                $validated['items'],
                $validated['payment_method'],
                (float) ($validated['paid_amount'] ?? 0),
                (float) ($validated['discount_amount'] ?? 0),
                auth()->user(),
                $validated['sale_type'],
                isset($validated['route_stop_id']) ? (int)$validated['route_stop_id'] : null,
                $validated['notes'] ?? null
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "وەسڵی فرۆشتن بە سەرکەوتوویی بڕدرا.",
                    'sale' => $sale->load(['items.product', 'retailer']),
                ]);
            }

            return redirect()->back()->with('success', "وەسڵی فرۆشتنی ژمارە {$sale->sale_number} بە سەرکەوتوویی تۆمارکرا.");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withErrors(['sale_error' => $e->getMessage()]);
        }
    }

    /**
     * Record partial or full return of a sale.
     */
    public function storeReturn(Request $request, int $saleId)
    {
        $sale = CashVanSale::findOrFail($saleId);
        $this->authService->authorizeDriverVanAccess($request, $sale->van);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'return_reason' => 'required|string|max:255',
            'restock_destination' => 'required|in:van,warehouse',
        ]);

        try {
            $ret = $this->cashVanService->recordReturn(
                $sale,
                $validated['items'],
                $validated['return_reason'],
                $validated['restock_destination'],
                auth()->user()
            );

            return redirect()->back()->with('success', "گەڕاندنەوە بە سەرکەوتوویی تۆمارکرا: {$ret->return_number}");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['return_error' => $e->getMessage()]);
        }
    }

    /**
     * Record an operating expense during shift.
     */
    public function storeExpense(Request $request, int $vanId)
    {
        $van = CashVan::findOrFail($vanId);
        $this->authService->authorizeDriverVanAccess($request, $van);

        $validated = $request->validate([
            'expense_type' => 'required|in:fuel,maintenance,toll,food,other',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $this->cashVanService->recordExpense(
                $van,
                $validated['expense_type'],
                (float) $validated['amount'],
                auth()->user(),
                $validated['notes'] ?? null
            );

            return redirect()->back()->with('success', "خەرجی بە سەرکەوتوویی تۆمارکرا.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['expense_error' => $e->getMessage()]);
        }
    }

    /**
     * End-of-day shift and cash reconciliation.
     */
    public function reconcileShift(Request $request, int $vanId)
    {
        $van = CashVan::findOrFail($vanId);
        $this->authService->authorizeDriverVanAccess($request, $van);

        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'actual_cash_handed' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $rec = $this->cashVanService->reconcileShift(
                $van,
                auth()->user(),
                (float) $validated['opening_cash'],
                (float) $validated['actual_cash_handed'],
                $validated['notes'] ?? null
            );

            return redirect()->back()->with('success', "تەحاسوبی کۆتایی ڕۆژ تۆمارکرا: ژمارە {$rec->reconciliation_number}. جیاوازی کاش: {$rec->cash_discrepancy}");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['reconcile_error' => $e->getMessage()]);
        }
    }

    /**
     * Create a scheduled daily route for a Cash Van.
     */
    public function storeRoute(Request $request, int $vanId)
    {
        $van = CashVan::findOrFail($vanId);
        $this->authService->authorizeSupplierVanAccess($request, $van);

        $validated = $request->validate([
            'route_name' => 'required|string|max:100',
            'scheduled_date' => 'required|date',
            'retailer_ids' => 'required|array|min:1',
            'retailer_ids.*' => 'exists:companies,id',
        ]);

        $route = CashVanRoute::create([
            'supplier_company_id' => $van->company_id,
            'cash_van_id' => $van->id,
            'driver_user_id' => $van->driver_user_id,
            'route_name' => $validated['route_name'],
            'scheduled_date' => $validated['scheduled_date'],
            'status' => 'draft',
        ]);

        foreach ($validated['retailer_ids'] as $index => $retailerId) {
            CashVanRouteStop::create([
                'route_id' => $route->id,
                'retailer_company_id' => $retailerId,
                'stop_order' => $index + 1,
                'status' => 'pending',
            ]);
        }

        return redirect()->back()->with('success', "هێڵی گەڕان (Route) بۆ بەرواری {$validated['scheduled_date']} بە سەرکەوتوویی دروستکرا.");
    }

    /**
     * Update visit stop status.
     */
    public function updateRouteStopStatus(Request $request, int $stopId)
    {
        $stop = CashVanRouteStop::with('route.van')->findOrFail($stopId);
        $this->authService->authorizeDriverVanAccess($request, $stop->route->van);

        $validated = $request->validate([
            'status' => 'required|in:visited,customer_unavailable,skipped',
            'notes' => 'nullable|string|max:255',
        ]);

        $stop->update([
            'status' => $validated['status'],
            'departure_time' => now(),
            'notes' => $validated['notes'] ?? $stop->notes,
        ]);

        return redirect()->back()->with('success', "دۆخی سەردانەکە نوێکرایەوە.");
    }

    /**
     * Export Cash Van sales report to UTF-8 BOM CSV.
     */
    public function exportSalesReport(Request $request, int $vanId): StreamedResponse
    {
        $van = CashVan::findOrFail($vanId);
        $this->authService->authorizeSupplierVanAccess($request, $van);

        $sales = CashVanSale::with(['retailer', 'items.product', 'driver'])
            ->where('cash_van_id', $van->id)
            ->latest()
            ->get();

        $response = new StreamedResponse(function () use ($sales) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ژمارەی وەسڵ',
                'بەروار',
                'مارکێت / کڕیار',
                'شۆفێر / مەندوب',
                'جۆری فرۆشتن',
                'کۆی سەرەتایی',
                'داشکاندن',
                'کۆی گشتی',
                'شێوازی پارەدان',
                'کاشی وەرگیراو',
                'قەرزی ماوە',
            ]);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->sale_number,
                    $sale->created_at->format('Y-m-d H:i'),
                    $sale->retailer?->name ?? 'نادیار',
                    $sale->driver?->name ?? 'نادیار',
                    $sale->sale_type === 'direct_sale' ? 'فرۆشتنی ڕاستەوخۆ' : 'داواکاری مۆبایل',
                    number_format($sale->subtotal, 2),
                    number_format($sale->discount_amount, 2),
                    number_format($sale->total_amount, 2),
                    $sale->payment_method,
                    number_format($sale->paid_amount, 2),
                    number_format($sale->balance_due, 2),
                ]);
            }

            fclose($handle);
        });

        $filename = 'cash_van_sales_' . $van->plate_number . '_' . date('Y-m-d') . '.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }
}
