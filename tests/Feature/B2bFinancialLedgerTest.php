<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CustomerLedgerEntry;
use App\Models\PaymentReceiptVoucher;
use App\Models\PurchaseOrder;
use App\Models\CashVan;
use App\Models\CashVanSale;
use App\Models\CashClearanceSettlement;
use App\Models\User;
use App\Services\B2bFinancialLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class B2bFinancialLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected Company $supplier;
    protected Company $retailer;
    protected User $supplierUser;
    protected User $driverUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Company::create([
            'name' => 'Apex Distribution Co',
            'trade_license_number' => 'TL-SUP-FIN-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'finance@apex.krd',
            'primary_phone' => '07501112233',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی بازرگانی',
        ]);

        $this->retailer = Company::create([
            'name' => 'Metro Supermarkets',
            'trade_license_number' => 'TL-RET-FIN-01',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'metro@market.krd',
            'primary_phone' => '07504445566',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
        ]);

        $this->supplierUser = User::create([
            'company_id' => $this->supplier->id,
            'name' => 'ژمێریاری کۆمپانیای دابینکەر',
            'email' => 'accountant@apex.krd',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->driverUser = User::create([
            'company_id' => $this->supplier->id,
            'name' => 'شۆفێری کاش ڤان',
            'email' => 'driver@apex.krd',
            'password' => Hash::make('password'),
            'role' => 'driver',
            'is_active' => true,
        ]);

        $this->retailerUser = User::create([
            'company_id' => $this->retailer->id,
            'name' => 'خاوەن مارکێت',
            'email' => 'owner@metro.krd',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    public function test_record_payment_receipt_voucher_updates_debt_and_ledger(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-FIN-001',
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'status' => 'delivered',
            'delivery_address' => 'ناونیشانی مارکێت لە هەولێر',
            'subtotal_amount' => 500.00,
            'total_amount' => 500.00,
            'paid_amount' => 0.00,
            'remaining_debt' => 500.00,
            'payment_status' => 'unpaid',
            'payment_terms' => 'credit_30',
        ]);

        $response = $this->actingAs($this->supplierUser)->post(route('financial.vouchers.store'), [
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'amount' => 200.00,
            'payment_method' => 'cash',
            'purchase_order_id' => $po->id,
            'amount_iqd' => 304000,
            'exchange_rate' => 1520,
            'notes' => 'دانەوەی بەشێک لە قەرز',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $po->refresh();
        $this->assertEquals(200.00, (float)$po->paid_amount);
        $this->assertEquals(300.00, (float)$po->remaining_debt);
        $this->assertEquals('partially_paid', $po->payment_status);

        $this->assertDatabaseHas('payment_receipt_vouchers', [
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'purchase_order_id' => $po->id,
            'amount' => 200.00,
            'payment_method' => 'cash',
        ]);

        $this->assertDatabaseHas('customer_ledger_entries', [
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'entry_type' => 'receipt_voucher',
            'credit_amount' => 200.00,
        ]);
    }

    public function test_settle_debt_on_order_creates_official_receipt_voucher(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-FIN-002',
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'status' => 'delivered',
            'delivery_address' => 'ناونیشانی مارکێت لە هەولێر',
            'subtotal_amount' => 400.00,
            'total_amount' => 400.00,
            'paid_amount' => 100.00,
            'remaining_debt' => 300.00,
            'payment_status' => 'partially_paid',
            'payment_terms' => 'credit_30',
        ]);

        $response = $this->actingAs($this->retailerUser)->post(route('orders.pay_debt', $po->id), [
            'payment_amount' => 300.00,
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'TXN-FIB-9988',
        ]);

        $response->assertSessionHas('success');
        $po->refresh();

        $this->assertEquals(400.00, (float)$po->paid_amount);
        $this->assertEquals(0.00, (float)$po->remaining_debt);
        $this->assertEquals('paid', $po->payment_status);

        $voucher = PaymentReceiptVoucher::where('purchase_order_id', $po->id)->latest()->first();
        $this->assertNotNull($voucher);
        $this->assertEquals(300.00, (float)$voucher->amount);
    }

    public function test_unified_statement_of_account_data_and_aging_calculation(): void
    {
        // 1. Order 1: $1000 total, $400 remaining debt
        $po = PurchaseOrder::create([
            'po_number' => 'PO-FIN-003',
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'status' => 'delivered',
            'delivery_address' => 'ناونیشانی مارکێت لە هەولێر',
            'payment_terms' => 'credit_30',
            'subtotal_amount' => 1000.00,
            'total_amount' => 1000.00,
            'paid_amount' => 600.00,
            'remaining_debt' => 400.00,
            'payment_status' => 'partially_paid',
            'created_at' => now()->subDays(10),
        ]);

        $service = app(B2bFinancialLedgerService::class);
        $statement = $service->getUnifiedStatementData($this->supplier->id, $this->retailer->id);

        $this->assertEquals(1000.00, $statement['total_debit']);
        $this->assertEquals(600.00, $statement['total_credit']);
        $this->assertEquals(400.00, $statement['net_balance']);
        $this->assertArrayHasKey('aging', $statement);
        $this->assertEquals(400.00, $statement['aging']['current_30']);
    }

    public function test_printable_receipt_voucher_renders_correctly(): void
    {
        $voucher = PaymentReceiptVoucher::create([
            'voucher_number' => 'RV-TEST-001',
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'amount' => 250.00,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'custody_status' => 'deposited_to_safe',
            'receipt_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->supplierUser)->get(route('financial.voucher.print', $voucher->id));
        $response->assertStatus(200);
        $response->assertSee('RV-TEST-001');
        $response->assertSee('250.00');
        $response->assertSee('RECEIPT VOUCHER / سند قبض');
    }

    public function test_driver_cash_clearance_settlement_flow(): void
    {
        // Voucher collected by driver
        $voucher = PaymentReceiptVoucher::create([
            'voucher_number' => 'RV-DRV-001',
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'collected_by_user_id' => $this->driverUser->id,
            'amount' => 150.00,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'custody_status' => 'in_driver_custody',
            'receipt_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->supplierUser)->post(route('financial.clearance.store'), [
            'supplier_company_id' => $this->supplier->id,
            'driver_user_id' => $this->driverUser->id,
            'actual_cash_deposited' => 150.00,
            'notes' => 'تەسلیماتی تەواوی نەقدینەی شۆفێر',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cash_clearance_settlements', [
            'supplier_company_id' => $this->supplier->id,
            'driver_user_id' => $this->driverUser->id,
            'total_cash_collected' => 150.00,
            'actual_cash_deposited' => 150.00,
            'discrepancy_amount' => 0.00,
            'status' => 'verified',
        ]);

        $voucher->refresh();
        $this->assertEquals('deposited_to_safe', $voucher->custody_status);
        $this->assertEquals($this->supplierUser->id, $voucher->verified_by_user_id);
    }

    public function test_opening_balance_recording_and_ledger_integration(): void
    {
        $response = $this->actingAs($this->supplierUser)->post(route('financial.opening_balance.store'), [
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'amount' => 1250.00,
            'type' => 'debit',
            'entry_date' => '2026-01-01',
            'notes' => 'قەرزی ماوەی ساڵی پێشوو لە دەفتەری کۆن',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customer_ledger_entries', [
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'entry_type' => 'opening_balance',
            'debit_amount' => 1250.00,
            'credit_amount' => 0.00,
            'running_balance' => 1250.00,
        ]);

        $running = app(B2bFinancialLedgerService::class)->getCustomerRunningBalance($this->supplier->id, $this->retailer->id);
        $this->assertEquals(1250.00, $running);
    }

    public function test_single_voucher_verification_into_safe(): void
    {
        $voucher = PaymentReceiptVoucher::create([
            'voucher_number' => 'RV-DRV-SINGLE',
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'collected_by_user_id' => $this->driverUser->id,
            'amount' => 300.00,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'custody_status' => 'in_driver_custody',
            'receipt_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->supplierUser)->post(route('financial.voucher.verify', $voucher->id), [
            'notes' => 'وەرگیرا لەلایەن ژمێریارەوە',
        ]);

        $response->assertSessionHas('success');

        $voucher->refresh();
        $this->assertEquals('deposited_to_safe', $voucher->custody_status);
        $this->assertEquals($this->supplierUser->id, $voucher->verified_by_user_id);
        $this->assertNotNull($voucher->verified_at);
    }

    public function test_market_debts_by_company_and_driver_portal_visibility(): void
    {
        // Create an order for retailer with remaining debt
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-DRV-DEBT',
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'driver_id' => $this->driverUser->id,
            'status' => 'dispatched',
            'delivery_address' => 'ناونیشانی مارکێت لە هەولێر',
            'subtotal_amount' => 600.00,
            'total_amount' => 600.00,
            'paid_amount' => 100.00,
            'remaining_debt' => 500.00,
            'payment_status' => 'partially_paid',
            'payment_terms' => 'credit_30',
            'payment_method' => 'credit',
            'otp_code' => '554433',
        ]);

        $response = $this->actingAs($this->driverUser)->get(route('marketlink.index', ['role' => 'driver']));

        $response->assertStatus(200);
        $response->assertViewHas('marketCompanyDebts');
        $response->assertViewHas('driverMarketDebts');
        $response->assertSee('دەفتەری قەرزی مارکێتەکان بۆ کۆمپانیاکان');
        $response->assertSee('قەرزی ئەم مارکێتە بۆ');
        $response->assertSee('وەرگرتنی قەرز / پارەی پسوولە (سند قبض)');
        $response->assertSee('کام مارکێت قەرزداری کام کۆمپانیایە');
    }
}
