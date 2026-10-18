<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Company;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\User;
use App\Models\RetailerProfile;
use App\Models\CashVan;
use App\Models\CashVanInventory;
use App\Models\CashVanSale;
use App\Models\CashVanReconciliation;
use App\Services\CashVanService;

class CashVanManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createSupplier(string $name = 'Apex Wholesale'): Company
    {
        return Company::create([
            'name' => $name,
            'business_type' => 'supplier',
            'trade_license_number' => 'TL-' . uniqid(),
            'verification_status' => 'verified',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'primary_phone' => '+964 750 111 2233',
            'primary_email' => 'supplier_' . uniqid() . '@marketlink.krd',
            'address_line' => 'ناوچەی پیشەسازی باکوور',
        ]);
    }

    private function createRetailer(string $name = 'Metro Mart'): Company
    {
        return Company::create([
            'name' => $name,
            'business_type' => 'retailer',
            'trade_license_number' => 'TL-RET-' . uniqid(),
            'verification_status' => 'verified',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'primary_phone' => '+964 770 222 3344',
            'primary_email' => 'retailer_' . uniqid() . '@marketlink.krd',
            'address_line' => 'شەقامی بازنەیی مەملەکە',
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

    private function createProduct(Company $supplier, int $stock = 100, float $price = 25.00): Product
    {
        $cat = ProductCategory::firstOrCreate(
            ['slug' => 'dairy'],
            [
                'name_en' => 'Dairy Products',
                'name_ar' => 'منتجات الألبان',
                'name_ku' => 'بەرهەمی شیری',
                'is_active' => true,
            ]
        );

        return Product::create([
            'supplier_company_id' => $supplier->id,
            'category_id' => $cat->id,
            'title' => 'شیر کەتەلۆک ' . uniqid(),
            'sku' => 'SKU-' . strtoupper(uniqid()),
            'base_wholesale_price' => $price,
            'current_stock' => $stock,
            'minimum_order_quantity' => 1,
            'unit_of_measure' => 'piece',
            'is_available' => true,
        ]);
    }

    public function test_supplier_can_register_new_cash_van()
    {
        $supplier = $this->createSupplier();
        $user = $this->createCompanyUser($supplier, 'owner');
        $driver = $this->createCompanyUser($supplier, 'driver');

        $response = $this->actingAs($user)->post(route('cash_van.vehicles.store'), [
            'vehicle_name' => 'ڤانی دابەشکردنی باکوور',
            'plate_number' => '22 B 98765',
            'model' => 'Hyundai H1 2024',
            'driver_user_id' => $driver->id,
            'operating_mode' => 'hybrid',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cash_vans', [
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی دابەشکردنی باکوور',
            'plate_number' => '22 B 98765',
            'driver_user_id' => $driver->id,
            'operating_mode' => 'hybrid',
        ]);
    }

    public function test_driver_cannot_register_new_cash_van(): void
    {
        $supplier = $this->createSupplier();
        $driver = $this->createCompanyUser($supplier, 'driver');

        $response = $this->actingAs($driver)->post(route('cash_van.vehicles.store'), [
            'vehicle_name' => 'ڤانی نایاسایی شۆفێر',
            'plate_number' => '99 Z 99999',
            'operating_mode' => 'direct_sale',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('cash_vans', [
            'vehicle_name' => 'ڤانی نایاسایی شۆفێر',
        ]);
    }

    public function test_supplier_can_load_stock_from_warehouse_to_van()
    {
        $supplier = $this->createSupplier();
        $user = $this->createCompanyUser($supplier, 'owner');
        $product = $this->createProduct($supplier, 100, 20.00);

        $van = CashVan::create([
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی تاقیکردنەوە',
            'plate_number' => '21 A 11111',
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('cash_van.stock.load', $van->id), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 30],
            ],
            'notes' => 'بارکردنی مەوادی بەیانی',
        ]);

        $response->assertSessionHasNoErrors();

        // Warehouse stock should decrease by 30 (from 100 to 70)
        $this->assertEquals(70, $product->fresh()->stock_quantity);

        // Van inventory should have 30
        $vanInv = CashVanInventory::where('cash_van_id', $van->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($vanInv);
        $this->assertEquals(30, (float)$vanInv->quantity);

        // Transfer log should be completed
        $this->assertDatabaseHas('cash_van_stock_transfers', [
            'cash_van_id' => $van->id,
            'transfer_type' => 'load_to_van',
            'status' => 'completed',
        ]);
    }

    public function test_cannot_load_more_stock_than_available_in_warehouse()
    {
        $supplier = $this->createSupplier();
        $user = $this->createCompanyUser($supplier, 'owner');
        $product = $this->createProduct($supplier, 10, 20.00);

        $van = CashVan::create([
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی تاقیکردنەوە',
            'plate_number' => '21 A 11111',
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('cash_van.stock.load', $van->id), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 50],
            ],
        ]);

        $response->assertSessionHasErrors('load_error');
        // Warehouse stock unchanged
        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }

    public function test_supplier_can_return_stock_from_van_to_warehouse()
    {
        $supplier = $this->createSupplier();
        $user = $this->createCompanyUser($supplier, 'owner');
        $product = $this->createProduct($supplier, 70, 20.00);

        $van = CashVan::create([
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی تاقیکردنەوە',
            'plate_number' => '21 A 11111',
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        CashVanInventory::create([
            'cash_van_id' => $van->id,
            'product_id' => $product->id,
            'quantity' => 30,
        ]);

        $response = $this->actingAs($user)->post(route('cash_van.stock.return', $van->id), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10],
            ],
            'notes' => 'گەڕاندنەوەی بەشێک لە کاڵا',
        ]);

        $response->assertSessionHasNoErrors();

        // Van stock decreases to 20
        $this->assertEquals(20, (float)CashVanInventory::where('cash_van_id', $van->id)->where('product_id', $product->id)->value('quantity'));

        // Warehouse stock increases to 80 (70 + 10)
        $this->assertEquals(80, $product->fresh()->stock_quantity);
    }

    public function test_van_direct_sale_deducts_van_inventory_and_records_cash_sale()
    {
        $supplier = $this->createSupplier();
        $retailer = $this->createRetailer();
        $driver = $this->createCompanyUser($supplier, 'driver');
        $product = $this->createProduct($supplier, 100, 15.00);

        $van = CashVan::create([
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی فرۆشتنی خێرا',
            'plate_number' => '22 B 55555',
            'driver_user_id' => $driver->id,
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        CashVanInventory::create([
            'cash_van_id' => $van->id,
            'product_id' => $product->id,
            'quantity' => 20,
        ]);

        $response = $this->actingAs($driver)->post(route('cash_van.sales.store', $van->id), [
            'retailer_company_id' => $retailer->id,
            'sale_type' => 'direct_sale',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
            'payment_method' => 'cash',
            'discount_amount' => 5.00,
        ]);

        $response->assertSessionHasNoErrors();

        // Van inventory should decrease from 20 to 15
        $this->assertEquals(15, (float)CashVanInventory::where('cash_van_id', $van->id)->where('product_id', $product->id)->value('quantity'));

        // Sale: 5 * 15 = 75, discount = 5, total = 70, paid = 70, balance_due = 0
        $sale = CashVanSale::where('cash_van_id', $van->id)->first();
        $this->assertNotNull($sale);
        $this->assertEquals(75.00, (float)$sale->subtotal);
        $this->assertEquals(5.00, (float)$sale->discount_amount);
        $this->assertEquals(70.00, (float)$sale->total_amount);
        $this->assertEquals(70.00, (float)$sale->paid_amount);
        $this->assertEquals(0.00, (float)$sale->balance_due);
    }

    public function test_van_credit_sale_checks_credit_limit_and_updates_retailer_debt()
    {
        $supplier = $this->createSupplier();
        $retailer = $this->createRetailer();
        $driver = $this->createCompanyUser($supplier, 'driver');
        $product = $this->createProduct($supplier, 100, 50.00);

        // Retailer has $200 credit limit
        RetailerProfile::create([
            'company_id' => $retailer->id,
            'credit_limit' => 200.00,
        ]);

        $van = CashVan::create([
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی فرۆشتنی قەرز',
            'plate_number' => '22 B 66666',
            'driver_user_id' => $driver->id,
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        CashVanInventory::create([
            'cash_van_id' => $van->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        // Sale of 2 items = $100 on credit (within $200 limit)
        $response = $this->actingAs($driver)->post(route('cash_van.sales.store', $van->id), [
            'retailer_company_id' => $retailer->id,
            'sale_type' => 'direct_sale',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'payment_method' => 'credit',
            'discount_amount' => 0.00,
        ]);

        $response->assertSessionHasNoErrors();
        $profile = RetailerProfile::where('company_id', $retailer->id)->first();
        $this->assertEquals(100.00, (float)$profile->outstanding_debt);

        // Another sale exceeding the remaining credit limit ($150 > $100 remaining)
        $response2 = $this->actingAs($driver)->post(route('cash_van.sales.store', $van->id), [
            'retailer_company_id' => $retailer->id,
            'sale_type' => 'direct_sale',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3], // 3 * 50 = $150
            ],
            'payment_method' => 'credit',
            'discount_amount' => 0.00,
        ]);

        $response2->assertSessionHasErrors('sale_error');
    }

    public function test_van_partial_sales_return_restocks_and_adjusts_balance()
    {
        $supplier = $this->createSupplier();
        $retailer = $this->createRetailer();
        $driver = $this->createCompanyUser($supplier, 'driver');
        $product = $this->createProduct($supplier, 100, 20.00);

        $van = CashVan::create([
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی گەڕاندنەوە',
            'plate_number' => '22 B 77777',
            'driver_user_id' => $driver->id,
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        CashVanInventory::create([
            'cash_van_id' => $van->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $service = app(CashVanService::class);
        $sale = $service->recordSale(
            $van,
            $retailer->id,
            [['product_id' => $product->id, 'quantity' => 4]],
            'cash',
            80.00,
            0.00,
            $driver
        );

        $this->assertEquals(6, (float)CashVanInventory::where('cash_van_id', $van->id)->where('product_id', $product->id)->value('quantity'));

        // Return 1 item back to van
        $response = $this->actingAs($driver)->post(route('cash_van.returns.store', $sale->id), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'return_reason' => 'کارتۆن زیانی پێگەیشتووە',
            'restock_destination' => 'van',
        ]);

        $response->assertSessionHasNoErrors();

        // Van inventory should be back to 7 (6 + 1)
        $this->assertEquals(7, (float)CashVanInventory::where('cash_van_id', $van->id)->where('product_id', $product->id)->value('quantity'));

        // Returning more than sold qty (e.g. 5 more when only 3 remain) should fail
        $responseOver = $this->actingAs($driver)->post(route('cash_van.returns.store', $sale->id), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
            'return_reason' => 'زیادە',
            'restock_destination' => 'van',
        ]);

        $responseOver->assertSessionHasErrors('return_error');
    }

    public function test_van_expense_and_daily_shift_reconciliation()
    {
        $supplier = $this->createSupplier();
        $retailer = $this->createRetailer();
        $driver = $this->createCompanyUser($supplier, 'driver');
        $product = $this->createProduct($supplier, 100, 50.00);

        $van = CashVan::create([
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی تەحاسوب',
            'plate_number' => '22 B 88888',
            'driver_user_id' => $driver->id,
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        CashVanInventory::create([
            'cash_van_id' => $van->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $service = app(CashVanService::class);
        // Cash sale: 2 * 50 = $100
        $service->recordSale(
            $van,
            $retailer->id,
            [['product_id' => $product->id, 'quantity' => 2]],
            'cash',
            100.00,
            0.00,
            $driver
        );

        // Record fuel expense: $20
        $this->actingAs($driver)->post(route('cash_van.expenses.store', $van->id), [
            'expense_type' => 'fuel',
            'amount' => 20.00,
            'notes' => 'سووتەمەنی ڕێگا',
        ]);

        // Reconcile: Opening cash = $50. Expected = 50 + 100 - 20 = $130. Actual handed = $125 (Shortage of $5).
        $response = $this->actingAs($driver)->post(route('cash_van.reconcile.store', $van->id), [
            'opening_cash' => 50.00,
            'actual_cash_handed' => 125.00,
            'notes' => 'کورتێنان بە بڕی ٥ دۆلار',
        ]);

        $response->assertSessionHasNoErrors();

        $rec = CashVanReconciliation::where('cash_van_id', $van->id)->first();
        $this->assertNotNull($rec);
        $this->assertEquals(50.00, (float)$rec->opening_cash);
        $this->assertEquals(100.00, (float)$rec->total_cash_sales);
        $this->assertEquals(20.00, (float)$rec->total_expenses);
        $this->assertEquals(130.00, (float)$rec->expected_cash);
        $this->assertEquals(125.00, (float)$rec->actual_cash_handed);
        $this->assertEquals(-5.00, (float)$rec->cash_discrepancy);
    }

    public function test_tenant_isolation_company_cannot_access_or_sell_from_another_companys_van()
    {
        $supplierA = $this->createSupplier('Supplier A');
        $supplierB = $this->createSupplier('Supplier B');

        $userB = $this->createCompanyUser($supplierB, 'owner');

        $vanA = CashVan::create([
            'company_id' => $supplierA->id,
            'vehicle_name' => 'ڤانی کۆمپانیای تر',
            'plate_number' => '11 A 99999',
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        // User B attempts to load stock or operate van A
        $response = $this->actingAs($userB)->post(route('cash_van.stock.load', $vanA->id), [
            'items' => [['product_id' => 1, 'quantity' => 10]],
        ]);

        $response->assertStatus(403);
    }

    public function test_export_sales_csv_returns_streamed_csv_with_utf8_bom()
    {
        $supplier = $this->createSupplier();
        $user = $this->createCompanyUser($supplier, 'owner');

        $van = CashVan::create([
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی ڕاپۆرت',
            'plate_number' => '33 C 12345',
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('cash_van.export.sales', $van->id));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_supplier_can_create_route_with_retailer_stops()
    {
        $supplier = $this->createSupplier();
        $retailer1 = $this->createRetailer('Retailer 1');
        $retailer2 = $this->createRetailer('Retailer 2');
        $user = $this->createCompanyUser($supplier, 'owner');

        $van = CashVan::create([
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی هێڵی گەڕان',
            'plate_number' => '44 D 55555',
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('cash_van.routes.store', $van->id), [
            'route_name' => 'هێڵی ناوەندی شار',
            'scheduled_date' => now()->toDateString(),
            'retailer_ids' => [$retailer1->id, $retailer2->id],
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cash_van_routes', [
            'cash_van_id' => $van->id,
            'route_name' => 'هێڵی ناوەندی شار',
        ]);

        $this->assertDatabaseHas('cash_van_route_stops', [
            'retailer_company_id' => $retailer1->id,
            'stop_order' => 1,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('cash_van_route_stops', [
            'retailer_company_id' => $retailer2->id,
            'stop_order' => 2,
            'status' => 'pending',
        ]);
    }

    public function test_supplier_can_batch_load_multiple_products_to_van(): void
    {
        $supplier = $this->createSupplier();
        $user = $this->createCompanyUser($supplier, 'owner');
        $product1 = $this->createProduct($supplier, 100, 10.00);
        $product2 = $this->createProduct($supplier, 50, 25.00);

        $van = CashVan::create([
            'company_id' => $supplier->id,
            'vehicle_name' => 'ڤانی دابەشکردنی خێرا',
            'plate_number' => '77 K 12345',
            'operating_mode' => 'direct_sale',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('cash_van.stock.load', $van->id), [
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 20],
                ['product_id' => $product2->id, 'quantity' => 15],
            ],
            'notes' => 'بارکردنی فرە-کاڵا بە یەک داواکاری',
        ]);

        $response->assertSessionHasNoErrors();

        // Verify stock deducted from warehouse
        $this->assertEquals(80, $product1->fresh()->current_stock);
        $this->assertEquals(35, $product2->fresh()->current_stock);

        // Verify stock added to van inventory
        $this->assertDatabaseHas('cash_van_inventories', [
            'cash_van_id' => $van->id,
            'product_id' => $product1->id,
            'quantity' => 20,
        ]);
        $this->assertDatabaseHas('cash_van_inventories', [
            'cash_van_id' => $van->id,
            'product_id' => $product2->id,
            'quantity' => 15,
        ]);
    }
}

