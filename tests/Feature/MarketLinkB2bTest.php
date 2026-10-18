<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Company;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\B2bConversation;
use App\Models\B2bMessage;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ProductVolumeTier;
use App\Models\User;
use App\Models\SecurityAuditLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MarketLinkB2bTest extends TestCase
{
    use RefreshDatabase;

    private function createPlatformAdminUser(): User
    {
        return User::create([
            'name' => 'Platform Operator Admin',
            'email' => 'admin_' . uniqid() . '@marketlink.krd',
            'password' => bcrypt('password'),
            'role' => 'platform_admin',
            'is_active' => true,
        ]);
    }

    private function createCompanyUser(Company $company, string $role = 'owner'): User
    {
        return User::create([
            'name' => $company->name . ' User',
            'email' => 'user_' . uniqid() . '@marketlink.krd',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_platform_operator_can_register_new_company()
    {
        $admin = $this->createPlatformAdminUser();

        $response = $this->actingAs($admin)->post(route('operator.company.store'), [
            'name' => 'کۆمپانیای دیجلە بۆ بازرگانی کۆمەڵ',
            'business_type' => 'supplier',
            'trade_license_number' => 'TL-ERBIL-9921',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'primary_phone' => '+964 750 333 4455',
            'primary_email' => 'contact@dijla.com',
            'address_line' => 'ناوچەی پیشەسازی باکوور',
        ]);

        $response->assertRedirect(route('marketlink.index', ['role' => 'operator']));
        $this->assertDatabaseHas('companies', [
            'name' => 'کۆمپانیای دیجلە بۆ بازرگانی کۆمەڵ',
            'trade_license_number' => 'TL-ERBIL-9921',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
        ]);
    }

    public function test_retailer_can_send_b2b_chat_message_to_supplier()
    {
        $supplier = Company::create([
            'name' => 'Apex Wholesale',
            'trade_license_number' => 'TL-SUP-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'apex@marketlink.io',
            'primary_phone' => '12345678',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی بازرگانی',
        ]);

        $retailer = Company::create([
            'name' => 'Metro Supermarkets',
            'trade_license_number' => 'TL-RET-01',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'metro@marketlink.io',
            'primary_phone' => '87654321',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی تووی مەلیك',
        ]);

        $retailerUser = $this->createCompanyUser($retailer);

        $conversation = B2bConversation::create([
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'subject' => 'پرسیاری نرخ و داشکاندن',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($retailerUser)->post(route('messages.store'), [
            'conversation_id' => $conversation->id,
            'sender_company_id' => $retailer->id,
            'body' => 'سڵاو، پێویستیمان بە ٥٠٠ کارتۆن لەم بەرهەمەیە، داشکاندنەکەتان چۆن دەبێت؟',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('b2b_messages', [
            'conversation_id' => $conversation->id,
            'sender_company_id' => $retailer->id,
            'body' => 'سڵاو، پێویستیمان بە ٥٠٠ کارتۆن لەم بەرهەمەیە، داشکاندنەکەتان چۆن دەبێت؟',
        ]);
    }

    public function test_retailer_can_post_market_demand()
    {
        $retailer = Company::create([
            'name' => 'Metro Supermarkets',
            'trade_license_number' => 'TL-RET-DEMAND',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'metro@marketlink.io',
            'primary_phone' => '87654321',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی تووی مەلیك',
        ]);

        $retailerUser = $this->createCompanyUser($retailer);

        $response = $this->actingAs($retailerUser)->post(route('retailer.demand.store'), [
            'retailer_company_id' => $retailer->id,
            'title' => 'پێویستمان بە ٣٠٠ کارتۆن ڕۆنی زەیتوونە',
            'quantity' => 300,
            'unit' => 'کارتۆن',
            'target_unit_price' => 25.00,
            'notes' => 'کوالێتی پلە یەک',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('market_demands', [
            'retailer_company_id' => $retailer->id,
            'title' => 'پێویستمان بە ٣٠٠ کارتۆن ڕۆنی زەیتوونە',
            'quantity' => 300,
        ]);
    }

    public function test_supplier_can_send_price_proposal_in_chat()
    {
        $supplier = Company::create([
            'name' => 'Apex Wholesale',
            'trade_license_number' => 'TL-SUP-PROP',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'apex@marketlink.io',
            'primary_phone' => '12345678',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی بازرگانی',
        ]);

        $retailer = Company::create([
            'name' => 'Metro Supermarkets',
            'trade_license_number' => 'TL-RET-PROP',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'metro@marketlink.io',
            'primary_phone' => '87654321',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی تووی مەلیك',
        ]);

        $supplierUser = $this->createCompanyUser($supplier);

        $conversation = B2bConversation::create([
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'subject' => 'دانوستاندنی نرخ',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($supplierUser)->post(route('messages.proposal'), [
            'conversation_id' => $conversation->id,
            'sender_company_id' => $supplier->id,
            'proposed_unit_price' => 19.50,
            'min_quantity' => 100,
            'notes' => 'گەیاندنی خێرا',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('b2b_messages', [
            'conversation_id' => $conversation->id,
            'sender_company_id' => $supplier->id,
            'message_type' => 'quote_proposal',
        ]);
    }

    public function test_supplier_dispatch_and_retailer_inspection_receipt_and_debt_tracking()
    {
        $supplier = Company::create([
            'name' => 'Apex Wholesale',
            'trade_license_number' => 'TL-SUP-FULFILL',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'apex@marketlink.io',
            'primary_phone' => '12345678',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی بازرگانی',
        ]);

        $retailer = Company::create([
            'name' => 'Metro Supermarkets',
            'trade_license_number' => 'TL-RET-FULFILL',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'metro@marketlink.io',
            'primary_phone' => '87654321',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی تووی مەلیك',
        ]);

        $supplierUser = $this->createCompanyUser($supplier);
        $retailerUser = $this->createCompanyUser($retailer);

        $order = PurchaseOrder::create([
            'po_number' => 'PO-TEST-1001',
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier->id,
            'status' => 'issued',
            'subtotal_amount' => 1500.00,
            'total_amount' => 1500.00,
            'paid_amount' => 0.00,
            'remaining_debt' => 1500.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'Net 30 Days Direct Settlement',
            'delivery_address' => 'سلێمانی، شەقامی بازرگانی',
        ]);

        // 1. Supplier prepares and dispatches goods with waybill
        $dispatchResp = $this->actingAs($supplierUser)->post(route('orders.dispatch', $order->id), [
            'driver_name' => 'ئاسۆ کەریم',
            'driver_phone' => '+964 770 123 4567',
            'vehicle_plate' => 'سلێمانی 54321',
            'supplier_notes' => 'کارتۆنەکان بە تەواوی ئامادەکران',
        ]);
        $dispatchResp->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('dispatched', $order->status);
        $this->assertNotNull($order->waybill_number);
        $this->assertEquals('ئاسۆ کەریم', $order->driver_name);

        // 2. Retailer inspects goods, pays $500 cash, leaving $1000 remaining debt
        $receiveResp = $this->actingAs($retailerUser)->post(route('orders.receive', $order->id), [
            'inspected_by' => 'کامەران ئەحمەد - سەرپەرشتیاری کۆگا',
            'inspection_notes' => 'کوالیتی زۆر باش بوو، هەموو کارتۆنەکان بێ زیان گەیشتن',
            'paid_amount' => 500.00,
            'payment_method' => 'cash',
        ]);
        $receiveResp->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('delivered', $order->status);
        $this->assertEquals(500.00, (float)$order->paid_amount);
        $this->assertEquals(1000.00, (float)$order->remaining_debt);
        $this->assertEquals('partially_paid', $order->payment_status);
        $this->assertEquals('کامەران ئەحمەد - سەرپەرشتیاری کۆگا', $order->inspected_by);

        // 3. Retailer settles remaining debt by paying $1000
        $settleResp = $this->actingAs($retailerUser)->post(route('orders.pay_debt', $order->id), [
            'payment_amount' => 1000.00,
        ]);
        $settleResp->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(1500.00, (float)$order->paid_amount);
        $this->assertEquals(0.00, (float)$order->remaining_debt);
        $this->assertEquals('paid', $order->payment_status);

        // 4. Printable invoice view works
        $invoiceResp = $this->actingAs($retailerUser)->get(route('orders.invoice', $order->id));
        $invoiceResp->assertStatus(200);
        $invoiceResp->assertSee('PO-TEST-1001');
        $invoiceResp->assertSee($order->waybill_number);
    }

    public function test_user_can_send_image_and_voice_message_in_b2b_chat()
    {
        Storage::fake('public');

        $supplier = Company::create([
            'name' => 'Supplier Media Co',
            'trade_license_number' => 'TL-SUP-MEDIA',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'media_sup@marketlink.io',
            'primary_phone' => '+9647501112233',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی پیشەسازی باکوور',
        ]);

        $retailer = Company::create([
            'name' => 'Retailer Media Co',
            'trade_license_number' => 'TL-RET-MEDIA',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'media_ret@marketlink.io',
            'primary_phone' => '+9647701112233',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سالم',
        ]);

        $retailerUser = $this->createCompanyUser($retailer);
        $supplierUser = $this->createCompanyUser($supplier);

        $conversation = B2bConversation::create([
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'status' => 'active',
        ]);

        // 1. Send image message
        $imageFile = UploadedFile::fake()->create('product_sample.png', 100, 'image/png');
        $responseImg = $this->actingAs($retailerUser)->post(route('messages.store'), [
            'conversation_id' => $conversation->id,
            'sender_company_id' => $retailer->id,
            'body' => 'ئەمەش نموونەی وێنەی کاڵای داواکراو',
            'image' => $imageFile,
        ]);
        $responseImg->assertSessionHas('success');
        $this->assertDatabaseHas('b2b_messages', [
            'conversation_id' => $conversation->id,
            'sender_company_id' => $retailer->id,
            'message_type' => 'image',
        ]);

        // 2. Send voice note message
        $voiceFile = UploadedFile::fake()->create('voice_note.webm', 120, 'audio/webm');
        $responseVoice = $this->actingAs($supplierUser)->post(route('messages.store'), [
            'conversation_id' => $conversation->id,
            'sender_company_id' => $supplier->id,
            'voice' => $voiceFile,
        ]);
        $responseVoice->assertSessionHas('success');
        $this->assertDatabaseHas('b2b_messages', [
            'conversation_id' => $conversation->id,
            'sender_company_id' => $supplier->id,
            'message_type' => 'voice',
        ]);

        // 3. Test checkNotifications endpoint for retailer (should see supplier's unread voice note)
        $notifResp = $this->actingAs($retailerUser)->getJson(route('notifications.check', [
            'role' => 'retailer',
            'company_id' => $retailer->id,
        ]));
        $notifResp->assertStatus(200);
        $notifResp->assertJsonPath('total_unread', 1);

        // 4. Test markNotificationsRead endpoint
        $markResp = $this->actingAs($retailerUser)->postJson(route('notifications.mark_read'), [
            'role' => 'retailer',
            'company_id' => $retailer->id,
        ]);
        $markResp->assertStatus(200);
        $markResp->assertJson(['success' => true]);

        // Unread count should now be 0
        $notifResp2 = $this->actingAs($retailerUser)->getJson(route('notifications.check', [
            'role' => 'retailer',
            'company_id' => $retailer->id,
        ]));
        $notifResp2->assertJsonPath('total_unread', 0);
    }

    public function test_company_directory_can_filter_by_province_and_category()
    {
        Company::create([
            'name' => 'مارکێتی هەڵۆی هەولێر',
            'trade_license_number' => 'TL-ERB-01',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'erbil@market.com',
            'primary_phone' => '07501234567',
            'country' => 'هەرێمی کوردستان',
            'province' => 'هەولێر',
            'city' => 'هەولێر',
            'business_category' => 'هایپەرمارکێت',
            'address_line' => 'شەقامی ٦٠ مەتری',
        ]);

        Company::create([
            'name' => 'مارکێتی سلێمانی نوێ',
            'trade_license_number' => 'TL-SUL-02',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'suli@market.com',
            'primary_phone' => '07701234567',
            'country' => 'هەرێمی کوردستان',
            'province' => 'سلێمانی',
            'city' => 'سلێمانی',
            'business_category' => 'مینی مارکێت',
            'address_line' => 'سەروەری',
        ]);

        $supplier = Company::create([
            'name' => 'دابینکەری تاقیکردنەوە',
            'trade_license_number' => 'TL-DIR-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'dir_supp@market.com',
            'primary_phone' => '07509876543',
            'country' => 'هەرێمی کوردستان',
            'province' => 'هەولێر',
            'city' => 'هەولێر',
            'address_line' => 'ناوچەی پیشەسازی باکوور',
        ]);
        $supplierUser = $this->createCompanyUser($supplier, 'owner');

        // Filter by Erbil province
        $resp = $this->actingAs($supplierUser)->get('/?role=supplier&filter_province=' . urlencode('هەولێر'));
        $resp->assertStatus(200);
        $directoryRetailers = $resp->viewData('directoryRetailers');
        $this->assertTrue($directoryRetailers->contains('name', 'مارکێتی هەڵۆی هەولێر'));
        $this->assertFalse($directoryRetailers->contains('name', 'مارکێتی سلێمانی نوێ'));
    }

    public function test_multi_supplier_cart_checkout_splits_orders_and_applies_tiered_pricing()
    {
        $supplier1 = Company::create([
            'name' => 'کۆمپانیای ئاراس خۆراک',
            'trade_license_number' => 'TL-SUP-011',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'aras@food.com',
            'primary_phone' => '07501111111',
            'country' => 'هەرێمی کوردستان',
            'province' => 'هەولێر',
            'city' => 'هەولێر',
            'address_line' => 'ناوچەی پیشەسازی',
        ]);

        $supplier2 = Company::create([
            'name' => 'کۆمپانیای دیاری شیرەمەنی',
            'trade_license_number' => 'TL-SUP-022',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'dyari@dairy.com',
            'primary_phone' => '07702222222',
            'country' => 'هەرێمی کوردستان',
            'province' => 'سلێمانی',
            'city' => 'سلێمانی',
            'address_line' => 'ڕاپەڕین',
        ]);

        $retailer = Company::create([
            'name' => 'هایپەرمارکێتی کاروان',
            'trade_license_number' => 'TL-RET-033',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'karwan@hyper.com',
            'primary_phone' => '07503333333',
            'country' => 'هەرێمی کوردستان',
            'province' => 'هەولێر',
            'city' => 'هەولێر',
            'address_line' => 'بەختیاری',
        ]);

        $retailerUser = $this->createCompanyUser($retailer);

        $cat = ProductCategory::create([
            'name_en' => 'Groceries',
            'name_ku' => 'خۆراک',
            'slug' => 'groceries-test',
        ]);

        $p1 = Product::create([
            'supplier_company_id' => $supplier1->id,
            'category_id' => $cat->id,
            'sku' => 'OIL-001',
            'barcode' => '8690111222',
            'title' => 'ڕۆنی زەیتوون ٥ لیتر',
            'base_wholesale_price' => 20.00,
            'minimum_order_quantity' => 5,
            'current_stock' => 100,
        ]);

        ProductVolumeTier::create([
            'product_id' => $p1->id,
            'min_quantity' => 10,
            'unit_price' => 18.00,
        ]);

        $p2 = Product::create([
            'supplier_company_id' => $supplier2->id,
            'category_id' => $cat->id,
            'sku' => 'CHE-002',
            'barcode' => '8690333444',
            'title' => 'پەنیری کوردی سێت',
            'base_wholesale_price' => 15.00,
            'minimum_order_quantity' => 2,
            'current_stock' => 50,
        ]);

        $cartItems = [
            ['product_id' => $p1->id, 'quantity' => 10],
            ['product_id' => $p2->id, 'quantity' => 4],
        ];

        $response = $this->actingAs($retailerUser)->post(route('retailer.cart.checkout'), [
            'retailer_company_id' => $retailer->id,
            'payment_terms' => 'credit_30',
            'cart_items' => json_encode($cartItems),
        ]);

        $response->assertRedirect(route('marketlink.index', ['role' => 'retailer']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('purchase_orders', [
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier1->id,
            'status' => 'pending',
            'total_amount' => 180.00,
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier2->id,
            'status' => 'pending',
            'total_amount' => 60.00,
        ]);
    }

    public function test_full_order_status_lifecycle_and_audit_logs()
    {
        $supplier = Company::create([
            'name' => 'کۆمپانیای بەرهەم',
            'trade_license_number' => 'TL-SUP-FLOW',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'barham@sup.com',
            'primary_phone' => '07509999999',
            'country' => 'هەرێمی کوردستان',
            'province' => 'هەولێر',
            'city' => 'هەولێر',
            'address_line' => 'ناوچەی بازرگانی',
        ]);

        $retailer = Company::create([
            'name' => 'سوپەرمارکێتی ئالان',
            'trade_license_number' => 'TL-RET-FLOW',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'alan@market.com',
            'primary_phone' => '07508888888',
            'country' => 'هەرێمی کوردستان',
            'province' => 'هەولێر',
            'city' => 'هەولێر',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $supplierUser = $this->createCompanyUser($supplier);
        $retailerUser = $this->createCompanyUser($retailer);

        $cat = ProductCategory::create([
            'name_en' => 'Drinks',
            'name_ku' => 'خواردنەوەکان',
            'slug' => 'drinks-flow',
        ]);

        $product = Product::create([
            'supplier_company_id' => $supplier->id,
            'category_id' => $cat->id,
            'sku' => 'JUC-01',
            'barcode' => '7788990011',
            'title' => 'شەربەتی سروشتی پرتەقاڵ',
            'base_wholesale_price' => 10.00,
            'minimum_order_quantity' => 1,
            'current_stock' => 100,
            'reserved_stock' => 0,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-FLOW-101',
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier->id,
            'status' => 'pending',
            'subtotal_amount' => 200.00,
            'total_amount' => 200.00,
            'paid_amount' => 0.00,
            'remaining_debt' => 200.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'credit_30',
            'delivery_address' => 'ناونیشانی مارکێت لە هەولێر',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'product_title' => $product->title,
            'sku' => $product->sku,
            'quantity_ordered' => 20,
            'unit_price' => 10.00,
            'total_price' => 200.00,
        ]);

        // Step 2: Supplier approves and reserves stock
        $respApprove = $this->actingAs($supplierUser)->post(route('orders.approve', $po->id));
        $respApprove->assertSessionHas('success');
        $po->refresh();
        $product->refresh();
        $this->assertEquals('approved', $po->status);
        $this->assertTrue((bool)$po->stock_reserved);
        $this->assertEquals(20, $product->reserved_stock);

        // Step 3: Warehouse processing
        $respProcess = $this->actingAs($supplierUser)->post(route('orders.process', $po->id));
        $respProcess->assertSessionHas('success');
        $po->refresh();
        $this->assertEquals('processing', $po->status);

        // Step 4: Dispatch to Driver
        $respDispatch = $this->actingAs($supplierUser)->post(route('orders.dispatch', $po->id), [
            'driver_name' => 'سەردار محەمەد',
            'driver_phone' => '07505554433',
            'vehicle_plate' => 'هەولێر 98765',
        ]);
        $respDispatch->assertSessionHas('success');
        $po->refresh();
        $this->assertEquals('dispatched', $po->status);
        $this->assertNotNull($po->delivery_otp);
        $this->assertEquals(6, strlen($po->delivery_otp));

        // Step 5: Deliver & Verify with OTP & damage handling
        $respDeliver = $this->actingAs($retailerUser)->post(route('orders.deliver_verify', $po->id), [
            'delivery_otp' => $po->delivery_otp,
            'inspected_by' => 'ئالان ئەحمەد',
            'damage_amount' => 20.00,
            'damage_notes' => 'دوو دانە بەهۆی بارانەوە تێکچووبوو',
        ]);
        $respDeliver->assertSessionHas('success');
        $po->refresh();
        $this->assertEquals('delivered', $po->status);
        $this->assertEquals(180.00, (float)$po->total_amount);
        $this->assertEquals(20.00, (float)$po->damage_amount);

        // Step 6: Complete & Settle Financials
        $respComplete = $this->actingAs($supplierUser)->post(route('orders.complete_settle', $po->id), [
            'payment_method' => 'Credit Net 30 Days',
        ]);
        $respComplete->assertSessionHas('success');
        $po->refresh();
        $product->refresh();
        $this->assertEquals('completed', $po->status);
        $this->assertEquals(80, $product->current_stock);
        $this->assertEquals(0, $product->reserved_stock);
        $this->assertNotNull($po->credit_due_date);

        // Step 7: Audit trail check
        $this->assertGreaterThanOrEqual(5, $po->statusLogs()->count());
        $respLogs = $this->actingAs($retailerUser)->get(route('orders.audit_logs', $po->id));
        $respLogs->assertStatus(200);
        $respLogs->assertJsonFragment(['current_status' => 'completed']);
    }

    public function test_barcode_search_api_returns_product_details()
    {
        $supplier = Company::create([
            'name' => 'دابینکەری بارکۆد',
            'trade_license_number' => 'TL-SUP-BAR',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'bar@sup.com',
            'primary_phone' => '07501239876',
            'country' => 'هەرێمی کوردستان',
            'province' => 'هەولێر',
            'city' => 'هەولێر',
            'address_line' => 'کۆگا',
        ]);

        $cat = ProductCategory::create([
            'name_en' => 'Dairy',
            'name_ku' => 'شیرەمەنی',
            'slug' => 'dairy-bar',
        ]);

        Product::create([
            'supplier_company_id' => $supplier->id,
            'category_id' => $cat->id,
            'sku' => 'YOG-BAR-99',
            'barcode' => '869999888811',
            'title' => 'ماستی خۆماڵی ١ کیلۆ',
            'base_wholesale_price' => 3.50,
            'minimum_order_quantity' => 10,
            'current_stock' => 150,
        ]);

        $retailer = Company::create([
            'name' => 'مارکێتی بارکۆد',
            'trade_license_number' => 'TL-RET-BAR',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'retbar@market.com',
            'primary_phone' => '07501239877',
            'country' => 'هەرێمی کوردستان',
            'province' => 'هەولێر',
            'city' => 'هەولێر',
            'address_line' => 'دوکان',
        ]);
        $retailerUser = $this->createCompanyUser($retailer, 'owner');

        $resp = $this->actingAs($retailerUser)->get(route('retailer.barcode.search', ['barcode' => '869999888811']));
        $resp->assertStatus(200);
        $resp->assertJsonFragment([
            'barcode' => '869999888811',
            'title' => 'ماستی خۆماڵی ١ کیلۆ',
            'minimum_order_quantity' => 10,
        ]);
    }

    public function test_driver_can_depart_from_warehouse_and_complete_handover_with_otp(): void
    {
        $supplier = Company::create([
            'name' => 'دابینکەری هێمن',
            'trade_license_number' => 'TL-DRV-SUP',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'driver-sup@test.com',
            'primary_phone' => '07501112233',
            'country' => 'هەرێمی کوردستان',
            'province' => 'هەولێر',
            'city' => 'هەولێر',
            'address_line' => 'کۆگای ناوەندی',
        ]);

        $retailer = Company::create([
            'name' => 'مارکێتی شاهانە',
            'trade_license_number' => 'TL-DRV-RET',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'driver-ret@test.com',
            'primary_phone' => '07504445566',
            'country' => 'هەرێمی کوردستان',
            'province' => 'هەولێر',
            'city' => 'هەولێر',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $supplierUser = $this->createCompanyUser($supplier);
        $driverUser = User::create([
            'name' => 'ئاکۆ کاکە (شۆفێر)',
            'email' => 'driver_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'company_id' => $supplier->id,
            'role' => 'driver',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-DRV1',
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier->id,
            'driver_id' => $driverUser->id,
            'status' => 'processing',
            'currency' => 'USD',
            'subtotal_amount' => 500.00,
            'total_amount' => 500.00,
            'paid_amount' => 0.00,
            'remaining_debt' => 500.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'cash_on_delivery',
            'delivery_address' => 'ناونیشانی مارکێتی شاهانە',
        ]);

        // Driver departs from warehouse
        $departResp = $this->actingAs($supplierUser)->post(route('orders.dispatch', $po->id), [
            'role' => 'driver',
            'driver_name' => 'ئاکۆ کاکە (شۆفێر)',
            'driver_phone' => '07509876543',
            'vehicle_plate' => 'هەولێر ١٢٣٤ بارهەڵگر',
            'supplier_notes' => 'بارکرا و لە کۆگا بەڕێکەوت بەرەو مارکێت.',
        ]);

        $departResp->assertRedirect();
        $po->refresh();

        $this->assertEquals('dispatched', $po->status);
        $this->assertEquals('ئاکۆ کاکە (شۆفێر)', $po->driver_name);
        $this->assertNotNull($po->dispatched_at);
        $this->assertNotNull($po->waybill_number);
        $this->assertNotNull($po->delivery_otp);

        // Handover by driver with OTP
        $handoverResp = $this->actingAs($driverUser)->postJson(route('orders.deliver_verify', $po->id), [
            'delivery_otp' => $po->delivery_otp,
            'damage_amount' => 0,
            'damage_notes' => '',
            'delivery_signature' => 'data:image/png;base64,fakeSig',
        ]);

        $handoverResp->assertStatus(200);
        $handoverResp->assertJsonFragment(['success' => true]);
        $po->refresh();
        $this->assertEquals('delivered', $po->status);
    }

    public function test_operator_can_batch_register_multiple_companies_and_locations()
    {
        $admin = $this->createPlatformAdminUser();

        $batch = [
            [
                'name' => 'کۆمپانیای بازرگانی گشتی (سەرەکی)',
                'business_type' => 'supplier',
                'province' => 'هەولێر',
                'city' => 'هەولێر',
                'address_line' => 'کۆگای ١٠٠ مەتری',
                'primary_phone' => '07501112233',
                'primary_email' => 'sup_hq@test.com',
            ],
            [
                'name' => 'مارکێتی شاهانە - لقی بەختیاری',
                'business_type' => 'retailer',
                'province' => 'هەولێر',
                'city' => 'هەولێر',
                'address_line' => 'گەڕەکی بەختیاری',
                'primary_phone' => '07504445566',
                'primary_email' => 'ret_bakh@test.com',
            ],
            [
                'name' => 'مارکێتی شاهانە - لقی ئیسکان',
                'business_type' => 'retailer',
                'province' => 'هەولێر',
                'city' => 'هەولێر',
                'address_line' => 'شەقامی ئیسکان',
                'primary_phone' => '07507778899',
                'primary_email' => 'ret_iskan@test.com',
            ],
        ];

        $resp = $this->actingAs($admin)->post(route('operator.company.store'), [
            'batch_records' => json_encode($batch)
        ]);

        $resp->assertRedirect();
        $this->assertDatabaseHas('companies', ['name' => 'کۆمپانیای بازرگانی گشتی (سەرەکی)', 'business_type' => 'supplier']);
        $this->assertDatabaseHas('companies', ['name' => 'مارکێتی شاهانە - لقی بەختیاری', 'business_type' => 'retailer']);
        $this->assertDatabaseHas('companies', ['name' => 'مارکێتی شاهانە - لقی ئیسکان', 'business_type' => 'retailer']);
        $this->assertDatabaseHas('supplier_profiles', ['currency' => 'USD']);
        $this->assertDatabaseCount('retailer_profiles', 2);
    }

    public function test_multi_supplier_checkout_groups_items_from_same_company_into_single_invoice()
    {
        $supplier = Company::create([
            'name' => 'Mega Foods Wholesale',
            'trade_license_number' => 'TL-MEGA-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی بازرگانی',
            'primary_phone' => '07501234567',
            'primary_email' => 'mega@test.com',
        ]);

        $retailer = Company::create([
            'name' => 'Family Supermarket',
            'trade_license_number' => 'TL-FAM-01',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
            'primary_phone' => '07507654321',
            'primary_email' => 'family@test.com',
        ]);

        $retailerUser = $this->createCompanyUser($retailer);

        $cat = ProductCategory::create([
            'name_en' => 'Food & Beverages',
            'name_ku' => 'خۆراک و خواردنەوەکان',
            'name_ar' => 'الأغذية والمشروبات',
            'slug' => 'food-beverages',
        ]);

        $p1 = Product::create([
            'supplier_company_id' => $supplier->id,
            'category_id' => $cat->id,
            'sku' => 'FOOD-01',
            'title' => 'برنجی کوردی پلە یەک',
            'base_wholesale_price' => 30.00,
            'minimum_order_quantity' => 5,
            'current_stock' => 100,
        ]);

        $p2 = Product::create([
            'supplier_company_id' => $supplier->id,
            'category_id' => $cat->id,
            'sku' => 'FOOD-02',
            'title' => 'ڕۆنی زەیتوون ٥ لیتری',
            'base_wholesale_price' => 20.00,
            'minimum_order_quantity' => 5,
            'current_stock' => 100,
        ]);

        $resp = $this->actingAs($retailerUser)->postJson(route('retailer.cart.checkout'), [
            'retailer_company_id' => $retailer->id,
            'items' => [
                ['product_id' => $p1->id, 'quantity' => 10],
                ['product_id' => $p2->id, 'quantity' => 10],
            ],
            'payment_method' => 'cash_on_delivery',
        ]);

        $resp->assertStatus(200);
        $resp->assertJsonFragment(['success' => true]);

        $orders = PurchaseOrder::where('retailer_company_id', $retailer->id)
            ->where('supplier_company_id', $supplier->id)
            ->get();

        $this->assertCount(1, $orders);
        $po = $orders->first();
        $this->assertEquals(500.00, (float)$po->total_amount);
        $this->assertCount(2, $po->items);
    }

    public function test_driver_delivery_otp_mismatch_returns_clean_422_json_error()
    {
        $supplier = Company::create([
            'name' => 'City Supplier',
            'trade_license_number' => 'TL-CITY-01',
            'business_type' => 'supplier',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'کۆگای شار',
            'primary_phone' => '123456',
            'primary_email' => 'city@test.com',
        ]);
        $retailer = Company::create([
            'name' => 'Metro Market',
            'trade_license_number' => 'TL-MET-01',
            'business_type' => 'retailer',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی ٦٠ مەتری',
            'primary_phone' => '654321',
            'primary_email' => 'metro@test.com',
        ]);

        $retailerUser = $this->createCompanyUser($retailer);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-OTP-ERR',
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'status' => 'dispatched',
            'delivery_otp' => '654321',
            'payment_terms' => 'Cash on Delivery',
            'delivery_address' => 'شەقامی ٦٠ مەتری',
            'subtotal_amount' => 150.00,
            'total_amount' => 150.00,
            'remaining_debt' => 150.00,
        ]);

        // Wrong OTP via JSON should return 422 JSON, NOT 302 redirect
        $resp = $this->actingAs($retailerUser)->postJson(route('orders.deliver_verify', $po->id), [
            'delivery_otp' => '000000',
        ]);

        $resp->assertStatus(422);
        $resp->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('کۆدی دڵنیایی', $resp->json('message'));

        // Correct OTP succeeds
        $respCorrect = $this->actingAs($retailerUser)->postJson(route('orders.deliver_verify', $po->id), [
            'delivery_otp' => '654321',
        ]);

        $respCorrect->assertStatus(200);
        $respCorrect->assertJsonFragment(['success' => true]);
        $po->refresh();
        $this->assertEquals('delivered', $po->status);
    }

    public function test_consolidated_invoice_has_single_otp_and_creates_delivery_proof_with_discrepancies()
    {
        $supplier = Company::create([
            'name' => 'کۆمپانیای بازرگانی سۆران',
            'trade_license_number' => 'TL-POD-SUP',
            'business_type' => 'supplier',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'کۆگای ناوەندی',
            'primary_phone' => '07501112233',
            'primary_email' => 'soran@pod.com',
        ]);

        $retailer = Company::create([
            'name' => 'مارکێتی کوردستان (سەرەکی)',
            'trade_license_number' => 'TL-POD-RET',
            'business_type' => 'retailer',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی ١٠٠ مەتری',
            'primary_phone' => '07504445566',
            'primary_email' => 'kurd@pod.com',
        ]);

        $supplierUser = $this->createCompanyUser($supplier);
        $retailerUser = $this->createCompanyUser($retailer);
        $driverUser = User::create([
            'name' => 'هەڵمەت شۆفێر',
            'email' => 'driver_soran_' . uniqid() . '@test.com',
            'password' => bcrypt('password'),
            'company_id' => $supplier->id,
            'role' => 'driver',
        ]);

        $cat = ProductCategory::create([
            'name_en' => 'Beverages & Snacks',
            'name_ku' => 'خواردنەوە و سووکەژەم',
            'slug' => 'bev-snacks-pod',
        ]);

        $prodA = Product::create([
            'supplier_company_id' => $supplier->id,
            'category_id' => $cat->id,
            'sku' => 'SNK-001',
            'title' => 'چیپسی کوردی فامیلی',
            'base_wholesale_price' => 12.00,
            'minimum_order_quantity' => 5,
            'current_stock' => 200,
        ]);

        $prodB = Product::create([
            'supplier_company_id' => $supplier->id,
            'category_id' => $cat->id,
            'sku' => 'SNK-002',
            'title' => 'شەربەتی هەناری سروشتی',
            'base_wholesale_price' => 15.00,
            'minimum_order_quantity' => 5,
            'current_stock' => 150,
        ]);

        $cartCheckout = $this->actingAs($retailerUser)->postJson(route('retailer.cart.checkout'), [
            'retailer_company_id' => $retailer->id,
            'items' => [
                ['product_id' => $prodA->id, 'quantity' => 10],
                ['product_id' => $prodB->id, 'quantity' => 10],
            ],
            'payment_method' => 'cash_on_delivery',
        ]);

        $cartCheckout->assertStatus(200);

        $orders = PurchaseOrder::where('retailer_company_id', $retailer->id)
            ->where('supplier_company_id', $supplier->id)
            ->get();

        $this->assertCount(1, $orders);
        $po = $orders->first();
        $this->assertEquals(270.00, (float)$po->total_amount);
        $this->assertEquals(2, $po->items()->count());

        $po->update(['driver_id' => $driverUser->id]);

        // Dispatch order with single 6-digit OTP
        $this->actingAs($supplierUser)->post(route('orders.dispatch', $po->id), [
            'role' => 'driver',
            'driver_name' => 'هەڵمەت شۆفێر',
            'driver_phone' => '07508889900',
            'vehicle_plate' => 'سلێمانی ٥٥٤٤ بارهەڵگر',
        ]);
        $po->refresh();
        $this->assertNotNull($po->delivery_otp);
        $this->assertEquals(6, strlen($po->delivery_otp));

        $itemA = $po->items->firstWhere('product_id', $prodA->id);
        $deliverResp = $this->actingAs($driverUser)->postJson(route('orders.deliver_verify', $po->id), [
            'delivery_otp' => $po->delivery_otp,
            'driver_name' => 'هەڵمەت شۆفێر',
            'inspected_by' => 'ئاکۆ بەڕێوەبەری مارکێت',
            'delivered_latitude' => 36.191200,
            'delivered_longitude' => 44.012500,
            'cash_collected' => 258.00,
            'damage_amount' => 12.00,
            'damage_notes' => 'یەک دانە چیپس بەهۆی بارانەوە تێکچووبوو',
            'delivery_signature' => 'data:image/png;base64,mockSign',
            'discrepancies' => [
                [
                    'item_id' => $itemA->id,
                    'ordered_qty' => 10,
                    'delivered_qty' => 9,
                    'reason' => 'damaged',
                    'notes' => 'کارتۆنێک دڕابوو',
                ]
            ],
        ]);

        $deliverResp->assertStatus(200);
        $deliverResp->assertJsonFragment(['success' => true]);

        $po->refresh();
        $this->assertEquals('delivered', $po->status);
        $this->assertEquals(258.00, (float)$po->total_amount);
        $this->assertEquals(258.00, (float)$po->paid_amount);
        $this->assertEquals(0.00, (float)$po->remaining_debt);

        $this->assertDatabaseHas('delivery_proofs', [
            'purchase_order_id' => $po->id,
            'driver_name' => 'هەڵمەت شۆفێر',
            'received_by_name' => 'ئاکۆ بەڕێوەبەری مارکێت',
            'cash_collected' => 258.00,
            'status' => 'partial_delivery',
        ]);

        $this->assertDatabaseHas('delivery_discrepancies', [
            'purchase_order_item_id' => $itemA->id,
            'ordered_quantity' => 10,
            'delivered_quantity' => 9,
            'reason' => 'damaged',
        ]);
    }

    public function test_orders_csv_export_returns_streamed_csv_with_utf8_bom()
    {
        $supplier = Company::create([
            'name' => 'کۆمپانیای ڕاپۆرتی نموونەیی',
            'trade_license_number' => 'TL-REP-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'report@marketlink.io',
            'primary_phone' => '11223344',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی پیشەسازی',
        ]);

        $retailer = Company::create([
            'name' => 'مارکێتی ڕاپۆرت',
            'trade_license_number' => 'TL-RET-REP',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'ret-report@marketlink.io',
            'primary_phone' => '55667788',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی تووی مەلیك',
        ]);

        $supplierUser = $this->createCompanyUser($supplier);

        PurchaseOrder::create([
            'po_number' => 'PO-REP-9999',
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier->id,
            'status' => 'approved',
            'currency' => 'USD',
            'subtotal_amount' => 1500.00,
            'total_amount' => 1500.00,
            'paid_amount' => 500.00,
            'remaining_debt' => 1000.00,
            'payment_status' => 'partially_paid',
            'payment_terms' => 'Cash on Delivery',
            'delivery_address' => 'هەولێر',
        ]);

        $response = $this->actingAs($supplierUser)->get(route('reports.orders.export', ['supplier_id' => $supplier->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('PO-REP-9999', $content);
        $this->assertStringContainsString('کۆمپانیای ڕاپۆرتی نموونەیی', $content);
    }

    public function test_account_statement_csv_export_returns_streamed_financial_report()
    {
        $company = Company::create([
            'name' => 'کۆمپانیای نەریمان بۆ بازرگانی',
            'trade_license_number' => 'TL-STMT-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'stmt@marketlink.io',
            'primary_phone' => '99887766',
            'city' => 'دهۆک',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $companyUser = $this->createCompanyUser($company);

        $response = $this->actingAs($companyUser)->get(route('reports.statement.export', $company->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('کەشف حیسابی فەرمی — کۆمپانیای نەریمان بۆ بازرگانی', $content);
    }

    public function test_otp_lockout_after_max_failed_attempts()
    {
        $supplier = Company::create([
            'name' => 'Supplier Security Test',
            'trade_license_number' => 'TL-SEC-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'sec@marketlink.io',
            'primary_phone' => '123123',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی پیشەسازی',
        ]);

        $retailer = Company::create([
            'name' => 'Retailer Security Test',
            'trade_license_number' => 'TL-SEC-02',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'sec2@marketlink.io',
            'primary_phone' => '321321',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $retailerUser = $this->createCompanyUser($retailer);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-SEC-001',
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier->id,
            'status' => 'dispatched',
            'delivery_otp' => '987654',
            'subtotal_amount' => 500.00,
            'total_amount' => 500.00,
            'paid_amount' => 0.00,
            'remaining_debt' => 500.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'Cash on Delivery',
            'delivery_address' => 'هەولێر',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $resp = $this->actingAs($retailerUser)->postJson(route('orders.deliver_verify', $po->id), [
                'delivery_otp' => '000000',
            ]);
            $resp->assertStatus(422);
        }

        $respLock = $this->actingAs($retailerUser)->postJson(route('orders.deliver_verify', $po->id), [
            'delivery_otp' => '987654',
        ]);
        $respLock->assertStatus(422);
        $respLock->assertJsonFragment(['locked' => true]);
    }

    public function test_idor_retailer_cannot_view_another_retailers_invoice()
    {
        $victimRetailer = Company::create([
            'name' => 'Victim Market',
            'trade_license_number' => 'TL-VIC-01',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'victim@idor.com',
            'primary_phone' => '111',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $supplier = Company::create([
            'name' => 'Supplier Legit',
            'trade_license_number' => 'TL-LEGIT-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'legit@idor.com',
            'primary_phone' => '222',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $attackerRetailer = Company::create([
            'name' => 'Attacker Market',
            'trade_license_number' => 'TL-ATT-01',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'attacker@idor.com',
            'primary_phone' => '333',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $attackerUser = $this->createCompanyUser($attackerRetailer);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-SECRET-001',
            'retailer_company_id' => $victimRetailer->id,
            'supplier_company_id' => $supplier->id,
            'status' => 'approved',
            'subtotal_amount' => 1000.00,
            'total_amount' => 1000.00,
            'paid_amount' => 0.00,
            'remaining_debt' => 1000.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'Credit',
            'delivery_address' => 'ناونیشانی مارکێت',
        ]);

        // Attacker attempts to view victim's invoice by ID tampering
        $response = $this->actingAs($attackerUser)->get(route('orders.invoice', [
            'id' => $po->id,
            'role' => 'retailer',
            'active_retailer_id' => $attackerRetailer->id,
        ]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'idor_attempt',
            'severity' => 'critical',
        ]);
    }

    public function test_unauthorized_company_cannot_approve_supplier_order()
    {
        $supplier = Company::create([
            'name' => 'True Supplier',
            'trade_license_number' => 'TL-AUTH-SUP',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'sup2@auth.com',
            'primary_phone' => '444',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی بازرگانی',
        ]);

        $retailer = Company::create([
            'name' => 'Ordering Retailer',
            'trade_license_number' => 'TL-AUTH-RET',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'ret2@auth.com',
            'primary_phone' => '555',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $otherSupplier = Company::create([
            'name' => 'Impostor Supplier',
            'trade_license_number' => 'TL-AUTH-IMP',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'imp@auth.com',
            'primary_phone' => '666',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $otherSupplierUser = $this->createCompanyUser($otherSupplier);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-AUTH-002',
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier->id,
            'status' => 'pending',
            'subtotal_amount' => 500.00,
            'total_amount' => 500.00,
            'paid_amount' => 0.00,
            'remaining_debt' => 500.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'Cash',
            'delivery_address' => 'هەولێر',
        ]);

        // Impostor supplier attempts to approve True Supplier's order
        $response = $this->actingAs($otherSupplierUser)->post(route('orders.approve', [
            'id' => $po->id,
            'role' => 'supplier',
            'active_supplier_id' => $otherSupplier->id,
        ]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'unauthorized_tenant_access',
            'severity' => 'critical',
        ]);
    }

    public function test_company_staff_role_cannot_approve_orders_rbac()
    {
        $supplier = Company::create([
            'name' => 'Supplier RBAC',
            'trade_license_number' => 'TL-RBAC-SUP',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'rbac@supplier.com',
            'primary_phone' => '777',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی پیشەسازی',
        ]);

        $retailer = Company::create([
            'name' => 'Retailer RBAC',
            'trade_license_number' => 'TL-RBAC-RET',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'rbac@retailer.com',
            'primary_phone' => '888',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی سەرەکی',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-RBAC-003',
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier->id,
            'status' => 'pending',
            'subtotal_amount' => 300.00,
            'total_amount' => 300.00,
            'paid_amount' => 0.00,
            'remaining_debt' => 300.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'Cash',
            'delivery_address' => 'هەولێر',
        ]);

        $staffUser = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@supplier.com',
            'password' => bcrypt('password123'),
            'company_id' => $supplier->id,
            'role' => 'staff',
        ]);

        $response = $this->actingAs($staffUser)->post(route('orders.approve', $po->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'role_permission_denied',
            'user_id' => $staffUser->id,
        ]);
    }

    public function test_company_cannot_send_message_in_another_tenants_conversation()
    {
        $supplier = Company::create([
            'name' => 'Supplier Alpha',
            'trade_license_number' => 'TL-ALPHA',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'alpha@sup.com',
            'primary_phone' => '999',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقام',
        ]);

        $retailer = Company::create([
            'name' => 'Retailer Beta',
            'trade_license_number' => 'TL-BETA',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'beta@ret.com',
            'primary_phone' => '101',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقام',
        ]);

        $eavesdropper = Company::create([
            'name' => 'Eavesdropper Co',
            'trade_license_number' => 'TL-EAVES',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'eaves@co.com',
            'primary_phone' => '202',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقام',
        ]);

        $eavesdropperUser = $this->createCompanyUser($eavesdropper);

        $conv = B2bConversation::create([
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'subject' => 'گفتوگۆی تایبەت',
            'last_message_at' => now(),
        ]);

        // Eavesdropper attempts to send message pretending to be in this conversation
        $response = $this->actingAs($eavesdropperUser)->post(route('messages.store'), [
            'conversation_id' => $conv->id,
            'sender_company_id' => $eavesdropper->id,
            'body' => 'هەوڵی نەشیاو بۆ دەستوەردان',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'unauthorized_tenant_access',
            'company_id' => $eavesdropper->id,
        ]);
    }

    // =========================================================================
    // ANTI-ATTACK TESTS (Requirement 4): Unauthenticated & Tampering vectors
    // =========================================================================

    public function test_unauthenticated_user_with_role_operator_cannot_register_company()
    {
        $response = $this->post(route('operator.company.store'), [
            'role' => 'operator',
            'name' => 'Hacker Fake Supplier',
            'business_type' => 'supplier',
            'trade_license_number' => 'TL-FAKE-001',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'primary_phone' => '07500000000',
            'primary_email' => 'hacker@fake.com',
        ]);

        // Unauthenticated request must be denied (401 from auth middleware or 403)
        $this->assertTrue(in_array($response->status(), [401, 403]));
        $this->assertDatabaseMissing('companies', ['name' => 'Hacker Fake Supplier']);
    }

    public function test_unauthenticated_user_with_role_operator_cannot_approve_order()
    {
        $supplier = Company::create([
            'name' => 'Supplier Target',
            'trade_license_number' => 'TL-TGT-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'tgt@target.com',
            'primary_phone' => '1111',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی سەرەکی',
        ]);

        $retailer = Company::create([
            'name' => 'Retailer Target',
            'trade_license_number' => 'TL-TGT-02',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'tgt2@target.com',
            'primary_phone' => '2222',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی سەرەکی',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TARGET-001',
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier->id,
            'status' => 'pending',
            'subtotal_amount' => 400.00,
            'total_amount' => 400.00,
            'paid_amount' => 0.00,
            'remaining_debt' => 400.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'Cash',
            'delivery_address' => 'هەولێر',
        ]);

        $response = $this->post(route('orders.approve', [
            'id' => $po->id,
            'role' => 'operator',
        ]));

        $this->assertTrue(in_array($response->status(), [401, 403]));
        $this->assertEquals('pending', $po->fresh()->status);
    }

    public function test_unauthenticated_user_with_role_operator_cannot_view_invoice()
    {
        $supplier = Company::create([
            'name' => 'Supplier Inv Target',
            'trade_license_number' => 'TL-INV-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'inv@target.com',
            'primary_phone' => '3333',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی سەرەکی',
        ]);

        $retailer = Company::create([
            'name' => 'Retailer Inv Target',
            'trade_license_number' => 'TL-INV-02',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'inv2@target.com',
            'primary_phone' => '4444',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی سەرەکی',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-INV-SECRET',
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier->id,
            'status' => 'approved',
            'subtotal_amount' => 800.00,
            'total_amount' => 800.00,
            'paid_amount' => 0.00,
            'remaining_debt' => 800.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'Cash',
            'delivery_address' => 'هەولێر',
        ]);

        $response = $this->get(route('orders.invoice', [
            'id' => $po->id,
            'role' => 'operator',
        ]));

        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    public function test_unauthenticated_user_with_role_operator_cannot_export_reports()
    {
        $company = Company::create([
            'name' => 'Report Target Co',
            'trade_license_number' => 'TL-REPTGT-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'reptgt@test.com',
            'primary_phone' => '5555',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی سەرەکی',
        ]);

        $ordersExportResp = $this->get(route('reports.orders.export', ['role' => 'operator']));
        $this->assertTrue(in_array($ordersExportResp->status(), [401, 403]));

        $stmtExportResp = $this->get(route('reports.statement.export', [
            'companyId' => $company->id,
            'role' => 'operator',
        ]));
        $this->assertTrue(in_array($stmtExportResp->status(), [401, 403]));
    }

    public function test_unauthenticated_user_with_role_operator_cannot_send_chat_message()
    {
        $supplier = Company::create([
            'name' => 'Chat Target Sup',
            'trade_license_number' => 'TL-CHATTGT-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'chatsup@test.com',
            'primary_phone' => '6666',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی سەرەکی',
        ]);

        $retailer = Company::create([
            'name' => 'Chat Target Ret',
            'trade_license_number' => 'TL-CHATTGT-02',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'chatret@test.com',
            'primary_phone' => '7777',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی سەرەکی',
        ]);

        $conv = B2bConversation::create([
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'subject' => 'گفتوگۆ',
            'last_message_at' => now(),
        ]);

        $response = $this->post(route('messages.store'), [
            'role' => 'operator',
            'conversation_id' => $conv->id,
            'sender_company_id' => $retailer->id,
            'body' => 'پەیامی نەناسراو و نایاسایی',
        ]);

        $this->assertTrue(in_array($response->status(), [401, 403]));
        $this->assertDatabaseMissing('b2b_messages', ['body' => 'پەیامی نەناسراو و نایاسایی']);
    }

    public function test_authenticated_user_cannot_spoof_sender_company_id_in_chat()
    {
        $supplier = Company::create([
            'name' => 'Supplier Legit Chat',
            'trade_license_number' => 'TL-LEGIT-CHAT-SUP',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'legitsup@test.com',
            'primary_phone' => '8888',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی بازرگانی',
        ]);

        $retailer = Company::create([
            'name' => 'Retailer Legit Chat',
            'trade_license_number' => 'TL-LEGIT-CHAT-RET',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'legitret@test.com',
            'primary_phone' => '9999',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی بازرگانی',
        ]);

        $attackerCompany = Company::create([
            'name' => 'Attacker Market Spoof',
            'trade_license_number' => 'TL-ATT-SPOOF',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'attacker@spoof.com',
            'primary_phone' => '0000',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقام',
        ]);

        $attackerUser = $this->createCompanyUser($attackerCompany);

        $conv = B2bConversation::create([
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'subject' => 'نهێنی بازرگانی',
            'last_message_at' => now(),
        ]);

        // Attacker user tries to post message claiming to be $retailer (spoofing sender_company_id)
        $response = $this->actingAs($attackerUser)->post(route('messages.store'), [
            'conversation_id' => $conv->id,
            'sender_company_id' => $retailer->id,
            'body' => 'پەیامێکی ساختە بە ناوی مارکێتی ترەوە',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('b2b_messages', ['body' => 'پەیامێکی ساختە بە ناوی مارکێتی ترەوە']);
        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'unauthorized_tenant_access',
            'user_id' => $attackerUser->id,
            'severity' => 'critical',
        ]);
    }

    public function test_authenticated_user_cannot_export_another_company_financial_statement()
    {
        $companyA = Company::create([
            'name' => 'Company A Finance',
            'trade_license_number' => 'TL-COMP-A',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'compa@finance.com',
            'primary_phone' => '1212',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی پیشەسازی',
        ]);

        $companyB = Company::create([
            'name' => 'Company B Finance Victim',
            'trade_license_number' => 'TL-COMP-B',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'compb@finance.com',
            'primary_phone' => '3434',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی بازرگانی',
        ]);

        $userA = $this->createCompanyUser($companyA);

        // User from Company A attempts to export Company B's financial statement
        $response = $this->actingAs($userA)->get(route('reports.statement.export', [
            'companyId' => $companyB->id,
        ]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'idor_attempt',
            'user_id' => $userA->id,
            'severity' => 'critical',
        ]);
    }

    public function test_authenticated_retailer_cannot_tamper_supplier_company_id_to_manage_products()
    {
        $supplier = Company::create([
            'name' => 'Target Supplier Stock',
            'trade_license_number' => 'TL-STK-SUP',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'targetsup@stock.com',
            'primary_phone' => '5656',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'کۆگا',
        ]);

        $retailer = Company::create([
            'name' => 'Malicious Retailer',
            'trade_license_number' => 'TL-MAL-RET',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'malicious@ret.com',
            'primary_phone' => '7878',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'مارکێت',
        ]);

        $retailerUser = $this->createCompanyUser($retailer);

        $cat = ProductCategory::create([
            'name_en' => 'Hardware',
            'name_ku' => 'کەرەستە',
            'slug' => 'hardware-tamper',
        ]);

        // Retailer user tries to create product on behalf of supplier
        $response = $this->actingAs($retailerUser)->post(route('supplier.product.store'), [
            'supplier_company_id' => $supplier->id,
            'category_id' => $cat->id,
            'sku' => 'HACK-01',
            'title' => 'بەرهەمی هاککراو',
            'base_wholesale_price' => 1.00,
            'minimum_order_quantity' => 1,
            'current_stock' => 9999,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('products', ['sku' => 'HACK-01']);
        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'role_permission_denied',
            'user_id' => $retailerUser->id,
        ]);
    }

    public function test_driver_cannot_access_unassigned_delivery_orders()
    {
        $supplier = Company::create([
            'name' => 'Supplier Logistics',
            'trade_license_number' => 'TL-LOG-SUP',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'logsup@test.com',
            'primary_phone' => '9090',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'کۆگا',
        ]);

        $retailer = Company::create([
            'name' => 'Retailer Logistics',
            'trade_license_number' => 'TL-LOG-RET',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'logret@test.com',
            'primary_phone' => '0909',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'مارکێت',
        ]);

        $driverUserA = User::create([
            'name' => 'Driver A Assigned',
            'email' => 'driver_a@test.com',
            'password' => bcrypt('password'),
            'company_id' => $supplier->id,
            'role' => 'driver',
        ]);

        $driverUserB = User::create([
            'name' => 'Driver B Impostor',
            'email' => 'driver_b@test.com',
            'password' => bcrypt('password'),
            'company_id' => $supplier->id,
            'role' => 'driver',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-LOG-ASSIGNED',
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'driver_id' => $driverUserA->id,
            'status' => 'dispatched',
            'delivery_otp' => '112233',
            'subtotal_amount' => 300.00,
            'total_amount' => 300.00,
            'remaining_debt' => 300.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'Cash',
            'delivery_address' => 'هەولێر',
        ]);

        // Driver B attempts to deliver order assigned to Driver A
        $response = $this->actingAs($driverUserB)->postJson(route('orders.deliver_verify', $po->id), [
            'delivery_otp' => '112233',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('dispatched', $po->fresh()->status);
        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'unauthorized_tenant_access',
            'user_id' => $driverUserB->id,
        ]);
    }

    public function test_authenticated_retailer_with_role_operator_in_request_cannot_access_platform_admin()
    {
        $retailer = Company::create([
            'name' => 'Privilege Escalation Market',
            'trade_license_number' => 'TL-PRV-RET',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'priv@market.com',
            'primary_phone' => '1313',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقام',
        ]);

        $retailerUser = $this->createCompanyUser($retailer);

        // Retailer tries to register a company pretending to be an operator via request parameters
        $response = $this->actingAs($retailerUser)->post(route('operator.company.store'), [
            'role' => 'operator',
            'name' => 'Illegitimate Escalate Co',
            'business_type' => 'supplier',
            'trade_license_number' => 'TL-ESCALATE-01',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'primary_phone' => '07501112233',
            'primary_email' => 'escalate@fake.com',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('companies', ['name' => 'Illegitimate Escalate Co']);
        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'unauthorized_platform_admin_access',
            'user_id' => $retailerUser->id,
            'severity' => 'critical',
        ]);
    }

    public function test_approved_order_appears_for_driver_and_can_be_dispatched()
    {
        $supplier = Company::create([
            'name' => 'Koran Wholesale Co',
            'trade_license_number' => 'TL-APPR-SUP',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'koran@supplier.krd',
            'primary_phone' => '07501234567',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی پیشەسازی',
        ]);

        $retailer = Company::create([
            'name' => 'Hawler City Supermarket',
            'trade_license_number' => 'TL-APPR-RET',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'hawler@market.krd',
            'primary_phone' => '07507654321',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $driver = User::create([
            'name' => 'Logistics Driver Omar',
            'email' => 'driver_omar@marketlink.krd',
            'password' => bcrypt('password'),
            'company_id' => null,
            'role' => 'driver',
            'is_active' => true,
        ]);

        // Purchase order newly approved by supplier
        $po = PurchaseOrder::create([
            'po_number' => 'PO-APPR-TEST-99',
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'driver_id' => null,
            'status' => 'approved',
            'subtotal_amount' => 500.00,
            'total_amount' => 500.00,
            'remaining_debt' => 500.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'Cash on Delivery',
            'delivery_address' => 'هەولێر - شەقامی ٦٠ مەتری',
        ]);

        // 1. Driver visits dashboard -> Order MUST appear in driver shipments
        $response = $this->actingAs($driver)->get(route('marketlink.index', ['role' => 'driver']));
        $response->assertOk();
        $response->assertSee('PO-APPR-TEST-99');
        $response->assertSee('Hawler City Supermarket');
        $response->assertSee('ئامادەیە لە کۆگا');

        // 2. Driver clicks depart from warehouse
        $dispatchResponse = $this->actingAs($driver)->post(route('orders.dispatch', $po->id), [
            'driver_name' => 'Logistics Driver Omar',
            'driver_phone' => '07509998877',
            'vehicle_plate' => '22 A 99999',
            'role' => 'driver',
        ]);

        $dispatchResponse->assertSessionHas('success');
        $freshPo = $po->fresh();
        $this->assertEquals('dispatched', $freshPo->status);
        $this->assertEquals($driver->id, $freshPo->driver_id);
        $this->assertNotEmpty($freshPo->delivery_otp);
    }

    public function test_retailer_location_saved_and_driver_navigation_url_generated()
    {
        $admin = $this->createPlatformAdminUser();

        // 1. Operator registers new retailer with GPS coordinates
        $response = $this->actingAs($admin)->post(route('operator.company.store'), [
            'name' => 'مارکێتی هەڵۆی نوێ',
            'business_type' => 'retailer',
            'trade_license_number' => 'TL-RET-GPS-9988',
            'city' => 'سلێمانی',
            'province' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'primary_phone' => '+964 770 123 4567',
            'primary_email' => 'halo@market.krd',
            'address_line' => 'سەرچنار بەرامبەر باخی گشتی',
            'latitude' => 35.5802,
            'longitude' => 45.3905,
            'google_maps_url' => 'https://maps.app.goo.gl/example123',
        ]);

        $response->assertRedirect(route('marketlink.index', ['role' => 'operator']));
        $retailer = Company::where('trade_license_number', 'TL-RET-GPS-9988')->first();
        $this->assertNotNull($retailer);
        $this->assertEquals(35.5802, $retailer->latitude);
        $this->assertEquals(45.3905, $retailer->longitude);
        $this->assertStringContainsString('35.5802,45.3905', $retailer->navigation_url);

        // 2. Create supplier and product
        $supplier = Company::create([
            'name' => 'دابینکەری کۆمەڵ',
            'business_type' => 'supplier',
            'trade_license_number' => 'TL-SUP-GPS-7766',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'primary_phone' => '+964 750 111 2233',
            'primary_email' => 'sup@gps.krd',
            'address_line' => 'ناوچەی پیشەسازی',
            'verification_status' => 'verified',
            'account_status' => 'active',
        ]);

        $cat = ProductCategory::create(['slug' => 'drinks', 'name_en' => 'Drinks', 'name_ar' => 'مشروبات', 'name_ku' => 'خواردنەوەکان']);
        $product = Product::create([
            'supplier_company_id' => $supplier->id,
            'category_id' => $cat->id,
            'title' => 'ئاوی کانزایی',
            'sku' => 'WATER-001',
            'base_wholesale_price' => 2.50,
            'minimum_order_quantity' => 10,
            'stock_available' => 500,
            'status' => 'active',
        ]);

        // 3. Retailer orders items
        $retailerUser = $this->createCompanyUser($retailer, 'owner');
        $orderRes = $this->actingAs($retailerUser)->postJson(route('retailer.cart.checkout'), [
            'retailer_company_id' => $retailer->id,
            'payment_terms' => 'cash_on_delivery',
            'delivery_address' => 'سلێمانی - سەرچنار',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 20],
            ],
        ]);
        $orderRes->assertJson(['success' => true]);

        $po = PurchaseOrder::where('retailer_company_id', $retailer->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals(35.5802, $po->delivery_latitude);
        $this->assertEquals(45.3905, $po->delivery_longitude);
        $this->assertStringContainsString('35.5802,45.3905', $po->navigation_url);

        // 4. Driver loads / dispatches cargo and gets navigation link
        $driver = User::create([
            'name' => 'Driver Karwan',
            'email' => 'driver_karwan@marketlink.krd',
            'password' => bcrypt('password'),
            'role' => 'driver',
        ]);

        $dispatchJson = $this->actingAs($driver)->postJson(route('orders.dispatch', $po->id), [
            'driver_name' => 'Karwan Ahmed',
            'driver_phone' => '07501234567',
            'vehicle_plate' => '21 B 12345',
            'role' => 'driver',
        ]);

        $dispatchJson->assertJson([
            'success' => true,
            'status' => 'dispatched',
        ]);

        $resData = $dispatchJson->json();
        $this->assertNotEmpty($resData['navigation_url']);
        $this->assertStringContainsString('35.5802,45.3905', $resData['navigation_url']);
        $this->assertStringContainsString('travelmode=driving', $resData['navigation_url']);
    }
}


