<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Audit Trail for all Order Status Changes
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('changed_by_name')->nullable();
            $table->string('role')->nullable(); // supplier, retailer, driver, operator
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Enhance purchase_orders with OTP, Signature, Damage & Credit Due Date
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            $table->string('delivery_otp', 10)->nullable()->after('status');
            $table->string('delivery_signature')->nullable()->after('delivery_otp');
            $table->text('damage_notes')->nullable()->after('inspection_notes');
            $table->decimal('damage_amount', 12, 2)->default(0.00)->after('damage_notes');
            $table->date('credit_due_date')->nullable()->after('payment_method');
            $table->boolean('stock_reserved')->default(false)->after('status');
        });

        // 3. Add reserved_stock to products
        Schema::table('products', function (Blueprint $table) {
            $table->integer('reserved_stock')->default(0)->after('current_stock');
        });

        // 4. Add credit_limit to retailer_profiles
        Schema::table('retailer_profiles', function (Blueprint $table) {
            $table->decimal('credit_limit', 12, 2)->default(5000.00)->after('store_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_otp',
                'delivery_signature',
                'damage_notes',
                'damage_amount',
                'credit_due_date',
                'stock_reserved',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('reserved_stock');
        });

        Schema::table('retailer_profiles', function (Blueprint $table) {
            $table->dropColumn('credit_limit');
        });
    }
};
