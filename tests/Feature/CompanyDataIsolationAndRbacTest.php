<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\B2bConversation;
use App\Models\B2bMessage;
use App\Models\PaymentReceiptVoucher;
use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyDataIsolationAndRbacTest extends TestCase
{
    use RefreshDatabase;

    protected Company $supplierA;
    protected Company $supplierB;
    protected Company $retailerA;
    protected Company $retailerB;

    protected User $supplierAOwner;
    protected User $supplierAStaff;
    protected User $supplierADriver;
    protected User $supplierAAccountant;

    protected User $supplierBOwner;

    protected User $retailerAOwner;
    protected User $retailerABuyer;
    protected User $retailerAReceiver;

    protected User $retailerBOwner;

    protected User $platformAdmin;

    protected ProductCategory $category;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = ProductCategory::create([
            'name_ku' => 'خۆراکی گشتی',
            'name_ar' => 'مواد غذائية',
            'name_en' => 'General Grocery',
            'slug' => 'grocery',
        ]);

        // Suppliers
        $this->supplierA = Company::create([
            'name' => 'Supplier Alpha Co',
            'trade_license_number' => 'TL-SUP-A-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'alpha@supplier.krd',
            'primary_phone' => '07501112233',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی پیشەسازی هەولێر',
        ]);

        $this->supplierB = Company::create([
            'name' => 'Supplier Beta Co',
            'trade_license_number' => 'TL-SUP-B-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'beta@supplier.krd',
            'primary_phone' => '07502223344',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی پیشەسازی سلێمانی',
        ]);

        // Retailers
        $this->retailerA = Company::create([
            'name' => 'Market Hawler One',
            'trade_license_number' => 'TL-RET-A-01',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'hawler1@market.krd',
            'primary_phone' => '07503334455',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی ٦٠ مەتری',
        ]);

        $this->retailerB = Company::create([
            'name' => 'Market Slemani One',
            'trade_license_number' => 'TL-RET-B-01',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'slemani1@market.krd',
            'primary_phone' => '07504445566',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی بازنەیی مەولەوی',
        ]);

        // Products
        $this->productA = Product::create([
            'supplier_company_id' => $this->supplierA->id,
            'category_id' => $this->category->id,
            'sku' => 'SKU-ALPHA-100',
            'barcode' => '777000111222',
            'title' => 'شەکر Alpha 5kg',
            'base_wholesale_price' => 10.00,
            'minimum_order_quantity' => 5,
            'current_stock' => 100,
            'reserved_stock' => 10,
            'is_available' => true,
        ]);

        $this->productB = Product::create([
            'supplier_company_id' => $this->supplierB->id,
            'category_id' => $this->category->id,
            'sku' => 'SKU-BETA-200',
            'barcode' => '888000333444',
            'title' => 'برنج Beta 10kg',
            'base_wholesale_price' => 25.00,
            'minimum_order_quantity' => 2,
            'current_stock' => 80,
            'reserved_stock' => 5,
            'is_available' => true,
        ]);

        // Supplier A Users
        $this->supplierAOwner = User::create([
            'company_id' => $this->supplierA->id,
            'name' => 'خاوەن دابینکەری ئەلفا',
            'email' => 'owner.alpha@supplier.krd',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->supplierAStaff = User::create([
            'company_id' => $this->supplierA->id,
            'name' => 'کارمەندی دابینکەری ئەلفا',
            'email' => 'staff.alpha@supplier.krd',
            'password' => Hash::make('password123'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->supplierADriver = User::create([
            'company_id' => $this->supplierA->id,
            'name' => 'شۆفێری دابینکەری ئەلفا',
            'email' => 'driver.alpha@supplier.krd',
            'password' => Hash::make('password123'),
            'role' => 'driver',
            'is_active' => true,
        ]);

        $this->supplierAAccountant = User::create([
            'company_id' => $this->supplierA->id,
            'name' => 'ژمێریاری دابینکەری ئەلفا',
            'email' => 'accountant.alpha@supplier.krd',
            'password' => Hash::make('password123'),
            'role' => 'accountant',
            'is_active' => true,
        ]);

        // Supplier B Owner
        $this->supplierBOwner = User::create([
            'company_id' => $this->supplierB->id,
            'name' => 'خاوەن دابینکەری بێتا',
            'email' => 'owner.beta@supplier.krd',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Retailer A Users
        $this->retailerAOwner = User::create([
            'company_id' => $this->retailerA->id,
            'name' => 'خاوەن مارکێتی ئەلفا',
            'email' => 'owner.a@retailer.krd',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->retailerABuyer = User::create([
            'company_id' => $this->retailerA->id,
            'name' => 'بەرپرسی کڕینی مارکێتی ئەلفا',
            'email' => 'buyer.a@retailer.krd',
            'password' => Hash::make('password123'),
            'role' => 'buyer',
            'is_active' => true,
        ]);

        $this->retailerAReceiver = User::create([
            'company_id' => $this->retailerA->id,
            'name' => 'وەرگری کاڵای مارکێتی ئەلفا',
            'email' => 'receiver.a@retailer.krd',
            'password' => Hash::make('password123'),
            'role' => 'receiver',
            'is_active' => true,
        ]);

        // Retailer B Owner
        $this->retailerBOwner = User::create([
            'company_id' => $this->retailerB->id,
            'name' => 'خاوەن مارکێتی بێتا',
            'email' => 'owner.b@retailer.krd',
            'password' => Hash::make('password123'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        // Platform Admin
        $this->platformAdmin = User::create([
            'company_id' => null,
            'name' => 'سەرپەرشتیاری پلاتفۆرم',
            'email' => 'admin@marketlink.krd',
            'password' => Hash::make('password123'),
            'role' => 'platform_admin',
            'is_active' => true,
        ]);
    }

    protected function createOrder(Company $supplier, Company $retailer, Product $product, int $qty = 10, string $status = 'pending'): PurchaseOrder
    {
        $total = $product->base_wholesale_price * $qty;

        $po = PurchaseOrder::create([
            'po_number' => 'PO-' . rand(10000, 99999),
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'status' => $status,
            'currency' => 'USD',
            'subtotal_amount' => $total,
            'total_amount' => $total,
            'remaining_debt' => $total,
            'paid_amount' => 0.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'cash_on_delivery',
            'delivery_address' => 'ناونیشانی مارکێت',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'product_title' => $product->title,
            'sku' => $product->sku,
            'quantity_ordered' => $qty,
            'unit_price' => $product->base_wholesale_price,
            'total_price' => $total,
        ]);

        return $po;
    }

    /**
     * Requirement: Supplier A cannot view, approve or cancel Supplier B orders.
     */
    public function test_supplier_a_cannot_view_or_approve_or_cancel_supplier_b_orders(): void
    {
        $poBeta = $this->createOrder($this->supplierB, $this->retailerA, $this->productB, 4);

        // Supplier A attempts to approve Supplier B's order
        $response = $this->actingAs($this->supplierAOwner)
            ->post("/orders/{$poBeta->id}/approve");

        $response->assertStatus(403);
        $this->assertEquals('pending', $poBeta->fresh()->status);
        $this->assertTrue(SecurityAuditLog::where('event_type', 'unauthorized_tenant_access')->exists());

        // Supplier A attempts to cancel Supplier B's order
        $cancelResponse = $this->actingAs($this->supplierAOwner)
            ->post("/orders/{$poBeta->id}/cancel", ['cancel_reason' => 'illegal attempt']);

        $cancelResponse->assertStatus(403);
        $this->assertEquals('pending', $poBeta->fresh()->status);
    }

    /**
     * Requirement: Supplier A cannot modify stock or create products under Supplier B.
     */
    public function test_supplier_a_cannot_modify_stock_or_create_products_for_supplier_b(): void
    {
        // Attempt to change stock of Supplier B's product
        $stockResponse = $this->actingAs($this->supplierAOwner)
            ->post("/supplier/products/{$this->productB->id}/stock", ['current_stock' => 999]);

        $stockResponse->assertStatus(403);
        $this->assertEquals(80, $this->productB->fresh()->current_stock);
        $this->assertTrue(SecurityAuditLog::where('event_type', 'role_permission_denied')->exists());

        // Attempt to create product with supplier_company_id of Supplier B
        $createProdResponse = $this->actingAs($this->supplierAOwner)
            ->post('/supplier/products', [
                'supplier_company_id' => $this->supplierB->id,
                'category_id' => $this->category->id,
                'sku' => 'SKU-MALICIOUS-1',
                'title' => 'Malicious Product',
                'base_wholesale_price' => 15.0,
                'minimum_order_quantity' => 1,
                'current_stock' => 10,
            ]);

        $createProdResponse->assertStatus(403);
        $this->assertDatabaseMissing('products', ['sku' => 'SKU-MALICIOUS-1']);
    }

    /**
     * Requirement: Market A cannot view Market B account, invoices, or audit logs (anti-IDOR).
     */
    public function test_market_a_cannot_view_market_b_invoices_or_audit_logs(): void
    {
        $poBetaMarket = $this->createOrder($this->supplierA, $this->retailerB, $this->productA, 10);

        // Retailer A tries to view invoice of Retailer B
        $invoiceResponse = $this->actingAs($this->retailerAOwner)
            ->get("/orders/{$poBetaMarket->id}/invoice");

        $invoiceResponse->assertStatus(403);
        $this->assertTrue(SecurityAuditLog::where('event_type', 'idor_attempt')->exists());

        // Retailer A tries to view audit logs of Retailer B's order
        $auditResponse = $this->actingAs($this->retailerAOwner)
            ->get("/orders/{$poBetaMarket->id}/audit-logs");

        $auditResponse->assertStatus(403);
    }

    /**
     * Requirement: Market A cannot place orders or checkout by spoofing retailer_company_id.
     */
    public function test_market_a_cannot_spoof_company_id_in_orders_or_checkout(): void
    {
        // Direct PO creation tampering
        $orderResponse = $this->actingAs($this->retailerAOwner)
            ->post('/retailer/orders', [
                'retailer_company_id' => $this->retailerB->id,
                'supplier_company_id' => $this->supplierA->id,
                'product_id' => $this->productA->id,
                'quantity' => 5,
                'agreed_price' => 10.0,
                'payment_terms' => 'cash_on_delivery',
            ]);

        $orderResponse->assertStatus(403);
        $this->assertTrue(SecurityAuditLog::where('event_type', 'unauthorized_tenant_access')->exists());

        // Multi-supplier checkout tampering
        $checkoutResponse = $this->actingAs($this->retailerAOwner)
            ->post('/retailer/cart/checkout', [
                'retailer_company_id' => $this->retailerB->id,
                'items' => [
                    ['product_id' => $this->productA->id, 'quantity' => 5],
                ],
                'payment_terms' => 'cash_on_delivery',
            ]);

        $checkoutResponse->assertStatus(403);
    }

    /**
     * Requirement: Retailer A cannot view Retailer B financial statement.
     */
    public function test_retailer_a_cannot_view_retailer_b_financial_statement(): void
    {
        $response = $this->actingAs($this->retailerAOwner)
            ->get("/financial/statement/{$this->retailerB->id}");

        $response->assertStatus(403);
        $this->assertTrue(SecurityAuditLog::where('event_type', 'idor_financial_statement_access')->exists());
    }

    /**
     * Requirement: Driver cannot perform managerial financial operations.
     */
    public function test_driver_cannot_create_opening_balance_or_perform_safe_clearance(): void
    {
        // Driver tries to set opening balance
        $obResponse = $this->actingAs($this->supplierADriver)
            ->post('/financial/opening-balance', [
                'supplier_company_id' => $this->supplierA->id,
                'retailer_company_id' => $this->retailerA->id,
                'amount' => 500.0,
            ]);

        $obResponse->assertStatus(403);
        $this->assertTrue(SecurityAuditLog::where('event_type', 'role_permission_denied')->exists());

        // Driver tries to perform cash clearance
        $clearanceResponse = $this->actingAs($this->supplierADriver)
            ->post('/financial/clearance', [
                'supplier_company_id' => $this->supplierA->id,
                'driver_user_id' => $this->supplierADriver->id,
                'actual_cash_deposited' => 250.0,
            ]);

        $clearanceResponse->assertStatus(403);

        // Driver tries to verify a voucher into safe
        $voucher = PaymentReceiptVoucher::create([
            'voucher_number' => 'RV-TEST-001',
            'supplier_company_id' => $this->supplierA->id,
            'retailer_company_id' => $this->retailerA->id,
            'amount' => 100.0,
            'payment_method' => 'cash',
            'receipt_date' => now()->toDateString(),
            'custody_status' => 'in_driver_custody',
            'is_verified' => false,
        ]);

        $verifyResponse = $this->actingAs($this->supplierADriver)
            ->post("/financial/vouchers/{$voucher->id}/verify");

        $verifyResponse->assertStatus(403);
    }

    /**
     * Requirement: Driver cannot deposit receipt voucher directly into company treasury safe.
     */
    public function test_driver_cannot_deposit_receipt_voucher_directly_to_safe(): void
    {
        $response = $this->actingAs($this->supplierADriver)
            ->post('/financial/vouchers', [
                'supplier_company_id' => $this->supplierA->id,
                'retailer_company_id' => $this->retailerA->id,
                'amount' => 150.0,
                'payment_method' => 'cash',
                'custody_status' => 'deposited_to_safe',
            ]);

        $response->assertStatus(403);
        $this->assertTrue(SecurityAuditLog::where('event_type', 'role_permission_denied')->exists());
    }

    /**
     * Requirement: Driver cannot view general financial accounting statements.
     */
    public function test_driver_cannot_view_financial_statements(): void
    {
        $response = $this->actingAs($this->supplierADriver)
            ->get("/financial/statement/{$this->retailerA->id}");

        $response->assertStatus(403);
    }

    /**
     * Requirement: Driver cannot place orders or create demands.
     */
    public function test_driver_cannot_place_orders_or_create_demands(): void
    {
        $orderResponse = $this->actingAs($this->supplierADriver)
            ->post('/retailer/orders', [
                'retailer_company_id' => $this->retailerA->id,
                'supplier_company_id' => $this->supplierA->id,
                'product_id' => $this->productA->id,
                'quantity' => 5,
                'agreed_price' => 10.0,
                'payment_terms' => 'cash_on_delivery',
            ]);

        $orderResponse->assertStatus(403);

        $demandResponse = $this->actingAs($this->supplierADriver)
            ->post('/retailer/demands', [
                'retailer_company_id' => $this->retailerA->id,
                'title' => 'Driver unauthorized demand',
                'quantity' => 10,
                'unit' => 'Carton',
            ]);

        $demandResponse->assertStatus(403);
    }

    /**
     * Requirement: Staff role cannot approve, dispatch, or cancel orders.
     */
    public function test_staff_role_cannot_approve_or_dispatch_or_cancel_orders(): void
    {
        $po = $this->createOrder($this->supplierA, $this->retailerA, $this->productA, 10);

        // Staff tries to approve
        $approveResp = $this->actingAs($this->supplierAStaff)
            ->post("/orders/{$po->id}/approve");

        $approveResp->assertStatus(403);
        $this->assertEquals('pending', $po->fresh()->status);

        // Staff tries to dispatch
        $dispatchResp = $this->actingAs($this->supplierAStaff)
            ->post("/orders/{$po->id}/dispatch");

        $dispatchResp->assertStatus(403);

        // Staff tries to cancel
        $cancelResp = $this->actingAs($this->supplierAStaff)
            ->post("/orders/{$po->id}/cancel");

        $cancelResp->assertStatus(403);
    }

    /**
     * Requirement: Receiver role can receive orders but cannot approve or cancel orders.
     */
    public function test_receiver_role_can_receive_orders_but_cannot_approve_or_cancel(): void
    {
        $po = $this->createOrder($this->supplierA, $this->retailerA, $this->productA, 5, 'dispatched');

        // Receiver tries to approve
        $approveResp = $this->actingAs($this->retailerAReceiver)
            ->post("/orders/{$po->id}/approve");

        $approveResp->assertStatus(403);

        // Receiver tries to cancel
        $cancelResp = $this->actingAs($this->retailerAReceiver)
            ->post("/orders/{$po->id}/cancel");

        $cancelResp->assertStatus(403);

        // Receiver receives order
        $receiveResp = $this->actingAs($this->retailerAReceiver)
            ->post("/orders/{$po->id}/receive", [
                'inspected_by' => 'وەرگری فەرمی مارکێت',
                'inspection_notes' => 'کاڵاکان بە دروستی وەرگیران',
                'paid_amount' => 0.0,
                'payment_method' => 'credit',
            ]);

        $receiveResp->assertStatus(302);
        $this->assertEquals('delivered', $po->fresh()->status);
    }

    /**
     * Requirement: Accountant can create opening balance and verify vouchers.
     */
    public function test_accountant_can_create_opening_balance_and_verify_vouchers(): void
    {
        $obResp = $this->actingAs($this->supplierAAccountant)
            ->post('/financial/opening-balance', [
                'supplier_company_id' => $this->supplierA->id,
                'retailer_company_id' => $this->retailerA->id,
                'amount' => 1200.0,
                'type' => 'debit',
                'notes' => 'Initial debt for retailer',
            ]);

        $obResp->assertStatus(302);
        $this->assertDatabaseHas('customer_ledger_entries', [
            'supplier_company_id' => $this->supplierA->id,
            'retailer_company_id' => $this->retailerA->id,
            'debit_amount' => 1200.0,
        ]);

        $voucher = PaymentReceiptVoucher::create([
            'voucher_number' => 'RV-ACCT-001',
            'supplier_company_id' => $this->supplierA->id,
            'retailer_company_id' => $this->retailerA->id,
            'amount' => 450.0,
            'payment_method' => 'cash',
            'receipt_date' => now()->toDateString(),
            'custody_status' => 'in_driver_custody',
            'is_verified' => false,
        ]);

        $verifyResp = $this->actingAs($this->supplierAAccountant)
            ->post("/financial/vouchers/{$voucher->id}/verify");

        $verifyResp->assertStatus(302);
        $this->assertTrue((bool)$voucher->fresh()->is_verified);
        $this->assertEquals('deposited_to_safe', $voucher->fresh()->custody_status);
    }

    /**
     * Requirement: Chat and price proposals enforce strict tenant & product isolation.
     */
    public function test_chat_price_proposal_enforces_tenant_and_product_isolation(): void
    {
        $conv = B2bConversation::create([
            'supplier_company_id' => $this->supplierA->id,
            'retailer_company_id' => $this->retailerA->id,
            'subject' => 'گفتوگۆی تاقیکاری',
        ]);

        // Supplier B user tries to send proposal into Conversation between Supplier A & Retailer A
        $intruderProposal = $this->actingAs($this->supplierBOwner)
            ->post('/b2b/proposals', [
                'conversation_id' => $conv->id,
                'sender_company_id' => $this->supplierB->id,
                'product_id' => $this->productB->id,
                'proposed_unit_price' => 20.0,
                'min_quantity' => 5,
            ]);

        $intruderProposal->assertStatus(403);

        // Supplier A tries to offer price on Supplier B's product
        $illegalProductProposal = $this->actingAs($this->supplierAOwner)
            ->post('/b2b/proposals', [
                'conversation_id' => $conv->id,
                'sender_company_id' => $this->supplierA->id,
                'product_id' => $this->productB->id,
                'proposed_unit_price' => 18.0,
                'min_quantity' => 10,
            ]);

        $illegalProductProposal->assertStatus(403);
        $this->assertTrue(SecurityAuditLog::where('event_type', 'idor_product_tampering')->exists());
    }

    /**
     * Requirement: Barcode search does not leak internal warehouse stock counts to other companies.
     */
    public function test_barcode_search_protects_internal_warehouse_stock(): void
    {
        // Retailer searches for Supplier A's product: sees availability, but internal counts are hidden
        $retailerSearch = $this->actingAs($this->retailerAOwner)
            ->getJson("/retailer/barcode/search?barcode={$this->productA->barcode}");

        $retailerSearch->assertStatus(200);
        $retailerSearch->assertJson([
            'found' => true,
            'product' => [
                'id' => $this->productA->id,
                'barcode' => $this->productA->barcode,
                'current_stock' => null,
                'reserved_stock' => null,
                'available_stock' => null,
                'is_available' => true,
            ],
        ]);

        // Supplier A searches their own product: sees full warehouse inventory counts
        $supplierSearch = $this->actingAs($this->supplierAOwner)
            ->getJson("/retailer/barcode/search?barcode={$this->productA->barcode}");

        $supplierSearch->assertStatus(200);
        $supplierSearch->assertJson([
            'found' => true,
            'product' => [
                'id' => $this->productA->id,
                'current_stock' => 100,
                'reserved_stock' => 10,
                'available_stock' => 90,
                'is_available' => true,
            ],
        ]);
    }

    /**
     * Requirement: Request parameter tampering with role or tenant does not grant elevated privileges.
     */
    public function test_request_tampering_role_or_company_id_does_not_grant_access(): void
    {
        // Retailer tries to impersonate platform operator via query parameter
        $response = $this->actingAs($this->retailerAOwner)
            ->get('/?role=operator');

        $response->assertStatus(200);
        // Page view renders as retailer, not operator
        $this->assertEquals('retailer', session('active_role'));
        $this->assertTrue(SecurityAuditLog::where('event_type', 'role_tampering_attempt')->exists());

        // Supplier A tries to impersonate Supplier B via query parameter
        $tamperSupplier = $this->actingAs($this->supplierAOwner)
            ->get("/?active_supplier_id={$this->supplierB->id}");

        $tamperSupplier->assertStatus(200);
        $this->assertEquals($this->supplierA->id, session('active_supplier_id'));
        $this->assertTrue(SecurityAuditLog::where('event_type', 'tenant_tampering_attempt')->exists());
    }

    /**
     * Requirement: User management prevents creating users for other companies or unauthorized escalation.
     */
    public function test_user_management_prevents_cross_company_tampering_and_escalation(): void
    {
        // Supplier A Owner tries to create user under Supplier B
        $resp = $this->actingAs($this->supplierAOwner)
            ->post('/users', [
                'name' => 'Spy User',
                'email' => 'spy@beta.krd',
                'password' => 'secret123',
                'role' => 'staff',
                'company_id' => $this->supplierB->id,
            ]);

        $resp->assertStatus(302);
        // The created user must be strictly forced to Supplier A, never Supplier B
        $createdUser = User::where('email', 'spy@beta.krd')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals($this->supplierA->id, (int)$createdUser->company_id);

        // Supplier A Owner tries to create a Platform Admin
        $escalateResp = $this->actingAs($this->supplierAOwner)
            ->post('/users', [
                'name' => 'Fake Admin',
                'email' => 'fake.admin@krd',
                'password' => 'secret123',
                'role' => 'platform_admin',
            ]);

        $escalateResp->assertStatus(403);
        $this->assertTrue(SecurityAuditLog::where('event_type', 'role_escalation_attempt')->exists());

        // Supplier A Owner tries to toggle status of Supplier B user
        $toggleResp = $this->actingAs($this->supplierAOwner)
            ->post("/users/{$this->supplierBOwner->id}/toggle-status");

        $toggleResp->assertStatus(403);
        $this->assertTrue(SecurityAuditLog::where('event_type', 'idor_user_tampering')->exists());
    }
}
