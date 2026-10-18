<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CustomerLedgerEntry;
use App\Models\OrderInstallment;
use App\Models\PaymentReceiptVoucher;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Services\FinancialPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialTransactionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $supplier;
    protected Company $retailer;
    protected User $supplierOwner;
    protected User $supplierAccountant;
    protected User $retailerBuyer;
    protected User $retailerReceiver;
    protected User $driver;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Company::create([
            'name' => 'کۆمپانیای بازرگانی هاوڕێ (دابینکەر)',
            'trade_license_number' => 'TL-SUP-FIN-01',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'primary_email' => 'hawre_fin@supplier.krd',
            'primary_phone' => '07501234567',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'ناوچەی پیشەسازی',
            'is_active' => true,
        ]);

        $this->retailer = Company::create([
            'name' => 'مارکێتی کوردستان (کڕیار)',
            'trade_license_number' => 'TL-RET-FIN-01',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'primary_email' => 'kurdistan_fin@market.krd',
            'primary_phone' => '07507654321',
            'city' => 'سلێمانی',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
            'is_active' => true,
        ]);

        $this->supplierOwner = User::create([
            'name' => 'خاوەن کۆمپانیا',
            'email' => 'supplier_owner_fin@marketlink.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->supplier->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->supplierAccountant = User::create([
            'name' => 'ژمێریاری دابینکەر',
            'email' => 'supplier_acct_fin@marketlink.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->supplier->id,
            'role' => 'accountant',
            'is_active' => true,
        ]);

        $this->retailerBuyer = User::create([
            'name' => 'بەرپرسی کڕین مارکێت',
            'email' => 'retailer_buyer_fin@marketlink.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->retailer->id,
            'role' => 'buyer',
            'is_active' => true,
        ]);

        $this->retailerReceiver = User::create([
            'name' => 'وەرگری بار مارکێت',
            'email' => 'retailer_recv_fin@marketlink.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->retailer->id,
            'role' => 'receiver',
            'is_active' => true,
        ]);

        $this->driver = User::create([
            'name' => 'شۆفێری دابەشکردن',
            'email' => 'driver_fin@marketlink.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->supplier->id,
            'role' => 'driver',
            'is_active' => true,
        ]);

        $category = \App\Models\ProductCategory::create([
            'name_ku' => 'خۆراکی گشتی',
            'name_ar' => 'مواد غذائية',
            'name_en' => 'General Grocery',
            'slug' => 'grocery-fin',
        ]);

        $this->product = Product::create([
            'supplier_company_id' => $this->supplier->id,
            'category_id' => $category->id,
            'title' => 'ڕۆنی زەیتی نایاب',
            'sku' => 'OIL-PREM-001',
            'barcode' => '7891234567890',
            'base_wholesale_price' => 20.00,
            'wholesale_price' => 20.00,
            'current_stock' => 500,
            'reserved_stock' => 0,
            'minimum_order_quantity' => 1,
            'is_active' => true,
        ]);
    }

    protected function createOrder(float $qty = 50, string $status = 'dispatched'): PurchaseOrder
    {
        $total = round($qty * (float)$this->product->base_wholesale_price, 2);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-' . rand(10000, 99999),
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'status' => $status,
            'currency' => 'USD',
            'subtotal_amount' => $total,
            'total_amount' => $total,
            'paid_amount' => 0.00,
            'remaining_debt' => $total,
            'payment_status' => 'unpaid',
            'payment_terms' => 'cash_on_delivery',
            'delivery_address' => 'ناونیشانی مارکێت',
            'delivery_otp' => '654321',
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->name,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'sku' => $this->product->sku,
            'quantity_ordered' => $qty,
            'unit_price' => $this->product->base_wholesale_price,
            'total_price' => $total,
        ]);

        return $po;
    }

    /**
     * Requirement: Full payment on reception zeros remaining debt and creates verified/safe voucher.
     */
    public function test_full_payment_on_reception_zeros_remaining_debt_and_records_voucher(): void
    {
        $po = $this->createOrder(50); // Total $1,000

        $response = $this->actingAs($this->retailerReceiver)
            ->post("/orders/{$po->id}/receive", [
                'inspected_by' => 'کامەران - وەرگری مارکێت',
                'inspection_notes' => 'کۆی بارەکە بە بێ کێشە گەیشت',
                'paid_amount' => 1000.00,
                'payment_method' => 'bank_transfer',
            ]);

        $response->assertStatus(302);
        $po->refresh();

        $this->assertEquals(1000.00, (float)$po->paid_amount);
        $this->assertEquals(0.00, (float)$po->remaining_debt);
        $this->assertEquals('paid', $po->payment_status);
        $this->assertEquals('delivered', $po->status);

        // Assert receipt voucher created
        $voucher = PaymentReceiptVoucher::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($voucher);
        $this->assertEquals(1000.00, (float)$voucher->amount);
        $this->assertEquals('bank_transfer', $voucher->payment_method);
        $this->assertEquals('deposited_to_safe', $voucher->custody_status);

        // Assert customer ledger has invoice debit and voucher credit
        $this->assertDatabaseHas('customer_ledger_entries', [
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'entry_type' => 'order_invoice',
            'debit_amount' => 1000.00,
        ]);

        $this->assertDatabaseHas('customer_ledger_entries', [
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'entry_type' => 'receipt_voucher',
            'credit_amount' => 1000.00,
            'running_balance' => 0.00,
        ]);
    }

    /**
     * Requirement: Partial payment on reception updates remaining debt accurately.
     */
    public function test_partial_payment_on_reception_updates_debt_and_ledger_accurately(): void
    {
        $po = $this->createOrder(50); // Total $1,000

        $response = $this->actingAs($this->retailerReceiver)
            ->post("/orders/{$po->id}/receive", [
                'inspected_by' => 'کامەران',
                'inspection_notes' => 'نیوەی درا بە نەقد',
                'paid_amount' => 400.00,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(302);
        $po->refresh();

        $this->assertEquals(400.00, (float)$po->paid_amount);
        $this->assertEquals(600.00, (float)$po->remaining_debt);
        $this->assertEquals('partially_paid', $po->payment_status);

        // Cash collected on reception by receiver/driver has custody status 'in_driver_custody'
        $voucher = PaymentReceiptVoucher::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($voucher);
        $this->assertEquals(400.00, (float)$voucher->amount);
        $this->assertEquals('in_driver_custody', $voucher->custody_status);

        // Running balance on customer ledger is exactly $600.00
        $lastEntry = CustomerLedgerEntry::where('supplier_company_id', $this->supplier->id)
            ->where('retailer_company_id', $this->retailer->id)
            ->orderBy('id', 'desc')
            ->first();

        $this->assertEquals(600.00, (float)$lastEntry->running_balance);
    }

    /**
     * Requirement: Full credit on reception creates debt without recording receipt voucher.
     */
    public function test_full_credit_on_reception_creates_debt_without_recording_voucher(): void
    {
        $po = $this->createOrder(40); // Total $800

        $response = $this->actingAs($this->retailerReceiver)
            ->post("/orders/{$po->id}/receive", [
                'inspected_by' => 'کامەران',
                'inspection_notes' => 'قەرز بە وادەی ٣٠ ڕۆژ',
                'paid_amount' => 0.00,
                'payment_method' => 'credit',
            ]);

        $response->assertStatus(302);
        $po->refresh();

        $this->assertEquals(0.00, (float)$po->paid_amount);
        $this->assertEquals(800.00, (float)$po->remaining_debt);
        $this->assertEquals('unpaid', $po->payment_status);

        // No payment receipt voucher should be created for zero payment
        $this->assertFalse(PaymentReceiptVoucher::where('purchase_order_id', $po->id)->exists());

        // Invoice debit entry recorded
        $this->assertDatabaseHas('customer_ledger_entries', [
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'entry_type' => 'order_invoice',
            'debit_amount' => 800.00,
            'running_balance' => 800.00,
        ]);
    }

    /**
     * Requirement: Idempotency Key prevents duplicate payment and double ledger recording.
     */
    public function test_idempotency_prevents_duplicate_payment_records(): void
    {
        $po = $this->createOrder(30); // Total $600
        $idempotencyKey = 'IDEM-TEST-' . uniqid();

        // 1st request
        $resp1 = $this->actingAs($this->retailerReceiver)
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->post("/orders/{$po->id}/receive", [
                'inspected_by' => 'کامەران',
                'paid_amount' => 300.00,
                'payment_method' => 'bank_transfer',
            ]);

        $resp1->assertStatus(302);
        $this->assertEquals(1, PaymentReceiptVoucher::where('transaction_reference', $idempotencyKey)->count());
        $this->assertEquals(300.00, (float)$po->fresh()->remaining_debt);

        // 2nd identical request with same Idempotency-Key (e.g. double click or network retry)
        $resp2 = $this->actingAs($this->retailerReceiver)
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->post("/orders/{$po->id}/receive", [
                'inspected_by' => 'کامەران',
                'paid_amount' => 300.00,
                'payment_method' => 'bank_transfer',
            ]);

        $resp2->assertStatus(302);

        // Still only 1 voucher exists, debt was NOT deducted twice
        $this->assertEquals(1, PaymentReceiptVoucher::where('transaction_reference', $idempotencyKey)->count());
        $this->assertEquals(300.00, (float)$po->fresh()->remaining_debt);
        $this->assertEquals(300.00, (float)$po->fresh()->paid_amount);
    }

    /**
     * Requirement: Driver-collected cash is held in custody and cannot be deposited into safe without verification.
     */
    public function test_driver_cash_collected_is_held_in_custody_until_verified(): void
    {
        $po = $this->createOrder(25); // Total $500

        // Driver delivers and collects cash COD
        $resp = $this->actingAs($this->driver)
            ->post("/orders/{$po->id}/deliver-verify", [
                'delivery_otp' => '654321',
                'cash_collected' => 200.00,
                'driver_name' => $this->driver->name,
                'inspected_by' => 'ئاراس مارکێت',
            ]);

        $resp->assertStatus(302);
        $po->refresh();

        $this->assertEquals(200.00, (float)$po->paid_amount);
        $this->assertEquals(300.00, (float)$po->remaining_debt);

        $voucher = PaymentReceiptVoucher::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($voucher);
        $this->assertEquals('in_driver_custody', $voucher->custody_status);

        // Driver tries to verify voucher directly into company safe -> 403 Forbidden
        $driverVerify = $this->actingAs($this->driver)
            ->post("/financial/vouchers/{$voucher->id}/verify");

        $driverVerify->assertStatus(403);
        $this->assertEquals('in_driver_custody', $voucher->fresh()->custody_status);

        // Supplier Accountant verifies voucher into safe -> 302 Success
        $acctVerify = $this->actingAs($this->supplierAccountant)
            ->post("/financial/vouchers/{$voucher->id}/verify", ['notes' => 'پارەکە وەرگیرا و خرایە خەزێنە']);

        $acctVerify->assertStatus(302);
        $this->assertEquals('deposited_to_safe', $voucher->fresh()->custody_status);
        $this->assertEquals($this->supplierAccountant->id, $voucher->fresh()->verified_by_user_id);
    }

    /**
     * Requirement: Delivery verification deducts damages from total and creates discrepancy credit in ledger.
     */
    public function test_delivery_verification_adjusts_damage_discrepancy_and_debt(): void
    {
        $po = $this->createOrder(50); // Total $1,000

        $resp = $this->actingAs($this->driver)
            ->post("/orders/{$po->id}/deliver-verify", [
                'delivery_otp' => '654321',
                'damage_amount' => 150.00,
                'damage_notes' => '٣ کارتۆن بەهۆی تەڕبوون تەلەف بوو',
                'cash_collected' => 0.00,
                'driver_name' => $this->driver->name,
                'inspected_by' => 'مارکێت',
            ]);

        $resp->assertStatus(302);
        $po->refresh();

        $this->assertEquals(850.00, (float)$po->total_amount);
        $this->assertEquals(850.00, (float)$po->remaining_debt);
        $this->assertEquals(150.00, (float)$po->damage_amount);

        // Discrepancy credit entry recorded in customer ledger
        $this->assertDatabaseHas('customer_ledger_entries', [
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'entry_type' => 'order_return',
            'credit_amount' => 150.00,
            'running_balance' => 850.00,
        ]);
    }

    /**
     * Requirement: Payment reversal creates compensatory counter-entry in ledger and re-instates PO debt.
     */
    public function test_payment_reversal_creates_counter_entry_and_reinstates_debt(): void
    {
        $po = $this->createOrder(30); // Total $600

        // Pay full amount initially
        $this->actingAs($this->retailerReceiver)
            ->post("/orders/{$po->id}/receive", [
                'inspected_by' => 'کامەران',
                'paid_amount' => 600.00,
                'payment_method' => 'bank_transfer',
            ]);

        $po->refresh();
        $this->assertEquals(0.00, (float)$po->remaining_debt);

        $voucher = PaymentReceiptVoucher::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($voucher);

        // Accountant reverses payment (e.g., bounced cheque or bank transfer rejected)
        $reverseResp = $this->actingAs($this->supplierAccountant)
            ->post("/financial/vouchers/{$voucher->id}/reverse", [
                'reversal_reason' => 'حەواڵەی بانکی لەلایەن بانکەوە ڕەتکرایەوە (Transfer bounced)',
            ]);

        $reverseResp->assertStatus(302);
        $voucher->refresh();
        $po->refresh();

        // Voucher status is reversed (not deleted!)
        $this->assertEquals('reversed', $voucher->custody_status);
        $this->assertStringContainsString('REVERSED', $voucher->notes);

        // PO debt is re-instated
        $this->assertEquals(600.00, (float)$po->remaining_debt);
        $this->assertEquals(0.00, (float)$po->paid_amount);
        $this->assertEquals('unpaid', $po->payment_status);

        // Counter debit entry recorded in customer ledger
        $this->assertDatabaseHas('customer_ledger_entries', [
            'supplier_company_id' => $this->supplier->id,
            'retailer_company_id' => $this->retailer->id,
            'entry_type' => 'opening_balance',
            'reference_number' => 'REV-' . $voucher->voucher_number,
            'debit_amount' => 600.00,
            'running_balance' => 600.00,
        ]);

        $this->assertTrue(SecurityAuditLog::where('event_type', 'payment_reversal')->exists());
    }

    /**
     * Requirement: Order cancellation zeroes debt and records offsetting adjustment in ledger.
     */
    public function test_order_cancellation_offsets_ledger_and_zeros_remaining_debt(): void
    {
        $po = $this->createOrder(25); // Total $500

        // Pre-record order invoice
        app(FinancialPaymentService::class)->processOrderReceptionPayment(
            $po,
            0.00,
            'credit',
            $this->retailerBuyer
        );

        $this->assertEquals(500.00, (float)$po->fresh()->remaining_debt);

        // Cancel order
        $cancelResp = $this->actingAs($this->supplierOwner)
            ->post("/orders/{$po->id}/cancel", [
                'cancel_reason' => 'مارکێت داواکارییەکەی ڕەتکردەوە بەهۆی گۆڕانی پلان',
            ]);

        $cancelResp->assertStatus(302);
        $po->refresh();

        $this->assertEquals('cancelled', $po->status);
        $this->assertEquals(0.00, (float)$po->remaining_debt);

        // Offsetting cancellation credit entry recorded in ledger
        $lastEntry = CustomerLedgerEntry::where('supplier_company_id', $this->supplier->id)
            ->where('retailer_company_id', $this->retailer->id)
            ->orderBy('id', 'desc')
            ->first();

        $this->assertEquals(0.00, (float)$lastEntry->running_balance);
        $this->assertEquals(500.00, (float)$lastEntry->credit_amount);
    }

    /**
     * Requirement: Structured installment schedule creation and installment settlement.
     */
    public function test_installment_plan_schedule_and_settlement(): void
    {
        $po = $this->createOrder(60); // Total $1,200

        // Create 3-installment schedule ($400 each)
        $instResp = $this->actingAs($this->supplierAccountant)
            ->post("/orders/{$po->id}/installments", [
                'number_of_installments' => 3,
                'frequency' => 'monthly',
            ]);

        $instResp->assertStatus(302);
        $this->assertEquals(3, $po->installments()->count());

        $firstInst = $po->installments()->where('installment_number', 1)->first();
        $this->assertEquals(400.00, (float)$firstInst->amount);
        $this->assertEquals(400.00, (float)$firstInst->remaining_amount);
        $this->assertEquals('pending', $firstInst->status);

        // Settle first installment
        $payResp = $this->actingAs($this->supplierAccountant)
            ->post("/installments/{$firstInst->id}/pay", [
                'amount' => 400.00,
                'payment_method' => 'bank_transfer',
            ]);

        $payResp->assertStatus(302);
        $firstInst->refresh();
        $po->refresh();

        $this->assertEquals('paid', $firstInst->status);
        $this->assertEquals(0.00, (float)$firstInst->remaining_amount);
        $this->assertNotNull($firstInst->paid_at);

        // Order debt reduced from $1,200 to $800
        $this->assertEquals(800.00, (float)$po->remaining_debt);
        $this->assertEquals(400.00, (float)$po->paid_amount);
    }

    /**
     * Requirement: Balance integrity checker accurately reports consistent ledger and orders debt.
     */
    public function test_balance_integrity_checker_validates_ledger_and_debts(): void
    {
        $po1 = $this->createOrder(30); // $600
        $po2 = $this->createOrder(20); // $400

        // Receive po1 with $200 partial payment
        app(FinancialPaymentService::class)->processOrderReceptionPayment(
            $po1,
            200.00,
            'bank_transfer',
            $this->supplierAccountant
        );

        // Receive po2 with full credit ($0 payment)
        app(FinancialPaymentService::class)->processOrderReceptionPayment(
            $po2,
            0.00,
            'credit',
            $this->supplierAccountant
        );

        $integrityResp = $this->actingAs($this->supplierAccountant)
            ->getJson("/financial/integrity/{$this->supplier->id}/{$this->retailer->id}");

        $integrityResp->assertStatus(200);
        $data = $integrityResp->json('integrity');

        // Total debits: 600 + 400 = 1000
        $this->assertEquals(1000.00, (float)$data['total_debits']);
        // Total credits: 200
        $this->assertEquals(200.00, (float)$data['total_credits']);
        // Net ledger balance: 800
        $this->assertEquals(800.00, (float)$data['calculated_ledger_balance']);
        // Active PO remaining debts: 400 + 400 = 800
        $this->assertEquals(800.00, (float)$data['total_active_order_debt']);
        $this->assertTrue($data['is_ledger_consistent']);
    }
}
