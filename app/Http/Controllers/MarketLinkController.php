<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use App\Models\BusinessVerification;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\B2bConversation;
use App\Models\B2bMessage;
use App\Models\MarketDemand;
use App\Models\OrderStatusLog;
use App\Models\RetailerProfile;
use App\Models\SupplierProfile;
use App\Models\ProductVolumeTier;
use App\Models\DeliveryProof;
use App\Models\DeliveryDiscrepancy;
use App\Models\SecurityAuditLog;
use App\Models\B2bNotification;
use App\Models\CustomerLedgerEntry;
use App\Models\PaymentReceiptVoucher;
use App\Services\B2bSecurityService;
use App\Services\B2bReportService;
use App\Services\B2bTenantAuthorizationService;
use App\Services\B2bBackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketLinkController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Enforce active status
        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'email' => 'ئەم هەژمارە لەلایەن بەڕێوەبەری پلاتفۆرمەوە ناچالاک کراوە.',
            ]);
        }

        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $supplierId = $request->query('supplier_id');
        $chatSupplierId = $request->query('chat_supplier_id');
        $chatRetailerId = $request->query('chat_retailer_id');
        $conversationId = $request->query('conversation_id');
        $filterStatus = $request->query('status');

        $allCompanies = Company::latest()->get();
        $suppliers = Company::where('business_type', 'supplier')->where('verification_status', 'verified')->withCount('products')->with(['products' => fn($q) => $q->where('is_available', true)])->get();
        $retailers = Company::where('business_type', 'retailer')->where('verification_status', 'verified')->get();
        $categories = Cache::remember('marketlink_categories_cache', 300, fn() => ProductCategory::withCount('products')->get());

        // 1. Role and Tenant Resolution based STRICTLY on authenticated user record
        $currentSupplier = null;
        $currentRetailer = null;

        if ($user->isPlatformAdmin()) {
            // Platform Admin has full platform audit rights across roles
            $role = $request->query('role', session('active_role', 'operator'));
            if (!in_array($role, ['operator', 'supplier', 'retailer', 'driver'])) {
                $role = 'operator';
            }
            session(['active_role' => $role]);

            $activeSupplierId = $request->query('active_supplier_id', session('active_supplier_id', $suppliers->first()?->id));
            if ($activeSupplierId && $suppliers->contains('id', $activeSupplierId)) {
                $currentSupplier = Company::find($activeSupplierId);
                session(['active_supplier_id' => $activeSupplierId]);
            } else {
                $currentSupplier = $suppliers->first();
                session(['active_supplier_id' => $currentSupplier?->id]);
            }

            $activeRetailerId = $request->query('active_retailer_id', session('active_retailer_id', $retailers->first()?->id));
            if ($activeRetailerId && $retailers->contains('id', $activeRetailerId)) {
                $currentRetailer = Company::find($activeRetailerId);
                session(['active_retailer_id' => $activeRetailerId]);
            } else {
                $currentRetailer = $retailers->first();
                session(['active_retailer_id' => $currentRetailer?->id]);
            }
        } elseif ($user->isDriver()) {
            $role = 'driver';
            session(['active_role' => 'driver']);

            if ($request->query('role') && $request->query('role') !== 'driver') {
                SecurityAuditLog::logViolation(
                    'role_tampering_attempt',
                    "هەوڵی نەشیاوی شۆفێر ({$user->id}) بۆ بەزاندنی ڕۆڵ بۆ: {$request->query('role')}",
                    $request,
                    $user->company_id,
                    $user->id,
                    'critical'
                );
            }

            if ($user->company && $user->company->business_type === 'supplier') {
                $currentSupplier = $user->company;
            } else {
                $assignedVan = \App\Models\CashVan::where('driver_user_id', $user->id)->with('company')->first();
                if ($assignedVan && $assignedVan->company) {
                    $currentSupplier = $assignedVan->company;
                }
            }
        } elseif ($user->company) {
            if ($user->company->business_type === 'supplier') {
                $role = 'supplier';
                $currentSupplier = $user->company;
                session(['active_role' => 'supplier', 'active_supplier_id' => $currentSupplier->id]);

                // Guard against role & tenant tampering
                if ($request->query('role') && $request->query('role') !== 'supplier') {
                    SecurityAuditLog::logViolation(
                        'role_tampering_attempt',
                        "هەوڵی نەشیاوی دابینکەر ({$user->id}) بۆ بەزاندنی دەسەڵاتی ڕۆڵ بۆ: {$request->query('role')}",
                        $request,
                        $user->company_id,
                        $user->id,
                        'critical'
                    );
                }
                if ($request->query('active_supplier_id') && (int)$request->query('active_supplier_id') !== (int)$currentSupplier->id) {
                    SecurityAuditLog::logViolation(
                        'tenant_tampering_attempt',
                        "هەوڵی نەشیاوی دابینکەر ({$user->id}) بۆ دەستگەیشتن بە داتای دابینکەری تر: {$request->query('active_supplier_id')}",
                        $request,
                        $user->company_id,
                        $user->id,
                        'critical'
                    );
                }
            } else {
                $role = 'retailer';
                $currentRetailer = $user->company;
                session(['active_role' => 'retailer', 'active_retailer_id' => $currentRetailer->id]);

                // Guard against role & tenant tampering
                if ($request->query('role') && $request->query('role') !== 'retailer') {
                    SecurityAuditLog::logViolation(
                        'role_tampering_attempt',
                        "هەوڵی نەشیاوی مارکێت ({$user->id}) بۆ بەزاندنی دەسەڵاتی ڕۆڵ بۆ: {$request->query('role')}",
                        $request,
                        $user->company_id,
                        $user->id,
                        'critical'
                    );
                }
                if ($request->query('active_retailer_id') && (int)$request->query('active_retailer_id') !== (int)$currentRetailer->id) {
                    SecurityAuditLog::logViolation(
                        'tenant_tampering_attempt',
                        "هەوڵی نەشیاوی مارکێت ({$user->id}) بۆ دەستگەیشتن بە داتای مارکێتی تر: {$request->query('active_retailer_id')}",
                        $request,
                        $user->company_id,
                        $user->id,
                        'critical'
                    );
                }
            }
        } else {
            $role = 'retailer';
            SecurityAuditLog::logViolation(
                'unassigned_user_access',
                "بەکارهێنەری بێ کۆمپانیا ({$user->id}) هەوڵی بەکارهێنانی سیستەمی دا.",
                $request,
                null,
                $user->id,
                'warning'
            );
        }

        $pendingVerifications = BusinessVerification::with('company')->where('review_status', 'submitted')->get();
        $securityAuditLogs = SecurityAuditLog::with(['user', 'company'])->latest()->take(25)->get();

        $allOrdersQuery = PurchaseOrder::with(['retailer', 'supplier', 'items', 'statusLogs'])->latest();
        if ($filterStatus && $filterStatus !== 'all') {
            $allOrdersQuery->where('status', $filterStatus);
        }
        $allPurchaseOrders = $allOrdersQuery->get();

        if ($role === 'driver') {
            $driverUser = auth()->user();
            $driverShipments = PurchaseOrder::with(['retailer', 'supplier', 'items', 'statusLogs'])
                ->whereIn('status', ['approved', 'processing', 'dispatched', 'delivered', 'completed'])
                ->when($filterStatus && $filterStatus !== 'all', fn($q) => $q->where('status', $filterStatus))
                ->where(function($q) use ($driverUser) {
                    if ($driverUser) {
                        $q->where('driver_id', $driverUser->id)
                          ->orWhere(function($sub) {
                              $sub->whereNull('driver_id')
                                  ->whereIn('status', ['approved', 'processing', 'dispatched']);
                          });
                    } else {
                        $q->whereIn('status', ['approved', 'processing', 'dispatched', 'delivered']);
                    }
                })
                ->orderByRaw("CASE 
                    WHEN status = 'dispatched' THEN 1 
                    WHEN status IN ('approved', 'processing') THEN 2 
                    ELSE 3 
                END")
                ->latest()
                ->get();
        } else {
            $driverShipments = $allPurchaseOrders;
        }

        // 2. Strict Warehouse Isolation for Supplier
        $supplierProducts = collect();
        $inboundPurchaseOrders = collect();
        $lowStockCount = 0;
        $totalWarehouseValue = 0;

        if ($currentSupplier) {
            $supplierProducts = Product::with(['category', 'volumeTiers'])
                ->where('supplier_company_id', $currentSupplier->id)
                ->get();
            $lowStockCount = $supplierProducts->where('current_stock', '<=', 20)->count();
            $totalWarehouseValue = $supplierProducts->sum(fn($p) => $p->current_stock * $p->base_wholesale_price);
            $inboundPoQuery = PurchaseOrder::with(['retailer', 'items', 'statusLogs'])
                ->where('supplier_company_id', $currentSupplier->id)
                ->latest();
            if ($filterStatus && $filterStatus !== 'all') {
                $inboundPoQuery->where('status', $filterStatus);
            }
            $inboundPurchaseOrders = $inboundPoQuery->get();
        }

        // Debts owed to this supplier by retailers (excluding cancelled orders)
        $debtsByRetailer = $inboundPurchaseOrders->where('status', '!=', 'cancelled')->where('remaining_debt', '>', 0)->groupBy('retailer_company_id');
        $totalSupplierReceivable = $inboundPurchaseOrders->where('status', '!=', 'cancelled')->sum('remaining_debt');

        // 3. Strict Outbound Orders & Market Demands for Retailer
        $outboundPurchaseOrders = collect();
        $myDemands = collect();

        if ($currentRetailer) {
            $outboundPoQuery = PurchaseOrder::with(['supplier', 'items', 'statusLogs'])
                ->where('retailer_company_id', $currentRetailer->id)
                ->latest();
            if ($filterStatus && $filterStatus !== 'all') {
                $outboundPoQuery->where('status', $filterStatus);
            }
            $outboundPurchaseOrders = $outboundPoQuery->get();
            $myDemands = MarketDemand::with('category')
                ->where('retailer_company_id', $currentRetailer->id)
                ->latest()
                ->get();
        }

        // Debts owed by this retailer to suppliers (excluding cancelled orders)
        $debtsBySupplier = $outboundPurchaseOrders->where('status', '!=', 'cancelled')->where('remaining_debt', '>', 0)->groupBy('supplier_company_id');
        $totalRetailerDebt = $outboundPurchaseOrders->where('status', '!=', 'cancelled')->sum('remaining_debt');
        $retailerCreditLimit = $currentRetailer ? ($currentRetailer->retailerProfile?->credit_limit ?? 5000.00) : 5000.00;

        // 4. Open Market Demands across platform (Visible to Suppliers to quote & bid)
        $allMarketDemands = MarketDemand::with(['retailer', 'category'])
            ->where('status', 'open')
            ->latest()
            ->get();

        // 5. Global Wholesale Catalog for Retailer Discovery (From ALL verified suppliers)
        $catalogQuery = Product::with(['supplier', 'category', 'volumeTiers'])
            ->where('is_available', true)
            ->whereHas('supplier', fn($sq) => $sq->where('verification_status', 'verified'));

        if ($search) {
            $catalogQuery->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }
        if ($categoryId) {
            $catalogQuery->where('category_id', $categoryId);
        }
        if ($supplierId) {
            $catalogQuery->where('supplier_company_id', $supplierId);
        }
        $catalogProducts = $catalogQuery->get();

        // 6. Reliable B2B Chat & Negotiation System
        $conversations = collect();
        $activeConversation = null;

        if ($role === 'retailer' && $currentRetailer) {
            $conversations = B2bConversation::with(['supplier', 'retailer', 'messages.sender'])
                ->where('retailer_company_id', $currentRetailer->id)
                ->latest('last_message_at')
                ->get();

            if ($chatSupplierId) {
                $activeConversation = B2bConversation::firstOrCreate([
                    'supplier_company_id' => $chatSupplierId,
                    'retailer_company_id' => $currentRetailer->id,
                ], [
                    'subject' => 'دانوستاندنی نرخ و پێداویستییەکان',
                    'last_message_at' => now(),
                ]);
            } elseif ($conversationId) {
                $activeConversation = $conversations->firstWhere('id', $conversationId) ?? $conversations->first();
            } else {
                $activeConversation = $conversations->first();
            }

            if ($activeConversation) {
                $activeConversation->messages()
                    ->where('sender_company_id', '!=', $currentRetailer->id)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
            }
        } elseif ($role === 'supplier' && $currentSupplier) {
            $conversations = B2bConversation::with(['supplier', 'retailer', 'messages.sender'])
                ->where('supplier_company_id', $currentSupplier->id)
                ->latest('last_message_at')
                ->get();

            if ($chatRetailerId) {
                $activeConversation = B2bConversation::firstOrCreate([
                    'supplier_company_id' => $currentSupplier->id,
                    'retailer_company_id' => $chatRetailerId,
                ], [
                    'subject' => 'دانوستاندنی نرخ و پێداویستییەکان',
                    'last_message_at' => now(),
                ]);
            } elseif ($conversationId) {
                $activeConversation = $conversations->firstWhere('id', $conversationId) ?? $conversations->first();
            } else {
                $activeConversation = $conversations->first();
            }

            if ($activeConversation) {
                $activeConversation->messages()
                    ->where('sender_company_id', '!=', $currentSupplier->id)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
            }
        }

        // 7. Dynamic In-App Notifications for the active tenant
        $unreadNotifications = collect();
        if ($role === 'supplier' && $currentSupplier) {
            $convIds = $conversations->pluck('id');
            $unreadMsgs = B2bMessage::with(['sender', 'conversation'])
                ->whereIn('conversation_id', $convIds)
                ->where('sender_company_id', '!=', $currentSupplier->id)
                ->where('is_read', false)
                ->latest()
                ->get();

            foreach ($unreadMsgs as $um) {
                $unreadNotifications->push([
                    'id' => 'msg_' . $um->id,
                    'type' => $um->message_type,
                    'title' => '💬 پەیامی نوێ لە ' . ($um->sender?->name ?? 'مارکێت'),
                    'body' => Str::limit($um->body, 55),
                    'url' => route('marketlink.index', ['role' => 'supplier', 'active_supplier_id' => $currentSupplier->id, 'conversation_id' => $um->conversation_id]) . '#chat-section',
                    'time' => $um->created_at->diffForHumans(),
                    'created_at' => $um->created_at,
                ]);
            }

            $newOrders = $inboundPurchaseOrders->whereIn('status', ['pending', 'issued']);
            foreach ($newOrders as $no) {
                $unreadNotifications->push([
                    'id' => 'po_' . $no->id,
                    'type' => 'order',
                    'title' => '📦 داواکاری نوێی کڕین: ' . $no->po_number,
                    'body' => ($no->retailer?->name ?? 'مارکێت') . ' — بە بڕی $' . number_format($no->total_amount, 2),
                    'url' => route('marketlink.index', ['role' => 'supplier', 'active_supplier_id' => $currentSupplier->id]) . '#orders-section',
                    'time' => $no->created_at->diffForHumans(),
                    'created_at' => $no->created_at,
                ]);
            }
        } elseif ($role === 'retailer' && $currentRetailer) {
            $convIds = $conversations->pluck('id');
            $unreadMsgs = B2bMessage::with(['sender', 'conversation'])
                ->whereIn('conversation_id', $convIds)
                ->where('sender_company_id', '!=', $currentRetailer->id)
                ->where('is_read', false)
                ->latest()
                ->get();

            foreach ($unreadMsgs as $um) {
                $typeTitle = $um->message_type === 'quote_proposal' ? '🏷️ ئۆفەری نرخی تایبەت' : '💬 پەیامی نوێ';
                $unreadNotifications->push([
                    'id' => 'msg_' . $um->id,
                    'type' => $um->message_type,
                    'title' => $typeTitle . ' لە ' . ($um->sender?->name ?? 'دابینکەر'),
                    'body' => Str::limit($um->body, 55),
                    'url' => route('marketlink.index', ['role' => 'retailer', 'active_retailer_id' => $currentRetailer->id, 'conversation_id' => $um->conversation_id]) . '#chat-section',
                    'time' => $um->created_at->diffForHumans(),
                    'created_at' => $um->created_at,
                ]);
            }

            $dispOrders = $outboundPurchaseOrders->whereIn('status', ['approved', 'processing', 'dispatched', 'delivered']);
            foreach ($dispOrders as $do) {
                $statusLabel = $do->status === 'approved' ? 'پەسەندکرا' : ($do->status === 'processing' ? 'ئامادە دەکرێت' : ($do->status === 'dispatched' ? 'بارکراوە لە ڕێگایە' : 'گەیشت'));
                $unreadNotifications->push([
                    'id' => 'po_disp_' . $do->id,
                    'type' => $do->status === 'dispatched' ? 'dispatch' : 'order',
                    'title' => '🚚 داواکاری ' . $do->po_number . ' ' . $statusLabel,
                    'body' => ($do->supplier?->name ?? 'دابینکەر') . ' — دۆخ: ' . $do->status,
                    'url' => route('marketlink.index', ['role' => 'retailer', 'active_retailer_id' => $currentRetailer->id]),
                    'time' => $do->updated_at->diffForHumans(),
                    'created_at' => $do->updated_at,
                ]);
            }
        } elseif ($role === 'operator') {
            foreach ($pendingVerifications as $pv) {
                $unreadNotifications->push([
                    'id' => 'verif_' . $pv->id,
                    'type' => 'verification',
                    'title' => '📑 داواکاری پەسەندکردنی کۆمپانیا',
                    'body' => ($pv->company?->name ?? 'کۆمپانیا') . ' مۆڵەتی نوێی ناردووە',
                    'url' => route('marketlink.index', ['role' => 'operator']),
                    'time' => $pv->created_at->diffForHumans(),
                    'created_at' => $pv->created_at,
                ]);
            }
        } elseif ($role === 'driver') {
            foreach ($driverShipments->where('status', 'dispatched') as $ds) {
                $unreadNotifications->push([
                    'id' => 'drv_' . $ds->id,
                    'type' => 'dispatch',
                    'title' => '🚚 باری نوێ بۆ گەیاندن: ' . $ds->po_number,
                    'body' => 'بۆ مارکێتی: ' . ($ds->retailer?->name ?? 'مارکێت'),
                    'url' => route('marketlink.index', ['role' => 'driver']),
                    'time' => $ds->updated_at->diffForHumans(),
                    'created_at' => $ds->updated_at,
                ]);
            }
        }
        if (auth()->check()) {
            $currentUserAuth = auth()->user();
            $dbNotifications = B2bNotification::where(function ($q) use ($currentUserAuth) {
                $q->where('user_id', $currentUserAuth->id)
                  ->orWhere(function ($sub) use ($currentUserAuth) {
                      $sub->whereNull('user_id');
                      if ($currentUserAuth->company_id) {
                          $sub->where('company_id', $currentUserAuth->company_id);
                      } elseif ($currentUserAuth->isPlatformAdmin()) {
                          $sub->whereNull('company_id');
                      }
                  });
            })
            ->where('is_read', false)
            ->latest()
            ->take(15)
            ->get();

            foreach ($dbNotifications as $dn) {
                $unreadNotifications->push([
                    'id' => 'db_' . $dn->id,
                    'db_id' => $dn->id,
                    'type' => $dn->type,
                    'title' => $dn->title,
                    'body' => $dn->message,
                    'url' => $dn->action_url ?: '#',
                    'time' => $dn->created_at->diffForHumans(),
                    'created_at' => $dn->created_at,
                ]);
            }
        }
        $unreadNotifications = $unreadNotifications->sortByDesc('created_at')->values();

        // 8. Province & Category Directories for Partners
        $filterProvince = $request->query('filter_province');
        $filterCategory = $request->query('filter_category');

        $directorySuppliers = Company::where('business_type', 'supplier')
            ->where('verification_status', 'verified')
            ->when($filterProvince, fn($q) => $q->where('province', $filterProvince))
            ->when($filterCategory, fn($q) => $q->where('business_category', $filterCategory))
            ->get();

        $directoryRetailers = Company::where('business_type', 'retailer')
            ->where('verification_status', 'verified')
            ->when($filterProvince, fn($q) => $q->where('province', $filterProvince))
            ->when($filterCategory, fn($q) => $q->where('business_category', $filterCategory))
            ->get();

        $allProvinces = ['هەولێر', 'سلێمانی', 'دهۆک', 'کەرکووک', 'هەڵەبجە'];
        $retailerCategories = ['سوپەرمارکێت', 'هایپەرمارکێت', 'مینی مارکێت', 'فرۆشگای خۆراک', 'شیرینی و چەرەزات', 'دەرمانخانە و کۆزمێتیک'];
        $supplierCategories = ['خۆراک و پێداویستی گشتی', 'خۆراک و خواردنەوەی سەرەکی', 'بەرهەمی شیری و ساردکەرەوە', 'پاککەرەوە و ماددەی ماڵ', 'کۆگای سەرەکی کۆمەڵ'];

        $companyUsersQuery = User::with('company')->latest();
        if ($role !== 'operator') {
            if ($role === 'supplier' && $currentSupplier) {
                $companyUsersQuery->where('company_id', $currentSupplier->id);
            } elseif ($role === 'retailer' && $currentRetailer) {
                $companyUsersQuery->where('company_id', $currentRetailer->id);
            } else {
                $companyUsersQuery->where('id', auth()->id());
            }
        }
        $companyUsers = $companyUsersQuery->get();

        $backups = app(B2bBackupService::class)->listBackups();

        // 9. Cash Van Fleet & Mobile Operations Data
        $cashVans = collect();
        $cashVanSales = collect();
        $cashVanRoutes = collect();
        $cashVanReconciliations = collect();
        $cashVanDrivers = collect();

        if ($currentSupplier) {
            $cashVansQuery = \App\Models\CashVan::with(['driver', 'inventories.product', 'routes.stops.retailer'])
                ->where('company_id', $currentSupplier->id);

            if ($user->isDriver()) {
                $myVans = (clone $cashVansQuery)->where('driver_user_id', $user->id)->get();
                $cashVans = $myVans->isNotEmpty() ? $myVans : (clone $cashVansQuery)->whereNull('driver_user_id')->get();
                if ($cashVans->isEmpty()) {
                    $cashVans = $cashVansQuery->get();
                }
            } else {
                $cashVans = $cashVansQuery->get();
            }
            $cashVanSales = \App\Models\CashVanSale::with(['retailer', 'items.product', 'van', 'driver'])
                ->where('supplier_company_id', $currentSupplier->id)
                ->latest()
                ->take(20)
                ->get();
            $cashVanRoutes = \App\Models\CashVanRoute::with(['van', 'driver', 'stops.retailer'])
                ->where('supplier_company_id', $currentSupplier->id)
                ->latest()
                ->take(10)
                ->get();
            $cashVanReconciliations = \App\Models\CashVanReconciliation::with(['van', 'driver'])
                ->where('supplier_company_id', $currentSupplier->id)
                ->latest()
                ->take(10)
                ->get();
            $cashVanDrivers = User::where('company_id', $currentSupplier->id)
                ->where('role', 'driver')
                ->get();
        }

        // 10. Unified Financial Ledger, Debt Aging & Payment Vouchers
        $financialLedgerService = app(\App\Services\B2bFinancialLedgerService::class);

        // Ensure historical transactions are synced for existing suppliers and retailers
        if ($currentSupplier) {
            foreach ($retailers as $r) {
                $financialLedgerService->syncHistoricalTransactions($currentSupplier->id, $r->id);
            }
        }

        // Recent payment receipt vouchers (سند قبض)
        $vouchersQuery = \App\Models\PaymentReceiptVoucher::with(['supplier', 'retailer', 'collector', 'purchaseOrder', 'cashVanSale'])->latest();
        if ($role === 'supplier' && $currentSupplier) {
            $vouchersQuery->where('supplier_company_id', $currentSupplier->id);
        } elseif ($role === 'retailer' && $currentRetailer) {
            $vouchersQuery->where('retailer_company_id', $currentRetailer->id);
        } elseif ($role === 'driver' && auth()->check()) {
            $vouchersQuery->where('collected_by_user_id', auth()->id());
        }
        $recentReceiptVouchers = $vouchersQuery->take(20)->get();

        // Customer Receivables Summary for the Financial Section (Strict Tenant Isolation)
        $customerFinancialSummaries = collect();
        $targetSupplierId = $currentSupplier?->id ?? ($role === 'operator' ? $suppliers->first()?->id : null);

        $summariesRetailers = match($role) {
            'supplier' => $retailers,
            'retailer' => $currentRetailer ? collect([$currentRetailer]) : collect(),
            'driver' => $retailers->whereIn('id', $driverShipments->pluck('retailer_company_id')->unique()),
            default => $retailers,
        };

        foreach ($summariesRetailers as $ret) {
            $ordersQuery = PurchaseOrder::where('retailer_company_id', $ret->id)->whereNotIn('status', ['cancelled']);
            if ($role === 'supplier' && $currentSupplier) {
                $ordersQuery->where('supplier_company_id', $currentSupplier->id);
            } elseif ($role === 'driver' && $currentSupplier) {
                $ordersQuery->where('supplier_company_id', $currentSupplier->id);
            }
            $debtOrders = (clone $ordersQuery)->where('remaining_debt', '>', 0)->get();
            $totalOrderDebt = (float)$debtOrders->sum('remaining_debt');
            $totalOrdersCount = (clone $ordersQuery)->count();
            
            // Van debt
            $vanDebt = (float)\App\Models\CashVanSale::where('retailer_company_id', $ret->id)
                ->when($currentSupplier, fn($q) => $q->where('supplier_company_id', $currentSupplier->id))
                ->whereNotIn('status', ['cancelled'])
                ->sum('balance_due');
            
            $totalCustomerDebt = round($totalOrderDebt + $vanDebt, 2);
            $runningBalance = $targetSupplierId ? $financialLedgerService->getCustomerRunningBalance($targetSupplierId, $ret->id) : $totalCustomerDebt;
            $effectiveDebt = $runningBalance > 0 ? $runningBalance : $totalCustomerDebt;
            $limit = (float)($ret->retailerProfile?->credit_limit ?? 5000.00);

            if ($effectiveDebt > 0 || $totalCustomerDebt > 0 || $totalOrdersCount > 0 || $role === 'operator') {
                $customerFinancialSummaries->push([
                    'retailer' => $ret,
                    'total_orders_count' => $totalOrdersCount,
                    'total_debt' => $effectiveDebt,
                    'running_balance' => $runningBalance,
                    'credit_limit' => $limit,
                    'is_over_limit' => $effectiveDebt > $limit,
                    'supplier_id' => $targetSupplierId,
                ]);
            }
        }

        // Driver custody vouchers and settlements
        $driverCustodyVouchers = \App\Models\PaymentReceiptVoucher::with(['supplier', 'retailer', 'collector'])
            ->where('custody_status', 'in_driver_custody')
            ->when($role === 'supplier' && $currentSupplier, fn($q) => $q->where('supplier_company_id', $currentSupplier->id))
            ->when($role === 'driver' && auth()->check(), fn($q) => $q->where('collected_by_user_id', auth()->id()))
            ->latest()
            ->get();
        $totalDriverCustodyCash = (float)$driverCustodyVouchers->sum('amount');

        $recentClearanceSettlements = \App\Models\CashClearanceSettlement::with(['supplier', 'driver', 'verifier'])
            ->when($role === 'supplier' && $currentSupplier, fn($q) => $q->where('supplier_company_id', $currentSupplier->id))
            ->latest()
            ->take(10)
            ->get();

        // 11. Multi-Company Debt Ledger (Strictly isolated by Company Tenant)
        $marketCompanyDebts = collect();
        $poPairsQuery = PurchaseOrder::whereNotIn('status', ['cancelled'])
            ->select('retailer_company_id', 'supplier_company_id')
            ->distinct();
        $ledgerPairsQuery = CustomerLedgerEntry::select('retailer_company_id', 'supplier_company_id')
            ->distinct();
        $vanPairsQuery = \App\Models\CashVanSale::whereNotIn('status', ['cancelled'])
            ->select('retailer_company_id', 'supplier_company_id')
            ->distinct();

        if ($role === 'supplier' && $currentSupplier) {
            $poPairsQuery->where('supplier_company_id', $currentSupplier->id);
            $ledgerPairsQuery->where('supplier_company_id', $currentSupplier->id);
            $vanPairsQuery->where('supplier_company_id', $currentSupplier->id);
        } elseif ($role === 'retailer' && $currentRetailer) {
            $poPairsQuery->where('retailer_company_id', $currentRetailer->id);
            $ledgerPairsQuery->where('retailer_company_id', $currentRetailer->id);
            $vanPairsQuery->where('retailer_company_id', $currentRetailer->id);
        } elseif ($role === 'driver') {
            $driverSupId = $currentSupplier?->id ?? $user->company_id;
            if ($driverSupId) {
                $poPairsQuery->where('supplier_company_id', $driverSupId);
                $ledgerPairsQuery->where('supplier_company_id', $driverSupId);
                $vanPairsQuery->where('supplier_company_id', $driverSupId);
            }
        }

        $poPairs = $poPairsQuery->get();
        $ledgerPairs = $ledgerPairsQuery->get();
        $vanPairs = $vanPairsQuery->get();

        $allUniquePairs = $poPairs->concat($ledgerPairs)->concat($vanPairs)->unique(function ($item) {
            return $item->retailer_company_id . '_' . $item->supplier_company_id;
        });

        foreach ($allUniquePairs as $pair) {
            $rId = (int)$pair->retailer_company_id;
            $sId = (int)$pair->supplier_company_id;
            $ret = $retailers->firstWhere('id', $rId) ?? Company::find($rId);
            $sup = $suppliers->firstWhere('id', $sId) ?? Company::find($sId);
            if (!$ret || !$sup) continue;

            $runningBalance = $financialLedgerService->getCustomerRunningBalance($sId, $rId);
            $orderDebt = (float)PurchaseOrder::where('retailer_company_id', $rId)->where('supplier_company_id', $sId)->whereNotIn('status', ['cancelled'])->sum('remaining_debt');
            $effectiveDebt = $runningBalance > 0 ? $runningBalance : $orderDebt;

            $ordersCount = PurchaseOrder::where('retailer_company_id', $rId)->where('supplier_company_id', $sId)->whereNotIn('status', ['cancelled'])->count();
            $unpaidOrders = PurchaseOrder::where('retailer_company_id', $rId)->where('supplier_company_id', $sId)->where('remaining_debt', '>', 0)->whereNotIn('status', ['cancelled'])->count();
            $limit = (float)($ret->retailerProfile?->credit_limit ?? 5000.00);

            $marketCompanyDebts->push([
                'retailer_id' => $rId,
                'supplier_id' => $sId,
                'retailer' => $ret,
                'supplier' => $sup,
                'running_balance' => $runningBalance,
                'effective_debt' => $effectiveDebt,
                'total_orders' => $ordersCount,
                'unpaid_orders' => $unpaidOrders,
                'credit_limit' => $limit,
                'is_over_limit' => $effectiveDebt > $limit,
            ]);
        }
        $marketCompanyDebts = $marketCompanyDebts->sortByDesc('effective_debt')->values();

        // 12. Driver Specific Debt & Running Balances (بۆ شۆفێر تەنها بۆ مارکێتەکانی خۆی)
        foreach ($driverShipments as $ds) {
            $ds->retailer_running_debt = $financialLedgerService->getCustomerRunningBalance($ds->supplier_company_id, $ds->retailer_company_id);
        }

        $driverRetailerIds = $driverShipments->pluck('retailer_company_id')->unique();
        $driverMarketDebts = $marketCompanyDebts->filter(function($row) use ($driverRetailerIds) {
            return $driverRetailerIds->contains($row['retailer_id']);
        })->values();

        return view('marketlink', compact(
            'role',
            'allCompanies',
            'suppliers',
            'retailers',
            'currentSupplier',
            'currentRetailer',
            'pendingVerifications',
            'categories',
            'catalogProducts',
            'supplierProducts',
            'inboundPurchaseOrders',
            'outboundPurchaseOrders',
            'allPurchaseOrders',
            'lowStockCount',
            'totalWarehouseValue',
            'conversations',
            'activeConversation',
            'myDemands',
            'allMarketDemands',
            'debtsByRetailer',
            'totalSupplierReceivable',
            'debtsBySupplier',
            'totalRetailerDebt',
            'search',
            'categoryId',
            'supplierId',
            'unreadNotifications',
            'filterProvince',
            'filterCategory',
            'directorySuppliers',
            'directoryRetailers',
            'allProvinces',
            'retailerCategories',
            'supplierCategories',
            'driverShipments',
            'retailerCreditLimit',
            'filterStatus',
            'securityAuditLogs',
            'companyUsers',
            'backups',
            'cashVans',
            'cashVanSales',
            'cashVanRoutes',
            'cashVanReconciliations',
            'cashVanDrivers',
            'recentReceiptVouchers',
            'customerFinancialSummaries',
            'driverCustodyVouchers',
            'totalDriverCustodyCash',
            'recentClearanceSettlements',
            'marketCompanyDebts',
            'driverMarketDebts'
        ));
    }

    public function storeCompany(Request $request)
    {
        app(B2bTenantAuthorizationService::class)->authorizePlatformAdmin($request);

        $batchRecords = $request->input('batch_records') ?: $request->input('records');
        if (is_string($batchRecords)) {
            $batchRecords = json_decode($batchRecords, true);
        }

        if (is_array($batchRecords) && count($batchRecords) > 0) {
            $createdCount = 0;
            $createdNames = [];

            DB::transaction(function () use ($batchRecords, &$createdCount, &$createdNames) {
                foreach ($batchRecords as $record) {
                    $name = trim($record['name'] ?? '');
                    if (empty($name)) continue;

                    $businessType = in_array($record['business_type'] ?? '', ['supplier', 'retailer']) ? $record['business_type'] : 'retailer';

                    // Ensure unique trade license
                    $license = trim($record['trade_license_number'] ?? '');
                    if (empty($license) || Company::where('trade_license_number', $license)->exists()) {
                        $license = 'TL-' . strtoupper(substr($businessType, 0, 3)) . '-' . date('Y') . '-' . rand(1000, 9999) . '-' . rand(10, 99);
                    }

                    $city = trim($record['city'] ?? 'هەولێر') ?: 'هەولێر';
                    $province = trim($record['province'] ?? $city) ?: $city;
                    $address = trim($record['address_line'] ?? $city) ?: $city;
                    $phone = trim($record['primary_phone'] ?? '+9647500000000') ?: '+9647500000000';
                    $email = trim($record['primary_email'] ?? '') ?: ('info_' . uniqid() . '@marketlink.krd');
                    $latitude = (isset($record['latitude']) && is_numeric($record['latitude'])) ? (float)$record['latitude'] : null;
                    $longitude = (isset($record['longitude']) && is_numeric($record['longitude'])) ? (float)$record['longitude'] : null;
                    $mapsUrl = !empty($record['google_maps_url']) ? trim($record['google_maps_url']) : null;

                    $company = Company::create([
                        'name' => $name,
                        'business_type' => $businessType,
                        'trade_license_number' => $license,
                        'province' => $province,
                        'city' => $city,
                        'business_category' => $record['business_category'] ?? null,
                        'country' => $record['country'] ?? 'هەرێمی کوردستان',
                        'primary_phone' => $phone,
                        'primary_email' => $email,
                        'address_line' => $address,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'google_maps_url' => $mapsUrl,
                        'verification_status' => 'verified',
                        'account_status' => 'active',
                        'verified_at' => now(),
                    ]);

                    if ($company->business_type === 'supplier') {
                        DB::table('supplier_profiles')->insert([
                            'company_id' => $company->id,
                            'minimum_order_total' => 100.00,
                            'default_payment_terms' => 'Net 30 Days',
                            'lead_time_days' => 2,
                            'currency' => 'USD',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('retailer_profiles')->insert([
                            'company_id' => $company->id,
                            'store_count' => 1,
                            'default_delivery_address' => $company->address_line,
                            'preferred_payment_method' => 'Direct Invoice',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $createdCount++;
                    $createdNames[] = $company->name;
                }
            });

            if ($request->wantsJson() || $request->isJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$createdCount} کۆمپانیا و شوێن بە سەرکەوتوویی تۆمارکران!",
                    'count' => $createdCount,
                ]);
            }

            return redirect()->route('marketlink.index', ['role' => 'operator'])
                ->with('success', "{$createdCount} کۆمپانیا و شوێن بە سەرکەوتوویی تۆمارکران: " . implode('، ', array_slice($createdNames, 0, 5)) . (count($createdNames) > 5 ? '...' : ''));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'business_type' => 'required|in:supplier,retailer',
            'trade_license_number' => 'required|string|max:100|unique:companies,trade_license_number',
            'province' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'business_category' => 'nullable|string|max:150',
            'country' => 'required|string|max:100',
            'primary_phone' => 'required|string|max:50',
            'primary_email' => 'required|email|max:150',
            'address_line' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_url' => 'nullable|string|max:1000',
        ]);

        $company = Company::create([
            'name' => $validated['name'],
            'business_type' => $validated['business_type'],
            'trade_license_number' => $validated['trade_license_number'],
            'province' => $validated['province'] ?? $validated['city'],
            'city' => $validated['city'],
            'business_category' => $validated['business_category'] ?? null,
            'country' => $validated['country'],
            'primary_phone' => $validated['primary_phone'],
            'primary_email' => $validated['primary_email'],
            'address_line' => $validated['address_line'] ?? $validated['city'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'google_maps_url' => $validated['google_maps_url'] ?? null,
            'verification_status' => 'verified',
            'account_status' => 'active',
            'verified_at' => now(),
        ]);

        if ($company->business_type === 'supplier') {
            DB::table('supplier_profiles')->insert([
                'company_id' => $company->id,
                'minimum_order_total' => 100.00,
                'default_payment_terms' => 'Net 30 Days',
                'lead_time_days' => 2,
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('retailer_profiles')->insert([
                'company_id' => $company->id,
                'store_count' => 1,
                'default_delivery_address' => $company->address_line,
                'preferred_payment_method' => 'Direct Invoice',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $typeLabel = $company->business_type === 'supplier' ? 'دابینکەر' : 'مارکێت';
        if ($request->wantsJson() || $request->isJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "کۆمپانیای نوێ '{$company->name}' وەک {$typeLabel} بە سەرکەوتوویی تۆمارکرا!",
            ]);
        }

        return redirect()->route('marketlink.index', ['role' => 'operator'])
            ->with('success', "کۆمپانیای نوێ '{$company->name}' وەک {$typeLabel} بە سەرکەوتوویی تۆمارکرا و پشتڕاستکرایەوە!");
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:b2b_conversations,id',
            'sender_company_id' => 'required|exists:companies,id',
            'body' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:10240',
            'voice' => 'nullable|file|max:25600',
        ]);

        if (empty($validated['body']) && !$request->hasFile('image') && !$request->hasFile('voice')) {
            return back()->with('error', 'تکایە دەق، وێنە یان فۆیسێک بنێرە.');
        }

        $conversation = B2bConversation::findOrFail($validated['conversation_id']);
        app(B2bTenantAuthorizationService::class)->authorizeConversationAccess($request, $conversation, (int)$validated['sender_company_id']);

        $attachmentUrl = null;
        $messageType = 'text';

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('chat_attachments/images', 'public');
            $attachmentUrl = '/storage/' . $path;
            $messageType = 'image';
        } elseif ($request->hasFile('voice')) {
            $file = $request->file('voice');
            $ext = $file->getClientOriginalExtension() ?: 'webm';
            $filename = 'voice_' . time() . '_' . uniqid() . '.' . $ext;
            $path = $file->storeAs('chat_attachments/voice', $filename, 'public');
            $attachmentUrl = '/storage/' . $path;
            $messageType = 'voice';
        }

        $bodyText = $validated['body'] ?? null;
        if (empty($bodyText)) {
            if ($messageType === 'image') {
                $bodyText = '📷 وێنەی هاوپێچکراو';
            } elseif ($messageType === 'voice') {
                $bodyText = '🎙️ پەیامی دەنگی (فۆیس)';
            }
        }

        B2bMessage::create([
            'conversation_id' => $validated['conversation_id'],
            'sender_company_id' => $validated['sender_company_id'],
            'message_type' => $messageType,
            'body' => $bodyText,
            'attachment_url' => $attachmentUrl,
            'is_read' => false,
        ]);

        $conversation = B2bConversation::find($validated['conversation_id']);
        $conversation->update(['last_message_at' => now()]);

        return back()->with('success', 'پەیامەکەت بە سەرکەوتوویی نێردرا!');
    }

    public function sendPriceProposal(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'required|exists:b2b_conversations,id',
            'sender_company_id' => 'required|exists:companies,id',
            'product_id' => 'nullable|exists:products,id',
            'proposed_unit_price' => 'required|numeric|min:0.01',
            'min_quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $conversation = B2bConversation::findOrFail($validated['conversation_id']);
        app(B2bTenantAuthorizationService::class)->authorizeConversationAccess($request, $conversation, (int)$validated['sender_company_id']);

        $productTitle = 'کاڵای دیاریکراو';
        if (!empty($validated['product_id'])) {
            $product = Product::find($validated['product_id']);
            if ($product) {
                if ((int)$product->supplier_company_id !== (int)$validated['sender_company_id']) {
                    SecurityAuditLog::logViolation(
                        'idor_product_tampering',
                        'هەوڵی پێشکەشکردنی ئۆفەری نرخ بۆ کاڵای دابینکەرێکی تر.',
                        $request,
                        (int)$validated['sender_company_id'],
                        auth()->id(),
                        'critical'
                    );
                    abort(403, 'دەستگەیشتن ڕەتکرایەوە: ناتوانیت ئۆفەری نرخی کاڵای کۆمپانیای تر بنێریت.');
                }
                $productTitle = $product->title;
            }
        }

        $body = "🏷️ **ئۆفەری نرخی تایبەتی کۆمەڵ**: نرخی هەر یەکەیەک بە \${$validated['proposed_unit_price']} بۆ کەمترین بڕی {$validated['min_quantity']} دانە ({$productTitle}). " . ($validated['notes'] ?? '');

        B2bMessage::create([
            'conversation_id' => $validated['conversation_id'],
            'sender_company_id' => $validated['sender_company_id'],
            'message_type' => 'quote_proposal',
            'body' => $body,
            'reference_id' => $validated['product_id'] ?? null,
            'is_read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return back()->with('success', 'ئۆفەری نرخی تایبەت بۆ مارکێت ڕەوانەکرا!');
    }

    public function storeDemand(Request $request)
    {
        $validated = $request->validate([
            'retailer_company_id' => 'required|exists:companies,id',
            'category_id' => 'nullable|exists:product_categories,id',
            'title' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit' => 'required|string',
            'target_unit_price' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $actor = app(B2bTenantAuthorizationService::class)->getActorContext($request);
        if (!$actor['is_authenticated']) {
            abort(403, 'تکایە سەرەتا بچۆ ژوورەوە.');
        }

        if ($actor['user']?->isDriver()) {
            abort(403, 'دەستگەیشتن ڕەتکرایەوە: شۆفێر مۆڵەتی بڵاوکردنەوەی پێداویستی مارکێتی نییە.');
        }

        if (!$actor['is_platform_admin'] && (int)$actor['company_id'] !== (int)$validated['retailer_company_id']) {
            SecurityAuditLog::logViolation('unauthorized_tenant_access', 'هەوڵی بڵاوکردنەوەی داواکاری بە ناوی مارکێتێکی تر.', $request, (int)$actor['company_id'], $actor['user']?->id, 'critical');
            abort(403, 'دەستگەیشتن ڕەتکرایەوە: تەنها مارکێتی خۆت دەتوانێت ئەم داواکارییە بڵاوبکاتەوە.');
        }

        MarketDemand::create($validated);

        return redirect()->route('marketlink.index', ['role' => 'retailer'])
            ->with('success', 'داواکاری پێداویستی مارکێت بڵاوکرایەوە! دابینکەران دەتوانن بینەری بن و نرخی گونجاو پێشکەش بکەن.');
    }

    public function updateProductStock(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        app(B2bTenantAuthorizationService::class)->authorizeCompanyManagement($request, (int)$product->supplier_company_id);

        $validated = $request->validate([
            'current_stock' => 'required|integer|min:0',
        ]);

        $oldStock = $product->current_stock;
        $newStock = (int)$validated['current_stock'];
        $product->update([
            'current_stock' => $newStock,
            'is_available' => $newStock > 0,
        ]);

        if ($newStock <= 10) {
            B2bNotification::send(
                $product->supplier_company_id,
                '⚠️ ئاگاداری کەمی کۆگا',
                "کاڵای '{$product->title}' بڕەکەی لە کۆگادا کەم بووەتەوە بۆ ({$newStock}) دانە.",
                'stock_alert',
                route('marketlink.index', ['role' => 'supplier']),
                null,
                ['product_id' => $product->id, 'current_stock' => $newStock]
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'current_stock' => $newStock,
                'message' => 'بڕی کۆگا بە سەرکەوتوویی نوێکرایەوە.',
            ]);
        }

        return back()->with('success', "کۆگای کاڵای '{$product->title}' لە {$oldStock}ەوە نوێکرایەوە بۆ {$newStock} دانە.");
    }

    public function approveVerification(Request $request, $id)
    {
        app(B2bTenantAuthorizationService::class)->authorizePlatformAdmin($request);

        $verification = BusinessVerification::findOrFail($id);
        $verification->update([
            'review_status' => 'approved',
            'reviewed_at' => now(),
            'reviewer_comments' => 'Verified through government company registry.'
        ]);

        $verification->company->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        return redirect()->route('marketlink.index', ['role' => 'operator'])
            ->with('success', "کۆمپانیای '{$verification->company->name}' بە سەرکەوتوویی پەسەندکرا!");
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'supplier_company_id' => 'required|exists:companies,id',
            'category_id' => 'required|exists:product_categories,id',
            'sku' => 'required|string',
            'title' => 'required|string',
            'base_wholesale_price' => 'required|numeric',
            'minimum_order_quantity' => 'required|integer',
            'current_stock' => 'required|integer',
        ]);

        app(B2bTenantAuthorizationService::class)->authorizeCompanyManagement($request, (int)$validated['supplier_company_id']);

        Product::create($validated);

        return redirect()->route('marketlink.index', ['role' => 'supplier'])
            ->with('success', "کاڵای نوێ '{$validated['title']}' بە سەرکەوتوویی لە کۆگای دابینکەردا بڵاوکرایەوە!");
    }

    public function barcodeSearch(Request $request)
    {
        $actor = app(B2bTenantAuthorizationService::class)->getActorContext($request);
        if (!$actor['is_authenticated']) {
            abort(403, 'تکایە سەرەتا بچۆ ژوورەوە.');
        }

        $query = $request->query('query') ?: $request->query('barcode') ?: $request->query('code');
        if (empty($query)) {
            return response()->json(['found' => false, 'product' => null, 'products' => []]);
        }

        $isPlatformAdmin = $actor['is_platform_admin'];
        $userCompanyId = (int)$actor['company_id'];

        $products = Product::with(['supplier', 'category', 'volumeTiers'])
            ->where(function ($q) use ($query) {
                $q->where('barcode', $query)
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('title', 'like', "%{$query}%");
            })
            ->where(function ($q) use ($isPlatformAdmin, $userCompanyId) {
                if ($isPlatformAdmin) {
                    return;
                }
                $q->where(function ($sub) use ($userCompanyId) {
                    $sub->where('is_available', true)
                        ->whereHas('supplier', fn($sq) => $sq->where('verification_status', 'verified'));
                    if ($userCompanyId) {
                        $sub->orWhere('supplier_company_id', $userCompanyId);
                    }
                });
            })
            ->take(10)
            ->get()
            ->map(function ($p) use ($userCompanyId, $isPlatformAdmin) {
                $isOwnerSupplier = $isPlatformAdmin || ($userCompanyId && $userCompanyId === (int)$p->supplier_company_id);
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'sku' => $p->sku,
                    'barcode' => $p->barcode,
                    'unit_of_measure' => $p->unit_of_measure,
                    'base_wholesale_price' => (float)$p->base_wholesale_price,
                    'currency' => $p->currency,
                    'minimum_order_quantity' => (int)$p->minimum_order_quantity,
                    'current_stock' => $isOwnerSupplier ? (int)$p->current_stock : null,
                    'reserved_stock' => $isOwnerSupplier ? (int)$p->reserved_stock : null,
                    'available_stock' => $isOwnerSupplier ? max(0, (int)$p->current_stock - (int)$p->reserved_stock) : null,
                    'is_available' => (bool)$p->is_available && ($p->current_stock > 0),
                    'supplier_company_id' => $p->supplier_company_id,
                    'supplier_id' => $p->supplier_company_id,
                    'supplier_name' => $p->supplier?->name ?? 'دابینکەر',
                    'category_name' => $p->category?->name_ku ?? $p->category?->name_en ?? '',
                    'volume_tiers' => $p->volumeTiers->map(fn($t) => [
                        'min_quantity' => (int)$t->min_quantity,
                        'unit_price' => (float)$t->unit_price,
                    ]),
                ];
            });

        return response()->json([
            'found' => $products->isNotEmpty(),
            'product' => $products->first(),
            'products' => $products,
        ]);
    }

    public function multiSupplierCheckout(Request $request)
    {
        $cartInput = $request->input('cart_items') ?: $request->input('items');
        $items = is_string($cartInput) ? json_decode($cartInput, true) : $cartInput;

        if (!is_array($items) || empty($items)) {
            if ($request->wantsJson() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => 'سەبەتەی کڕین بەتاڵە!'], 422);
            }
            return back()->with('error', 'سەبەتەی کڕین بەتاڵە!');
        }

        $actor = app(B2bTenantAuthorizationService::class)->getActorContext($request);
        if (!$actor['is_authenticated']) {
            abort(403, 'تکایە سەرەتا بچۆ ژوورەوە.');
        }

        if ($actor['user']?->isDriver()) {
            abort(403, 'دەستگەیشتن ڕەتکرایەوە: شۆفێر مۆڵەتی کڕین لە سەبەتەی کڕینی نییە.');
        }

        if ($actor['is_platform_admin']) {
            $retailerId = $request->input('retailer_company_id') ?: Company::where('business_type', 'retailer')->first()?->id;
        } else {
            $retailerId = $actor['company_id'];
            if ($request->filled('retailer_company_id') && (int)$request->input('retailer_company_id') !== (int)$retailerId) {
                SecurityAuditLog::logViolation('unauthorized_tenant_access', 'هەوڵی ئەنجامدانی کڕین بە ناوی مارکێتێکی تر.', $request, (int)$retailerId, $actor['user']?->id, 'critical');
                abort(403, 'دەستگەیشتن ڕەتکرایەوە: ناتوانیت بە ناوی مارکێتێکی تر کڕین ئەنجام بدەیت.');
            }
        }
        $retailer = Company::findOrFail($retailerId);

        $paymentTerms = $request->input('payment_terms') ?: $request->input('payment_method') ?: 'cash_on_delivery';
        $deliveryAddress = $request->input('delivery_address') ?: ($retailer->address_line ?? 'ناونیشانی مارکێت');
        $notes = $request->input('notes') ?: null;

        $validated = [
            'retailer_company_id' => $retailerId,
            'payment_terms' => $paymentTerms,
            'delivery_address' => $deliveryAddress,
            'notes' => $notes,
        ];
        $productIds = collect($items)->pluck('product_id')->filter()->unique();
        $products = Product::with(['supplier', 'volumeTiers'])->whereIn('id', $productIds)->get()->keyBy('id');

        $groupedBySupplier = [];
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $qty = (int)($item['quantity'] ?? 0);
            if (!$productId || $qty <= 0 || !isset($products[$productId])) {
                continue;
            }
            $product = $products[$productId];

            // MOQ check
            if ($qty < $product->minimum_order_quantity) {
                if ($request->wantsJson() || $request->isJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "کەمترین بڕی داواکاری (MOQ) بۆ کاڵای '{$product->title}' بریتییە لە {$product->minimum_order_quantity} دانە. تکایە بڕەکە زیاد بکە."
                    ], 422);
                }
                return back()->with('error', "کەمترین بڕی داواکاری (MOQ) بۆ کاڵای '{$product->title}' بریتییە لە {$product->minimum_order_quantity} دانە. تکایە بڕەکە زیاد بکە.");
            }

            $groupedBySupplier[$product->supplier_company_id][] = [
                'product' => $product,
                'quantity' => $qty,
            ];
        }

        if (empty($groupedBySupplier)) {
            if ($request->wantsJson() || $request->isJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هیچ کاڵایەکی دروست لە سەبەتەکەدا نەدۆزرایەوە.'
                ], 422);
            }
            return back()->with('error', 'هیچ کاڵایەکی دروست لە سەبەتەکەدا نەدۆزرایەوە.');
        }

        $createdPOs = [];

        DB::transaction(function () use ($groupedBySupplier, $retailer, $validated, &$createdPOs) {
            foreach ($groupedBySupplier as $supplierId => $supplierItems) {
                $subtotal = 0.00;
                $lineItemsData = [];

                foreach ($supplierItems as $itemRow) {
                    $prod = $itemRow['product'];
                    $qty = $itemRow['quantity'];

                    // Volume Tier pricing
                    $unitPrice = (float)$prod->base_wholesale_price;
                    $matchedTier = $prod->volumeTiers
                        ->where('min_quantity', '<=', $qty)
                        ->sortByDesc('min_quantity')
                        ->first();
                    if ($matchedTier) {
                        $unitPrice = (float)$matchedTier->unit_price;
                    }

                    $lineTotal = round($unitPrice * $qty, 2);
                    $subtotal += $lineTotal;

                    $lineItemsData[] = [
                        'product_id' => $prod->id,
                        'product_title' => $prod->title,
                        'sku' => $prod->sku,
                        'quantity_ordered' => $qty,
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                    ];
                }

                $po = PurchaseOrder::create([
                    'po_number' => 'PO-' . date('Y') . '-' . rand(1000, 9999),
                    'retailer_company_id' => $retailer->id,
                    'supplier_company_id' => $supplierId,
                    'status' => 'pending',
                    'currency' => 'USD',
                    'subtotal_amount' => $subtotal,
                    'discount_amount' => 0.00,
                    'tax_amount' => 0.00,
                    'total_amount' => $subtotal,
                    'paid_amount' => 0.00,
                    'remaining_debt' => $subtotal,
                    'payment_status' => 'unpaid',
                    'payment_terms' => $validated['payment_terms'],
                    'credit_due_date' => (str_contains($validated['payment_terms'], '30') || $validated['payment_terms'] === 'credit') ? now()->addDays(30)->toDateString() : null,
                    'delivery_address' => $validated['delivery_address'] ?? ($retailer->address_line ?? 'ناونیشانی مارکێت'),
                    'delivery_latitude' => $retailer->latitude,
                    'delivery_longitude' => $retailer->longitude,
                    'stock_reserved' => false,
                ]);

                foreach ($lineItemsData as $lid) {
                    PurchaseOrderItem::create(array_merge($lid, ['purchase_order_id' => $po->id]));
                }

                // Initial audit trail log
                OrderStatusLog::create([
                    'purchase_order_id' => $po->id,
                    'from_status' => null,
                    'to_status' => 'pending',
                    'changed_by_name' => $retailer->name,
                    'role' => 'retailer',
                    'notes' => "داواکاری تۆمارکرا لەڕێگەی سەبەتەی یەکگرتوو (Multi-Supplier Cart). تێرم: {$validated['payment_terms']}.",
                ]);

                // Notify in chat
                $conv = B2bConversation::firstOrCreate([
                    'supplier_company_id' => $supplierId,
                    'retailer_company_id' => $retailer->id,
                ], [
                    'subject' => 'داواکاری نوێ: ' . $po->po_number,
                    'last_message_at' => now(),
                ]);

                B2bMessage::create([
                    'conversation_id' => $conv->id,
                    'sender_company_id' => $retailer->id,
                    'message_type' => 'po_notice',
                    'body' => "🛒 **داواکاری نوێی کڕین ({$po->po_number})**: مارکێتی {$retailer->name} داواکاری بە بڕی \${$subtotal} تۆمارکرد. شێوازی پارەدان: {$validated['payment_terms']}. دۆخ: Pending.",
                ]);
                $conv->update(['last_message_at' => now()]);

                $createdPOs[] = $po->po_number;
            }
        });

        $poCount = count($createdPOs);
        $poList = implode(', ', $createdPOs);

        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => "داواکارییەکان بە سەرکەوتوویی دابەشکران بۆ {$poCount} پسوولەی جیا بەپێی دابینکەرەکان ({$poList}) و بە دۆخی پەندینگ (Pending) تۆمارکران!",
                'orders' => array_map(fn($num) => ['po_number' => $num], $createdPOs),
            ]);
        }

        return redirect()->route('marketlink.index', ['role' => 'retailer'])
            ->with('success', "داواکارییەکان بە سەرکەوتوویی دابەشکران بۆ {$poCount} پسوولەی جیا بەپێی دابینکەرەکان ({$poList}) و بە دۆخی پەندینگ (Pending) تۆمارکران!");
    }

    public function createPurchaseOrder(Request $request)
    {
        $validated = $request->validate([
            'retailer_company_id' => 'required|exists:companies,id',
            'supplier_company_id' => 'required|exists:companies,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'agreed_price' => 'required|numeric',
            'payment_terms' => 'required|string',
        ]);

        $actor = app(B2bTenantAuthorizationService::class)->getActorContext($request);
        if (!$actor['is_authenticated']) {
            abort(403, 'تکایە سەرەتا بچۆ ژوورەوە.');
        }

        if ($actor['user']?->isDriver()) {
            abort(403, 'دەستگەیشتن ڕەتکرایەوە: شۆفێر مۆڵەتی دروستکردنی فەرمانی کڕینی نییە.');
        }

        if (!$actor['is_platform_admin'] && (int)$actor['company_id'] !== (int)$validated['retailer_company_id']) {
            SecurityAuditLog::logViolation('unauthorized_tenant_access', 'هەوڵی دروستکردنی داواکاری بە ناوی مارکێتێکی تر.', $request, (int)$actor['company_id'], $actor['user']?->id, 'critical');
            abort(403, 'دەستگەیشتن ڕەتکرایەوە: ناتوانیت بە ناوی مارکێتێکی تر داواکاری بنێریت.');
        }

        $product = Product::findOrFail($validated['product_id']);

        if ($validated['quantity'] < $product->minimum_order_quantity) {
            return back()->with('error', "کەمترین بڕی داواکاری (MOQ) بۆ ئەم کاڵایە {$product->minimum_order_quantity} دانەیە.");
        }

        $total = round($validated['quantity'] * $validated['agreed_price'], 2);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-' . date('Y') . '-' . rand(1000, 9999),
            'retailer_company_id' => $validated['retailer_company_id'],
            'supplier_company_id' => $validated['supplier_company_id'],
            'status' => 'pending',
            'currency' => 'USD',
            'subtotal_amount' => $total,
            'total_amount' => $total,
            'paid_amount' => 0.00,
            'remaining_debt' => $total,
            'payment_status' => 'unpaid',
            'payment_terms' => $validated['payment_terms'],
            'credit_due_date' => (str_contains($validated['payment_terms'], '30') || $validated['payment_terms'] === 'credit') ? now()->addDays(30)->toDateString() : null,
            'delivery_address' => 'Retailer Distribution Hub',
            'delivery_latitude' => Company::where('id', $validated['retailer_company_id'])->value('latitude'),
            'delivery_longitude' => Company::where('id', $validated['retailer_company_id'])->value('longitude'),
            'stock_reserved' => false,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'product_title' => $product->title,
            'sku' => $product->sku,
            'quantity_ordered' => $validated['quantity'],
            'unit_price' => $validated['agreed_price'],
            'total_price' => $total,
        ]);

        OrderStatusLog::create([
            'purchase_order_id' => $po->id,
            'from_status' => null,
            'to_status' => 'pending',
            'changed_by_name' => $po->retailer?->name ?? 'مارکێت',
            'role' => 'retailer',
            'notes' => 'داواکاری نوێ تۆمارکرا و چاوەڕوانی پەسەندکردنی دابینکەرە.',
        ]);

        return redirect()->route('marketlink.index', ['role' => 'retailer'])
            ->with('success', "فەرمانی کڕینی ڕاستەوخۆ {$po->po_number} بە سەرکەوتوویی لە دۆخی pending تۆمارکرا!");
    }

    public function orderApprove(Request $request, $id)
    {
        $po = PurchaseOrder::with(['items', 'retailer.retailerProfile'])->findOrFail($id);
        app(B2bTenantAuthorizationService::class)->authorizeOrderAccess($request, $po, 'approve');
        $fromStatus = $po->status;

        $retailer = $po->retailer;
        $creditLimit = $retailer?->retailerProfile ? (float)$retailer->retailerProfile->credit_limit : 5000.00;
        $currentDebt = (float)PurchaseOrder::where('retailer_company_id', $po->retailer_company_id)->sum('remaining_debt');

        $notes = "داواکاری پەسەندکرا و کاڵاکان لە کۆگا ڕاگیران (Reserved Stock).";
        if ($currentDebt > $creditLimit) {
            $notes .= " ⚠️ تێبینی: قەرزی مارکێت (\${$currentDebt}) لە سەقفی قەرز (\${$creditLimit}) زیاترە بەڵام بە دەستی پەسەندکرا.";
        }

        // Reserve Stock
        foreach ($po->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->increment('reserved_stock', $item->quantity_ordered);
            }
        }

        $po->update([
            'status' => 'approved',
            'stock_reserved' => true,
        ]);

        OrderStatusLog::create([
            'purchase_order_id' => $po->id,
            'from_status' => $fromStatus,
            'to_status' => 'approved',
            'changed_by_name' => $po->supplier?->name ?? 'دابینکەر',
            'role' => 'supplier',
            'notes' => $notes,
        ]);

        // Send chat alert
        $conv = B2bConversation::firstOrCreate([
            'supplier_company_id' => $po->supplier_company_id,
            'retailer_company_id' => $po->retailer_company_id,
        ]);
        B2bMessage::create([
            'conversation_id' => $conv->id,
            'sender_company_id' => $po->supplier_company_id,
            'message_type' => 'po_notice',
            'body' => "✅ **پەسەندکردنی داواکاری**: فەرمانی {$po->po_number} پەسەندکرا و کاڵاکانت لە کۆگا ڕاگیران (Reserved Stock).",
        ]);
        $conv->update(['last_message_at' => now()]);

        B2bNotification::send(
            $po->retailer_company_id,
            '✅ پەسەندکردنی داواکاری',
            "فەرمانی {$po->po_number} پەسەندکرا لەلایەن دابینکەرەوە.",
            'order_status',
            route('orders.invoice', $po->id),
            null,
            ['po_id' => $po->id]
        );

        return back()->with('success', "فەرمانی {$po->po_number} پەسەندکرا و بڕی کاڵاکان لە کۆگا ڕاگیرا (Reserved Stock)!");
    }

    public function orderProcess(Request $request, $id)
    {
        $po = PurchaseOrder::with(['items', 'retailer'])->findOrFail($id);
        app(B2bTenantAuthorizationService::class)->authorizeOrderAccess($request, $po, 'process');
        $fromStatus = $po->status;

        $adjustedItemId = $request->input('adjust_item_id');
        $adjustedQty = $request->input('adjusted_qty');
        $shortageNotes = '';

        if ($adjustedItemId && is_numeric($adjustedQty)) {
            $adjustedQty = max(0, (int)$adjustedQty);
            $item = $po->items->firstWhere('id', $adjustedItemId);
            if ($item && $item->quantity_ordered != $adjustedQty) {
                $diff = $item->quantity_ordered - $adjustedQty;
                $item->quantity_ordered = $adjustedQty;
                $item->total_price = round($adjustedQty * (float)$item->unit_price, 2);
                $item->save();

                // Adjust product reserved_stock
                $prod = Product::find($item->product_id);
                if ($prod && $diff > 0) {
                    $prod->decrement('reserved_stock', min($prod->reserved_stock, $diff));
                }

                // Recalculate PO total
                $newTotal = $po->items()->sum('total_price');
                $po->subtotal_amount = $newTotal;
                $po->total_amount = $newTotal;
                $po->remaining_debt = max(0, $newTotal - (float)$po->paid_amount);

                $shortageNotes = "بەهۆی کەمبوونی ستۆک لە کۆگا، بڕی '{$item->product_title}' دەستکاریکرا بۆ {$adjustedQty} دانە.";

                // Notify retailer in chat
                $conv = B2bConversation::firstOrCreate([
                    'supplier_company_id' => $po->supplier_company_id,
                    'retailer_company_id' => $po->retailer_company_id,
                ]);
                B2bMessage::create([
                    'conversation_id' => $conv->id,
                    'sender_company_id' => $po->supplier_company_id,
                    'message_type' => 'po_notice',
                    'body' => "⚠️ **ئاگاداری کەمبوونی کاڵا لە کۆگا**: لە فەرمانی {$po->po_number} بڕی کاڵای '{$item->product_title}' بۆ {$adjustedQty} کەمکرایەوە. کۆی گشتی نوێکراوەی پسوولە: \${$newTotal}.",
                ]);
                $conv->update(['last_message_at' => now()]);
            }
        }

        $po->update(['status' => 'processing']);

        OrderStatusLog::create([
            'purchase_order_id' => $po->id,
            'from_status' => $fromStatus,
            'to_status' => 'processing',
            'changed_by_name' => 'کارمەندی کۆگا',
            'role' => 'supplier',
            'notes' => 'لیستی ئامادەکردن (Pick List) دەرهێنرا؛ کاڵاکان ئامادەکران و پاکێج کران.' . ($shortageNotes ? " [{$shortageNotes}]" : ""),
        ]);

        return back()->with('success', "دۆخی داواکاری {$po->po_number} گۆڕدرا بۆ ئامادەکردن (Processing)! لیستی پاکێج ئامادەیە.");
    }

    public function dispatchOrder(Request $request, $id)
    {
        $po = PurchaseOrder::with(['retailer', 'supplier'])->findOrFail($id);
        app(B2bTenantAuthorizationService::class)->authorizeOrderAccess($request, $po, 'dispatch');
        $fromStatus = $po->status;
        $validated = $request->validate([
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:50',
            'vehicle_plate' => 'nullable|string|max:50',
            'supplier_notes' => 'nullable|string',
            'role' => 'nullable|string',
        ]);

        $role = $validated['role'] ?? ($request->input('role') ?: 'supplier');
        $isDriver = $role === 'driver';

        $waybillNumber = 'WB-' . date('Ymd') . '-' . rand(100, 999);
        $deliveryOtp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $driverName = !empty($validated['driver_name']) ? $validated['driver_name'] : ($po->driver_name ?: 'شۆفێری دابەشکردن');
        $driverPhone = !empty($validated['driver_phone']) ? $validated['driver_phone'] : ($po->driver_phone ?: '');
        $vehiclePlate = !empty($validated['vehicle_plate']) ? $validated['vehicle_plate'] : ($po->vehicle_plate ?: '');
        $notes = !empty($validated['supplier_notes']) ? $validated['supplier_notes'] : ($isDriver ? 'شۆفێر کاڵاکانی لە کۆگا بارکرد و بەرەو ناونیشانی مارکێت بەڕێکەوت.' : 'کاڵاکان ئامادەکران و بارکران بەرەو مارکێت.');

        $updateData = [
            'status' => 'dispatched',
            'waybill_number' => $waybillNumber,
            'delivery_otp' => $deliveryOtp,
            'dispatched_at' => now(),
            'driver_name' => $driverName,
            'driver_phone' => $driverPhone,
            'vehicle_plate' => $vehiclePlate,
            'supplier_notes' => $notes,
        ];
        if ($request->filled('driver_id')) {
            $updateData['driver_id'] = $request->input('driver_id');
        } elseif ($request->user()?->isDriver()) {
            $updateData['driver_id'] = $request->user()->id;
        }

        $po->update($updateData);

        $actorName = $isDriver ? $driverName : ($po->supplier?->name ?? 'دابینکەر');
        $logNotes = $isDriver 
            ? "شۆفێر کاڵاکانی لە کۆگا وەرگرت و لە کۆگا بەڕێکەوت بەرەو مارکێت. پسوولەی بارنامە: {$waybillNumber}. شۆفێر: {$driverName} ({$vehiclePlate}). کۆدی وەرگرتنی مارکێت (OTP): {$deliveryOtp}."
            : "پسوولەی بارکردنی {$waybillNumber} دەرکرا. شوفێر: {$driverName}. کۆدی وەرگرتنی مارکێت (OTP): {$deliveryOtp}.";

        OrderStatusLog::create([
            'purchase_order_id' => $po->id,
            'from_status' => $fromStatus,
            'to_status' => 'dispatched',
            'changed_by_name' => $actorName,
            'role' => $isDriver ? 'driver' : 'supplier',
            'notes' => $logNotes,
        ]);

        // Send notification message into chat
        $conv = B2bConversation::firstOrCreate([
            'supplier_company_id' => $po->supplier_company_id,
            'retailer_company_id' => $po->retailer_company_id,
        ], [
            'subject' => 'گەیاندنی فەرمانی ' . $po->po_number,
            'last_message_at' => now(),
        ]);

        $chatBody = $isDriver
            ? "🚚 **شۆفێر لە کۆگا بەڕێکەوت**: باری فەرمانی {$po->po_number} لە کۆگای دابینکەر وەرگیرا و بەڕێکەوت بەرەو مارکێتی {$po->retailer->name}.\nشۆفێر: {$driverName} ({$driverPhone}). تابلۆ: {$vehiclePlate}.\n📄 بارنامە: {$waybillNumber} | 🔑 کۆدی وەرگرتنی مارکێت (OTP): {$deliveryOtp}."
            : "🚚 **بارکردن و ناردنی کاڵاکان**: فەرمانی {$po->po_number} بە ژمارەی پسوولەی بارکردنی {$waybillNumber} ئامادەکرا و بەڕێکرا.\nشوفێر: {$driverName} ({$driverPhone}).\n🔑 کۆدی وەرگرتنی خۆتان (OTP): {$deliveryOtp}.";

        B2bMessage::create([
            'conversation_id' => $conv->id,
            'sender_company_id' => $po->supplier_company_id,
            'message_type' => 'po_notice',
            'body' => $chatBody,
        ]);
        $conv->update(['last_message_at' => now()]);

        B2bNotification::send(
            $po->retailer_company_id,
            '🚚 بارکردن و بەڕێکەوتن',
            "باری فەرمانی {$po->po_number} بەڕێکەوت بەرەو مارکێتەکەت. کۆدی وەرگرتن (OTP): {$deliveryOtp}",
            'order_status',
            route('orders.invoice', $po->id),
            null,
            ['po_id' => $po->id, 'waybill_number' => $waybillNumber]
        );

        $successMsg = $isDriver
            ? "بەڕێکەوتن لە کۆگا بە سەرکەوتوویی تۆمارکرا! باری فەرمانی {$po->po_number} ئێستا لە ڕێگایە بەرەو مارکێت. کۆدی وەرگرتن (OTP): {$deliveryOtp}."
            : "پسوولەی بارکردنی {$waybillNumber} دەرچوو و کاڵاکان بەڕێکران! کۆدی وەرگرتن (OTP): {$deliveryOtp}.";

        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'waybill_number' => $waybillNumber,
                'delivery_otp' => $deliveryOtp,
                'status' => 'dispatched',
                'navigation_url' => $po->navigation_url,
                'retailer_name' => $po->retailer?->name,
                'destination_lat' => $po->delivery_latitude ?? $po->retailer?->latitude,
                'destination_lng' => $po->delivery_longitude ?? $po->retailer?->longitude,
            ]);
        }

        return back()->with('success', $successMsg);
    }

    public function receiveOrder(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);
        app(B2bTenantAuthorizationService::class)->authorizeOrderAccess($request, $po, 'receive');
        $fromStatus = $po->status;
        $validated = $request->validate([
            'inspected_by' => 'required|string|max:100',
            'inspection_notes' => 'nullable|string',
            'paid_amount' => 'required|numeric|min:0|max:' . $po->total_amount,
            'payment_method' => 'required|string',
        ]);

        $idempotencyKey = $request->header('X-Idempotency-Key') ?: $request->input('idempotency_key');
        $financialPaymentService = app(\App\Services\FinancialPaymentService::class);
        $result = $financialPaymentService->processOrderReceptionPayment(
            $po,
            (float)$validated['paid_amount'],
            $validated['payment_method'],
            auth()->user(),
            $idempotencyKey,
            $validated['inspection_notes'] ?? null
        );

        $po = $result['purchase_order'];
        $paid = $result['paid_amount'];
        $remainingDebt = $result['remaining_debt'];

        $po->update([
            'inspected_by' => $validated['inspected_by'],
            'inspection_notes' => $validated['inspection_notes'] ?? 'کاڵاکان پشکنران و بە تەواوی وەرگیران.',
        ]);

        OrderStatusLog::create([
            'purchase_order_id' => $po->id,
            'from_status' => $fromStatus,
            'to_status' => 'delivered',
            'changed_by_name' => $validated['inspected_by'],
            'role' => 'retailer',
            'notes' => $validated['inspection_notes'] ?? 'کاڵاکان پشکنران و بە سەرکەوتوویی لە مارکێت وەرگیران.',
        ]);

        // Send confirmation message into chat
        $conv = B2bConversation::firstOrCreate([
            'supplier_company_id' => $po->supplier_company_id,
            'retailer_company_id' => $po->retailer_company_id,
        ]);

        B2bMessage::create([
            'conversation_id' => $conv->id,
            'sender_company_id' => $po->retailer_company_id,
            'message_type' => 'po_notice',
            'body' => "📦 **پشتڕاستکردنەوەی وەرگرتنی کاڵاکان**: مارکێتی {$po->retailer->name} کاڵاکانی فەرمانی {$po->po_number}ی پشکنی و وەریگرت.\n💰 بڕی پارەی دراو: \${$paid} | ⚠️ قەرزی ماوە: \${$remainingDebt} (شێوازی پارەدان: {$validated['payment_method']}).",
        ]);
        $conv->update(['last_message_at' => now()]);

        return back()->with('success', "وەرگرتنی کاڵاکان لەلایەن مارکێتەوە پشتڕاستکرایەوە! بڕی پارەی دراو: \${$paid}، قەرزی ماوە: \${$remainingDebt}.");
    }

    public function orderDeliverVerify(Request $request, $id)
    {
        try {
            $po = PurchaseOrder::with(['retailer', 'supplier', 'items'])->findOrFail($id);
            app(B2bTenantAuthorizationService::class)->authorizeOrderAccess($request, $po, 'deliver_verify');
            $fromStatus = $po->status;

            // Secure OTP verification using B2bSecurityService with brute-force lockout protection
            $securityService = app(B2bSecurityService::class);
            $expectedOtp = $po->delivery_otp ?: '123456';
            if ($request->filled('delivery_otp')) {
                $inputOtp = trim($request->input('delivery_otp'));
                $otpCheck = $securityService->verifyDeliveryOtp($po, $inputOtp);
                if (!$otpCheck['valid']) {
                    if ($request->wantsJson() || $request->isJson() || $request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => $otpCheck['message'],
                            'locked' => $otpCheck['locked'] ?? false,
                        ], 422);
                    }
                    return back()->with('error', $otpCheck['message']);
                }
            }

            $damageAmount = (float)$request->input('damage_amount', 0.00);
            $damageNotes = $request->input('damage_notes');
            $signature = $request->input('delivery_signature');
            $inspectedBy = $request->input('inspected_by', $po->retailer?->name ?? 'خاوەن مارکێت');
            $driverName = $request->input('driver_name', $po->driver_name ?? 'شۆفێری دابەشکردن');
            $cashCollected = (float)$request->input('cash_collected', 0.00);
            $lat = $request->filled('delivered_latitude') ? (float)$request->input('delivered_latitude') : null;
            $lng = $request->filled('delivered_longitude') ? (float)$request->input('delivered_longitude') : null;
            $isOffline = (bool)$request->input('synced_offline', false);

            $idempotencyKey = $request->header('X-Idempotency-Key') ?: $request->input('idempotency_key');
            $financialPaymentService = app(\App\Services\FinancialPaymentService::class);
            $payResult = $financialPaymentService->processDeliveryVerification(
                $po,
                $cashCollected,
                $damageAmount,
                $damageNotes,
                auth()->user(),
                $request->input('driver_id') ? (int)$request->input('driver_id') : ($po->driver_id ?? auth()->id()),
                $idempotencyKey
            );
            $po = $payResult['purchase_order'];

            $po->update([
                'delivery_otp' => $expectedOtp,
                'inspected_by' => $inspectedBy,
                'inspection_notes' => $request->input('inspection_notes', 'کاڵاکان پشکنران و بە فەرمی وەرگیران.'),
                'delivery_signature' => $signature,
            ]);

            // Save PoD DeliveryProof record
            $proofStatus = ($damageAmount > 0 || $request->filled('discrepancies')) ? 'partial_delivery' : 'full_delivery';
            $deliveryProof = DeliveryProof::create([
                'purchase_order_id' => $po->id,
                'driver_id' => $request->input('driver_id'),
                'driver_name' => $driverName,
                'received_by_name' => $inspectedBy,
                'signature_image_path' => $signature,
                'otp_code' => $expectedOtp,
                'delivered_latitude' => $lat,
                'delivered_longitude' => $lng,
                'cash_collected' => $cashCollected,
                'status' => $proofStatus,
                'delivered_at' => now(),
                'synced_offline' => $isOffline,
            ]);

            // Save line-item discrepancies if provided
            $rawDiscrepancies = $request->input('discrepancies');
            if (is_string($rawDiscrepancies)) {
                $rawDiscrepancies = json_decode($rawDiscrepancies, true);
            }
            if (is_array($rawDiscrepancies) && count($rawDiscrepancies) > 0) {
                foreach ($rawDiscrepancies as $disc) {
                    $itemId = $disc['item_id'] ?? null;
                    $orderedQty = (int)($disc['ordered_qty'] ?? 0);
                    $deliveredQty = (int)($disc['delivered_qty'] ?? 0);
                    $missingQty = max(0, $orderedQty - $deliveredQty);
                    $reason = in_array($disc['reason'] ?? '', ['damaged', 'missing', 'expired', 'rejected_by_store']) ? $disc['reason'] : 'damaged';

                    $poItem = $itemId ? PurchaseOrderItem::find($itemId) : null;
                    if ($poItem) {
                        $poItem->update([
                            'quantity_delivered' => $deliveredQty,
                        ]);
                    }

                    // Restock returned items to supplier warehouse (مەوادی گەڕاوەی بێ کێشە)
                    if ($reason === 'rejected_by_store' && $poItem && $poItem->product_id && $missingQty > 0) {
                        Product::where('id', $poItem->product_id)->increment('current_stock', $missingQty);
                    }

                    // Clean item-specific note
                    $reasonFa = match($reason) {
                        'damaged' => 'تەلەف و شکاو',
                        'missing' => 'کەم هاتووە',
                        'expired' => 'ئێکسپایەر',
                        'rejected_by_store' => 'گەڕاوەی مارکێت',
                        default => $reason
                    };
                    $itemNote = $disc['notes'] ?? "{$missingQty} دانە ({$reasonFa})";

                    DeliveryDiscrepancy::create([
                        'delivery_proof_id' => $deliveryProof->id,
                        'purchase_order_item_id' => $itemId,
                        'ordered_quantity' => $orderedQty,
                        'delivered_quantity' => $deliveredQty,
                        'reason' => $reason,
                        'evidence_photo_path' => $disc['photo_path'] ?? null,
                        'notes' => $itemNote,
                    ]);
                }
            } else {
                foreach ($po->items as $item) {
                    $item->update(['quantity_delivered' => $item->quantity_ordered]);
                }
            }

            // Ensure stock is deducted from warehouse based on actual delivered items
            $financialPaymentService->deductDeliveredStockFromWarehouse($po);

            $logNotes = "کاڵاکان بە سەلماندنی OTP ({$expectedOtp}) و پشکنین ڕادەستی مارکێت کران.";
            if ($damageAmount > 0) {
                $logNotes .= " داشکاندنی زیان/تێکچوون: \${$damageAmount} تۆمارکرا ({$damageNotes}).";
            }
            if ($cashCollected > 0) {
                $logNotes .= " پارەی نەختینەی وەرگیراو: \${$cashCollected}.";
            }
            if ($lat && $lng) {
                $logNotes .= " پشکنینی لۆکەیشن (GPS): {$lat}, {$lng}.";
            }

            OrderStatusLog::create([
                'purchase_order_id' => $po->id,
                'from_status' => $fromStatus,
                'to_status' => 'delivered',
                'changed_by_name' => $inspectedBy,
                'role' => 'driver',
                'notes' => $logNotes,
            ]);

            // Chat notification
            $conv = B2bConversation::firstOrCreate([
                'supplier_company_id' => $po->supplier_company_id,
                'retailer_company_id' => $po->retailer_company_id,
            ]);
            B2bMessage::create([
                'conversation_id' => $conv->id,
                'sender_company_id' => $po->retailer_company_id,
                'message_type' => 'po_notice',
                'body' => "📦 **پشتڕاستکردنەوەی گەیاندن**: فەرمانی {$po->po_number} بە کۆدی OTP و پشکنین ڕادەستی مارکێت کرا." . ($damageAmount > 0 ? " بڕی زیان: \${$damageAmount} کەمکرایەوە." : "") . ($cashCollected > 0 ? " کاش: \${$cashCollected} وەرگیرا." : ""),
            ]);
            $conv->update(['last_message_at' => now()]);

            if ($request->wantsJson() || $request->isJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "گەیاندن و ڕادەستکردنی فەرمانی {$po->po_number} بە سەرکەوتوویی پشتڕاستکرایەوە!",
                    'status' => 'delivered',
                    'proof_id' => $deliveryProof->id,
                ]);
            }

            return back()->with('success', "گەیاندن و ڕادەستکردنی فەرمانی {$po->po_number} بە سەرکەوتوویی پشتڕاستکرایەوە!");
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->isJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هەڵە لە ئەنجامدانی کردارەکە: ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'هەڵە: ' . $e->getMessage());
        }
    }

    public function orderCompleteSettle(Request $request, $id)
    {
        $po = PurchaseOrder::with(['retailer', 'supplier', 'items'])->findOrFail($id);
        app(B2bTenantAuthorizationService::class)->authorizeOrderAccess($request, $po, 'complete_settle');
        $fromStatus = $po->status;

        $financialPaymentService = app(\App\Services\FinancialPaymentService::class);

        // Ensure delivered stock is permanently deducted (idempotent)
        $financialPaymentService->deductDeliveredStockFromWarehouse($po);

        // Smart default: If order already has debt or is marked as credit/debt, preserve Credit Net 30 Days
        $isDebtOrder = ($po->remaining_debt > 0 && ($po->paid_amount == 0 || str_contains(strtolower((string)$po->payment_terms), 'credit') || $po->payment_method === 'debt'));
        $defaultMethod = $isDebtOrder ? 'Credit Net 30 Days' : ($po->payment_terms ?: 'COD Cash');
        $paymentMethod = $request->input('payment_method', $defaultMethod);
        $isCash = str_contains(strtolower($paymentMethod), 'cash') || str_contains(strtolower($paymentMethod), 'cod');

        $idempotencyKey = $request->header('X-Idempotency-Key') ?: $request->input('idempotency_key');
        $settleResult = $financialPaymentService->processOrderCompleteSettlement(
            $po,
            (float)$request->input('paid_amount', 0.00),
            $paymentMethod,
            auth()->user(),
            $idempotencyKey
        );
        $po = $settleResult['purchase_order'];
        $settleNote = $po->payment_status === 'paid'
            ? "پارەدانی کاش (COD) بە تەواوی وەرگیرا (\${$po->total_amount})."
            : "وەک قەرز بە وادەی ٣٠ ڕۆژ تۆمارکرا. بەرواری وادە: {$po->credit_due_date}. قەرزی ماوە: \${$po->remaining_debt}.";

        OrderStatusLog::create([
            'purchase_order_id' => $po->id,
            'from_status' => $fromStatus,
            'to_status' => 'completed',
            'changed_by_name' => 'بەشی ژمێریاری',
            'role' => 'supplier',
            'notes' => "پسوولەی کۆتایی و ژمێریاری تەواوکرا. ستۆکی کۆگا بە فەرمی کەمکرایەوە. [{$settleNote}]",
        ]);

        // Send chat notification
        $conv = B2bConversation::firstOrCreate([
            'supplier_company_id' => $po->supplier_company_id,
            'retailer_company_id' => $po->retailer_company_id,
        ]);
        B2bMessage::create([
            'conversation_id' => $conv->id,
            'sender_company_id' => $po->supplier_company_id,
            'message_type' => 'po_notice',
            'body' => "🎉 **تەواوبوونی داواکاری (Completed)**: فەرمانی {$po->po_number} بە فەرمی لە ژمێریاری و کۆگای دابینکەر تەواو بوو. {$settleNote}",
        ]);
        $conv->update(['last_message_at' => now()]);

        return back()->with('success', "فەرمانی {$po->po_number} بە سەرکەوتوویی تەواو بوو (Completed)، ستۆکی کۆگا کەمکرایەوە و حسابی دارایی یەکلاکرایەوە!");
    }

    public function orderCancel(Request $request, $id)
    {
        $po = PurchaseOrder::with(['items'])->findOrFail($id);
        app(B2bTenantAuthorizationService::class)->authorizeOrderAccess($request, $po, 'cancel');
        $fromStatus = $po->status;

        // Release reserved stock if it was reserved
        if ($po->stock_reserved) {
            foreach ($po->items as $item) {
                $prod = Product::find($item->product_id);
                if ($prod && $prod->reserved_stock >= $item->quantity_ordered) {
                    $prod->decrement('reserved_stock', $item->quantity_ordered);
                }
            }
        }

        $cancelReason = $request->input('cancel_reason', 'داواکاری هەڵوەشێنرایەوە (ڕەتکرایەوە) و تەواوی قەرز و ستۆک پوچەڵکرایەوە.');
        app(\App\Services\FinancialPaymentService::class)->processOrderCancellation(
            $po,
            $cancelReason,
            auth()->user()
        );

        OrderStatusLog::create([
            'purchase_order_id' => $po->id,
            'from_status' => $fromStatus,
            'to_status' => 'cancelled',
            'changed_by_name' => auth()->user()?->name ?? 'سیستەم / لایەنی پەیوەندیدار',
            'role' => auth()->user()?->role ?? 'supplier',
            'notes' => $cancelReason,
        ]);

        return back()->with('success', "فەرمانی {$po->po_number} هەڵوەشێنرایەوە (ڕەتکرایەوە) و هیچ قەرزێک لەسەر مارکێت نامێنێت.");
    }

    public function getOrderAuditLogs(Request $request, $id)
    {
        $po = PurchaseOrder::with('statusLogs')->findOrFail($id);
        app(B2bTenantAuthorizationService::class)->authorizeOrderAccess($request, $po, 'audit_logs');
        return response()->json([
            'po_number' => $po->po_number,
            'current_status' => $po->status,
            'logs' => $po->statusLogs,
        ]);
    }

    public function settleDebt(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);
        app(B2bTenantAuthorizationService::class)->authorizeOrderAccess($request, $po, 'settle_debt');
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:0.01|max:' . $po->remaining_debt,
        ]);

        $pay = (float) $validated['payment_amount'];
        $idempotencyKey = $request->header('X-Idempotency-Key') ?: $request->input('idempotency_key') ?: $request->input('transaction_reference');
        if ($idempotencyKey) {
            $existing = PaymentReceiptVoucher::where('transaction_reference', $idempotencyKey)->first();
            if ($existing) {
                return back()->with('success', "ئەم وەسڵە پێشتر تۆمارکراوە: {$existing->voucher_number}. قەرزی ماوە: \${$po->remaining_debt}.");
            }
        }

        $financialService = app(\App\Services\B2bFinancialLedgerService::class);
        $voucher = $financialService->recordReceiptVoucher(
            $po->supplier_company_id,
            $po->retailer_company_id,
            $pay,
            $request->input('payment_method', 'cash'),
            $request->input('collected_by_user_id'),
            $po->id,
            null,
            $request->filled('amount_iqd') ? (float)$request->input('amount_iqd') : null,
            $request->filled('exchange_rate') ? (float)$request->input('exchange_rate') : null,
            $request->input('cheque_number'),
            $request->input('cheque_due_date'),
            $idempotencyKey,
            $request->input('notes'),
            auth()->user()
        );
        $po->refresh();

        return back()->with('success', "بڕی \${$pay} بە وەسڵی فەرمی {$voucher->voucher_number} لە قەرز کەمکرایەوە! قەرزی ماوە: \${$po->remaining_debt}.");
    }

    public function showInvoice(Request $request, $id)
    {
        $order = PurchaseOrder::with(['retailer', 'supplier', 'items'])->findOrFail($id);
        app(B2bTenantAuthorizationService::class)->authorizeOrderAccess($request, $order, 'view_invoice');
        return view('invoice', compact('order'));
    }

    public function updateOrderStatus(Request $request, $id)
    {
        app(B2bTenantAuthorizationService::class)->authorizePlatformAdmin($request);

        $po = PurchaseOrder::findOrFail($id);
        $fromStatus = $po->status;
        $status = $request->input('status', 'dispatched');
        $po->update(['status' => $status]);

        OrderStatusLog::create([
            'purchase_order_id' => $po->id,
            'from_status' => $fromStatus,
            'to_status' => $status,
            'changed_by_name' => 'بەڕێوەبەری سیستەم',
            'role' => 'operator',
            'notes' => "دۆخی داواکاری گۆڕدرا بۆ: {$status}",
        ]);

        return back()->with('success', "دۆخی داواکاری {$po->po_number} نوێکرایەوە بۆ: {$status}");
    }

    public function checkNotifications(Request $request)
    {
        $actor = app(B2bTenantAuthorizationService::class)->getActorContext($request);
        if (!$actor['is_authenticated']) {
            return response()->json(['total_unread' => 0, 'notifications' => []]);
        }

        $user = $actor['user'];
        $role = $actor['role'];
        $companyId = $actor['company_id'];

        if ($actor['is_platform_admin']) {
            $role = 'operator';
        } elseif ($user->isDriver()) {
            $role = 'driver';
        } elseif ($user->company) {
            $role = $user->company->business_type;
            $companyId = $user->company_id;
        }

        $notifications = collect();

        if ($role === 'supplier' && $companyId) {
            $convIds = B2bConversation::where('supplier_company_id', $companyId)->pluck('id');
            $unreadMsgs = B2bMessage::with('sender')
                ->whereIn('conversation_id', $convIds)
                ->where('sender_company_id', '!=', $companyId)
                ->where('is_read', false)
                ->latest()
                ->get();

            foreach ($unreadMsgs as $um) {
                $typeLabel = $um->message_type === 'quote_proposal' ? '🏷️ ئۆفەری نرخ' : ($um->message_type === 'voice' ? '🎙️ فۆیس' : ($um->message_type === 'image' ? '📷 وێنە' : '💬 پەیام'));
                $notifications->push([
                    'id' => 'msg_' . $um->id,
                    'type' => $um->message_type,
                    'title' => $typeLabel . ' لە ' . ($um->sender?->name ?? 'مارکێت'),
                    'body' => Str::limit($um->body, 60),
                    'url' => route('marketlink.index', ['role' => 'supplier', 'active_supplier_id' => $companyId, 'conversation_id' => $um->conversation_id]) . '#chat-section',
                    'time' => $um->created_at->diffForHumans(),
                    'sender_name' => $um->sender?->name ?? 'مارکێت',
                ]);
            }

            $newOrders = PurchaseOrder::with('retailer')
                ->where('supplier_company_id', $companyId)
                ->whereIn('status', ['pending', 'issued'])
                ->latest()
                ->take(5)
                ->get();

            foreach ($newOrders as $no) {
                $notifications->push([
                    'id' => 'po_' . $no->id,
                    'type' => 'order',
                    'title' => '📦 فەرمانی کڕینی نوێ: ' . $no->po_number,
                    'body' => ($no->retailer?->name ?? 'مارکێت') . ' — $' . number_format($no->total_amount, 2),
                    'url' => route('marketlink.index', ['role' => 'supplier', 'active_supplier_id' => $companyId]) . '#orders-section',
                    'time' => $no->created_at->diffForHumans(),
                    'sender_name' => $no->retailer?->name ?? 'مارکێت',
                ]);
            }
        } elseif ($role === 'retailer' && $companyId) {
            $convIds = B2bConversation::where('retailer_company_id', $companyId)->pluck('id');
            $unreadMsgs = B2bMessage::with('sender')
                ->whereIn('conversation_id', $convIds)
                ->where('sender_company_id', '!=', $companyId)
                ->where('is_read', false)
                ->latest()
                ->get();

            foreach ($unreadMsgs as $um) {
                $typeTitle = $um->message_type === 'quote_proposal' ? '🏷️ ئۆفەری نرخی تایبەت' : ($um->message_type === 'voice' ? '🎙️ فۆیس' : ($um->message_type === 'image' ? '📷 وێنە' : '💬 پەیامی نوێ'));
                $notifications->push([
                    'id' => 'msg_' . $um->id,
                    'type' => $um->message_type,
                    'title' => $typeTitle . ' لە ' . ($um->sender?->name ?? 'دابینکەر'),
                    'body' => Str::limit($um->body, 60),
                    'url' => route('marketlink.index', ['role' => 'retailer', 'active_retailer_id' => $companyId, 'conversation_id' => $um->conversation_id]) . '#chat-section',
                    'time' => $um->created_at->diffForHumans(),
                    'sender_name' => $um->sender?->name ?? 'دابینکەر',
                ]);
            }

            $orderUpdates = PurchaseOrder::with('supplier')
                ->where('retailer_company_id', $companyId)
                ->whereIn('status', ['approved', 'processing', 'dispatched', 'delivered'])
                ->latest()
                ->take(5)
                ->get();

            foreach ($orderUpdates as $do) {
                $statusLabel = $do->status === 'approved' ? 'پەسەندکرا' : ($do->status === 'processing' ? 'ئامادە دەکرێت' : ($do->status === 'dispatched' ? 'بارکراوە لە ڕێگایە' : 'گەیشت'));
                $notifications->push([
                    'id' => 'po_disp_' . $do->id,
                    'type' => $do->status === 'dispatched' ? 'dispatch' : 'order',
                    'title' => '🚚 داواکاری ' . $do->po_number . ' ' . $statusLabel,
                    'body' => ($do->supplier?->name ?? 'دابینکەر') . ' — دۆخ: ' . $do->status,
                    'url' => route('marketlink.index', ['role' => 'retailer', 'active_retailer_id' => $companyId]),
                    'time' => $do->updated_at->diffForHumans(),
                    'sender_name' => $do->supplier?->name ?? 'دابینکەر',
                ]);
            }
        } elseif ($role === 'operator') {
            $pendingVerifs = BusinessVerification::with('company')->where('review_status', 'submitted')->latest()->take(5)->get();
            foreach ($pendingVerifs as $pv) {
                $notifications->push([
                    'id' => 'verif_' . $pv->id,
                    'type' => 'verification',
                    'title' => '📑 داواکاری نوێی پەسەندکردن',
                    'body' => ($pv->company?->name ?? 'کۆمپانیا') . ' مۆڵەتی نوێی ناردووە',
                    'url' => route('marketlink.index', ['role' => 'operator']),
                    'time' => $pv->created_at->diffForHumans(),
                    'sender_name' => $pv->company?->name ?? 'سیستەم',
                ]);
            }
            $pendingOrders = PurchaseOrder::with(['supplier', 'retailer'])->where('status', 'pending')->latest()->take(5)->get();
            foreach ($pendingOrders as $po) {
                $notifications->push([
                    'id' => 'po_op_' . $po->id,
                    'type' => 'order',
                    'title' => '📦 فەرمانی نوێی تۆمارکراو: ' . $po->po_number,
                    'body' => ($po->retailer?->name ?? 'مارکێت') . ' ➔ ' . ($po->supplier?->name ?? 'دابینکەر'),
                    'url' => route('marketlink.index', ['role' => 'operator']),
                    'time' => $po->created_at->diffForHumans(),
                    'sender_name' => $po->retailer?->name ?? 'سیستەم',
                ]);
            }
        } elseif ($role === 'driver') {
            $shipments = PurchaseOrder::with(['retailer', 'supplier'])
                ->where('driver_id', $user->id)
                ->where('status', 'dispatched')
                ->latest()
                ->take(5)
                ->get();
            foreach ($shipments as $sh) {
                $notifications->push([
                    'id' => 'drv_' . $sh->id,
                    'type' => 'dispatch',
                    'title' => '🚚 باری نوێ بۆ گەیاندن: ' . $sh->po_number,
                    'body' => 'بۆ مارکێتی: ' . ($sh->retailer?->name ?? 'مارکێت'),
                    'url' => route('marketlink.index', ['role' => 'driver']),
                    'time' => $sh->updated_at->diffForHumans(),
                    'sender_name' => $sh->supplier?->name ?? 'دابینکەر',
                ]);
            }
        }

        // Merge stored B2bNotification records
        $dbNotifications = B2bNotification::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere(function ($sub) use ($user) {
                  $sub->whereNull('user_id');
                  if ($user->company_id) {
                      $sub->where('company_id', $user->company_id);
                  } elseif ($user->isPlatformAdmin()) {
                      $sub->whereNull('company_id');
                  }
              });
        })
        ->where('is_read', false)
        ->latest()
        ->take(15)
        ->get();

        foreach ($dbNotifications as $dn) {
            $notifications->push([
                'id' => 'db_' . $dn->id,
                'db_id' => $dn->id,
                'type' => $dn->type,
                'title' => $dn->title,
                'body' => $dn->message,
                'url' => $dn->action_url ?: '#',
                'time' => $dn->created_at->diffForHumans(),
                'sender_name' => 'MarketLink',
            ]);
        }

        return response()->json([
            'total_unread' => $notifications->count(),
            'unread_count' => $notifications->count(),
            'notifications' => $notifications->values(),
        ]);
    }

    public function markNotificationsRead(Request $request)
    {
        $actor = app(B2bTenantAuthorizationService::class)->getActorContext($request);
        if (!$actor['is_authenticated']) {
            return response()->json(['success' => false], 403);
        }

        $user = $actor['user'];
        $companyId = $actor['company_id'];
        $role = $user->company?->business_type ?? $user->role;

        if ($companyId) {
            if ($role === 'supplier') {
                $convIds = B2bConversation::where('supplier_company_id', $companyId)->pluck('id');
            } else {
                $convIds = B2bConversation::where('retailer_company_id', $companyId)->pluck('id');
            }

            B2bMessage::whereIn('conversation_id', $convIds)
                ->where('sender_company_id', '!=', $companyId)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        if ($request->filled('id')) {
            $id = $request->input('id');
            if (is_string($id) && str_starts_with($id, 'db_')) {
                $id = (int)str_replace('db_', '', $id);
            }
            $dn = B2bNotification::find($id);
            if ($dn) {
                $dn->markAsRead();
            }
        } else {
            B2bNotification::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function ($sub) use ($user) {
                      $sub->whereNull('user_id');
                      if ($user->company_id) {
                          $sub->where('company_id', $user->company_id);
                      } elseif ($user->isPlatformAdmin()) {
                          $sub->whereNull('company_id');
                      }
                  });
            })
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function exportOrdersReport(Request $request, B2bReportService $reportService)
    {
        $actor = app(B2bTenantAuthorizationService::class)->getActorContext($request);
        app(B2bTenantAuthorizationService::class)->authorizeReportAccess($request);

        $status = $request->query('status');

        if ($actor['is_platform_admin']) {
            $supplierId = $request->query('supplier_id') ? (int)$request->query('supplier_id') : null;
            $retailerId = $request->query('retailer_id') ? (int)$request->query('retailer_id') : null;
        } else {
            $user = $actor['user'];
            $company = $user->company;
            if ($company?->business_type === 'supplier') {
                $supplierId = (int)$company->id;
                $retailerId = null;
            } else {
                $retailerId = (int)$company->id;
                $supplierId = null;
            }
        }

        return $reportService->exportOrdersCsv(
            $supplierId,
            $retailerId,
            $status
        );
    }

    public function exportStatementReport(Request $request, $companyId, B2bReportService $reportService)
    {
        app(B2bTenantAuthorizationService::class)->authorizeReportAccess($request, (int)$companyId);
        return $reportService->exportAccountStatementCsv((int)$companyId);
    }

    public function createBackup(Request $request, B2bBackupService $backupService)
    {
        app(B2bTenantAuthorizationService::class)->authorizePlatformAdmin($request);

        try {
            $includeMedia = $request->boolean('include_media');
            $description = $request->input('description', 'Manual backup from Admin Panel');

            $result = $backupService->createBackup([
                'include_media' => $includeMedia,
                'description' => $description,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'backup' => $result]);
            }

            return redirect()->back()->with('success', 'کۆپی پارێزەر (Backup) بە سەرکەوتوویی دروستکرا: ' . $result['filename']);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'هەڵە لە دروستکردنی باکئەپ: ' . $e->getMessage());
        }
    }

    public function restoreBackup(Request $request, B2bBackupService $backupService)
    {
        app(B2bTenantAuthorizationService::class)->authorizePlatformAdmin($request);

        $request->validate([
            'filename' => 'required|string',
        ]);

        try {
            $filename = basename($request->input('filename'));
            $result = $backupService->restoreBackup($filename, true);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'result' => $result]);
            }

            return redirect()->back()->with('success', 'سیستەم بە سەرکەوتوویی گەڕێندرایەوە. کۆپی پارێزەری پێش گەڕاندنەوە: ' . $result['safety_backup']);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'هەڵە لە گەڕاندنەوەی باکئەپ: ' . $e->getMessage());
        }
    }

    public function downloadBackup(Request $request, string $filename, B2bBackupService $backupService)
    {
        app(B2bTenantAuthorizationService::class)->authorizePlatformAdmin($request);

        $filename = basename($filename);
        if (!str_ends_with($filename, '.tar.gz')) {
            abort(404, 'ناوی فایل نادروستە.');
        }

        $filePath = $backupService->getBackupDir() . DIRECTORY_SEPARATOR . $filename;
        if (!File::exists($filePath)) {
            abort(404, 'فایلی باکئەپ نەدۆزرایەوە.');
        }

        return response()->download($filePath);
    }

    public function deleteBackup(Request $request, B2bBackupService $backupService)
    {
        app(B2bTenantAuthorizationService::class)->authorizePlatformAdmin($request);

        $request->validate([
            'filename' => 'required|string',
        ]);

        try {
            $filename = basename($request->input('filename'));
            $backupService->deleteBackup($filename);

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->back()->with('success', 'فایلی باکئەپ بە سەرکەوتوویی سڕایەوە.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'هەڵە لە سڕینەوە: ' . $e->getMessage());
        }
    }

    public function storeUser(Request $request)
    {
        $currentUser = auth()->user();
        if (!$currentUser || (!$currentUser->isPlatformAdmin() && !$currentUser->isCompanyOwner() && !$currentUser->isCompanyManager())) {
            SecurityAuditLog::logViolation(
                'rbac_user_create_denied',
                'هەوڵی دروستکردنی بەکارهێنەر بەبێ هەبوونی مۆڵەتی پێویست.',
                $request,
                $currentUser?->company_id,
                $currentUser?->id,
                'warning'
            );
            abort(403, 'تەنها خاوەن یان بەڕێوەبەری کۆمپانیا دەتوانێت هەژماری نوێ زیاد بکات.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:owner,manager,staff,buyer,receiver,accountant,finance_approver,driver,platform_admin',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $targetCompanyId = $validated['company_id'] ?? null;
        $targetRole = $validated['role'];

        if (!$currentUser->isPlatformAdmin()) {
            $targetCompanyId = $currentUser->company_id;
            if (in_array($targetRole, ['platform_admin', 'owner'])) {
                SecurityAuditLog::logViolation(
                    'role_escalation_attempt',
                    "هەوڵی بەرزکردنەوەی ناڕەوای دەسەڵات بۆ: {$targetRole}",
                    $request,
                    $currentUser->company_id,
                    $currentUser->id,
                    'critical'
                );
                abort(403, 'مۆڵەتی بەخشینی ئەم دەسەڵاتەت نییە.');
            }
        }

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $targetRole,
            'company_id' => $targetCompanyId,
            'is_active' => true,
        ]);

        SecurityAuditLog::logViolation(
            'user_created',
            "بەکارهێنەری نوێ دروستکرا: {$newUser->name} ({$newUser->email}) بە ڕۆڵی: {$newUser->role}",
            $request,
            $targetCompanyId,
            $newUser->id,
            'info'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'بەکارهێنەر بە سەرکەوتوویی زیادکرا.',
                'user' => $newUser,
            ]);
        }

        return redirect()->back()->with('success', "بەکارهێنەری نوێ ({$newUser->name}) بە سەرکەوتوویی زیادکرا.");
    }

    public function toggleUserStatus(Request $request, $id)
    {
        $currentUser = auth()->user();
        $targetUser = User::findOrFail($id);

        if (!$currentUser || (!$currentUser->isPlatformAdmin() && !$currentUser->isCompanyOwner() && !$currentUser->isCompanyManager())) {
            abort(403, 'مۆڵەتی دەستکاریکردنی بەکارهێنەرت نییە.');
        }

        if ((int)$currentUser->id === (int)$targetUser->id) {
            abort(422, 'ناتوانیت هەژماری خۆت ناچالاک بکەیت.');
        }

        if (!$currentUser->isPlatformAdmin() && (int)$currentUser->company_id !== (int)$targetUser->company_id) {
            SecurityAuditLog::logViolation(
                'idor_user_tampering',
                "هەوڵی دەستکاریکردنی هەژماری کۆمپانیایەکی تر ({$targetUser->id}) لە لایەن بەکارهێنەر ({$currentUser->id})",
                $request,
                $currentUser->company_id,
                $currentUser->id,
                'critical'
            );
            abort(403, 'دەسەڵاتی دەستکاریکردنی بەکارهێنەری ئەم کۆمپانیایەت نییە.');
        }

        $targetUser->is_active = !$targetUser->is_active;
        $targetUser->save();

        $actionName = $targetUser->is_active ? 'چالاککرایەوە' : 'ناچالاککرا';

        SecurityAuditLog::logViolation(
            'user_status_changed',
            "هەژماری ({$targetUser->email}) {$actionName}.",
            $request,
            $targetUser->company_id,
            $targetUser->id,
            'info'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $targetUser->is_active,
                'message' => "هەژمار بە سەرکەوتوویی {$actionName}.",
            ]);
        }

        return redirect()->back()->with('success', "هەژماری ({$targetUser->name}) بە سەرکەوتوویی {$actionName}.");
    }

    public function updateUserRole(Request $request, $id)
    {
        $currentUser = auth()->user();
        $targetUser = User::findOrFail($id);

        if (!$currentUser || (!$currentUser->isPlatformAdmin() && !$currentUser->isCompanyOwner())) {
            abort(403, 'تەنها خاوەن کۆمپانیا دەتوانێت ڕۆڵی کارمەند بگۆڕێت.');
        }

        if (!$currentUser->isPlatformAdmin() && (int)$currentUser->company_id !== (int)$targetUser->company_id) {
            abort(403, 'دەسەڵاتی گۆڕینی ڕۆڵی ئەم بەکارهێنەرەت نییە.');
        }

        $validated = $request->validate([
            'role' => 'required|string|in:owner,manager,staff,buyer,receiver,accountant,finance_approver,driver,platform_admin',
        ]);

        $newRole = $validated['role'];

        if (!$currentUser->isPlatformAdmin() && in_array($newRole, ['platform_admin', 'owner'])) {
            abort(403, 'مۆڵەتی بەخشینی ئەم ڕۆڵەت نییە.');
        }

        $oldRole = $targetUser->role;
        $targetUser->role = $newRole;
        $targetUser->save();

        SecurityAuditLog::logViolation(
            'user_role_changed',
            "ڕۆڵی بەکارهێنەر ({$targetUser->email}) لە {$oldRole}ەوە گۆڕدرا بۆ {$newRole}.",
            $request,
            $targetUser->company_id,
            $targetUser->id,
            'info'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'role' => $newRole,
                'message' => 'ڕۆڵی بەکارهێنەر بە سەرکەوتوویی نوێکرایەوە.',
            ]);
        }

        return redirect()->back()->with('success', "ڕۆڵی ({$targetUser->name}) بە سەرکەوتوویی گۆڕدرا بۆ {$newRole}.");
    }
}

