<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Payment Receipt Vouchers (پسوولەی فەرمی وەرگرتنی پارە / سند قبض)
        Schema::create('payment_receipt_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number', 50)->unique();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('collected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('cash_van_sale_id')->nullable()->constrained('cash_van_sales')->nullOnDelete();
            $table->decimal('amount', 12, 2); // Primary USD amount
            $table->string('currency', 10)->default('USD');
            $table->decimal('amount_iqd', 15, 2)->nullable(); // If paid in IQD
            $table->decimal('exchange_rate', 10, 4)->nullable(); // IQD rate per 1 USD
            $table->string('payment_method', 30)->default('cash'); // cash, bank_transfer, fib_fastpay, cheque
            $table->string('custody_status', 30)->default('in_driver_custody'); // in_driver_custody, deposited_to_safe, verified
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('cheque_number', 50)->nullable();
            $table->date('cheque_due_date')->nullable();
            $table->string('transaction_reference', 100)->nullable();
            $table->date('receipt_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_company_id', 'retailer_company_id']);
            $table->index(['receipt_date']);
            $table->index(['custody_status']);
        });

        // 2. Customer Ledger Entries (دەفتەری حیسابی یەکگرتووی کڕیار / کەشف حیسابی بەردەوام)
        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('entry_type', 40); // order_invoice, cash_van_sale, sale_return, receipt_voucher, discount_credit, opening_balance
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number', 60);
            $table->dateTime('entry_date');
            $table->string('description', 255);
            $table->decimal('debit_amount', 12, 2)->default(0.00); // زیادبوونی قەرز لەسەر کڕیار
            $table->decimal('credit_amount', 12, 2)->default(0.00); // دانەوەی قەرز / کەمکردنەوە
            $table->decimal('running_balance', 12, 2)->default(0.00); // ڕەسیدی ماوەی ئەم هێڵە
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_company_id', 'retailer_company_id', 'entry_date'], 'cust_ledger_company_date_idx');
            $table->index(['reference_number']);
        });

        // 3. Driver & Mandub Cash Clearance Settlements (تەحاسوب و پاکتاوی نەقدینەی شۆفێر لەگەڵ قاسمی سەرەکی)
        Schema::create('cash_clearance_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number', 50)->unique();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('driver_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('settlement_date');
            $table->decimal('total_cash_collected', 12, 2);
            $table->decimal('total_iqd_collected', 15, 2)->default(0.00);
            $table->decimal('actual_cash_deposited', 12, 2);
            $table->decimal('discrepancy_amount', 12, 2)->default(0.00);
            $table->string('status', 30)->default('verified'); // pending, verified, disputed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_company_id', 'driver_user_id']);
            $table->index(['settlement_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_clearance_settlements');
        Schema::dropIfExists('customer_ledger_entries');
        Schema::dropIfExists('payment_receipt_vouchers');
    }
};
