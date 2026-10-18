<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cash Vans (Vehicle registry per supplier)
        Schema::create('cash_vans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vehicle_name');
            $table->string('plate_number')->index();
            $table->string('model')->nullable();
            $table->enum('operating_mode', ['direct_sale', 'order_taking', 'hybrid'])->default('hybrid');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Van Inventory (Stock on each van)
        Schema::create('cash_van_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_van_id')->constrained('cash_vans')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('reserved_quantity', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['cash_van_id', 'product_id'], 'uniq_van_product');
        });

        // 3. Stock Transfers (Warehouse to Van, Van to Warehouse, Damage write-off)
        Schema::create('cash_van_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('cash_van_id')->constrained('cash_vans')->cascadeOnDelete();
            $table->enum('transfer_type', ['load_to_van', 'return_to_warehouse', 'damage_writeoff'])->default('load_to_van');
            $table->enum('status', ['draft', 'approved', 'completed', 'rejected'])->default('draft');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Stock Transfer Items
        Schema::create('cash_van_stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('cash_van_stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->timestamps();
        });

        // 5. Daily Van Routes
        Schema::create('cash_van_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('cash_van_id')->constrained('cash_vans')->cascadeOnDelete();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('route_name');
            $table->date('scheduled_date')->index();
            $table->enum('status', ['draft', 'in_progress', 'completed'])->default('draft');
            $table->timestamps();
        });

        // 6. Route Stops (Scheduled Visits)
        Schema::create('cash_van_route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('cash_van_routes')->cascadeOnDelete();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->integer('stop_order')->default(1);
            $table->enum('status', ['pending', 'visited', 'sale_completed', 'order_taken', 'customer_unavailable', 'skipped'])->default('pending');
            $table->dateTime('arrival_time')->nullable();
            $table->dateTime('departure_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Cash Van Sales (Invoices generated during visit)
        Schema::create('cash_van_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('cash_van_id')->constrained('cash_vans')->cascadeOnDelete();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('route_stop_id')->nullable()->constrained('cash_van_route_stops')->nullOnDelete();
            $table->enum('sale_type', ['direct_sale', 'order_taking'])->default('direct_sale');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->enum('payment_method', ['cash', 'credit', 'split'])->default('cash');
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('balance_due', 14, 2)->default(0);
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. Cash Van Sale Items
        Schema::create('cash_van_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('cash_van_sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        // 9. Returns & Claims (Partial or full item returns)
        Schema::create('cash_van_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('sale_id')->constrained('cash_van_sales')->cascadeOnDelete();
            $table->foreignId('cash_van_id')->constrained('cash_vans')->cascadeOnDelete();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_refund_amount', 14, 2)->default(0);
            $table->string('return_reason')->nullable();
            $table->enum('restock_destination', ['van', 'warehouse'])->default('van');
            $table->enum('status', ['approved', 'rejected'])->default('approved');
            $table->timestamps();
        });

        // 10. Return Items
        Schema::create('cash_van_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('cash_van_returns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });

        // 11. Van Operating Expenses (Fuel, toll, parking)
        Schema::create('cash_van_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_van_id')->constrained('cash_vans')->cascadeOnDelete();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('expense_type', ['fuel', 'maintenance', 'toll', 'food', 'other'])->default('fuel');
            $table->decimal('amount', 14, 2);
            $table->string('notes')->nullable();
            $table->string('receipt_photo_path')->nullable();
            $table->timestamps();
        });

        // 12. Daily Cash & Stock Reconciliations
        Schema::create('cash_van_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('reconciliation_number')->unique();
            $table->foreignId('cash_van_id')->constrained('cash_vans')->cascadeOnDelete();
            $table->foreignId('driver_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('shift_date')->index();
            $table->decimal('opening_cash', 14, 2)->default(0);
            $table->decimal('total_cash_sales', 14, 2)->default(0);
            $table->decimal('total_credit_sales', 14, 2)->default(0);
            $table->decimal('total_expenses', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->default(0);
            $table->decimal('actual_cash_handed', 14, 2)->default(0);
            $table->decimal('cash_discrepancy', 14, 2)->default(0);
            $table->enum('status', ['submitted', 'verified'])->default('submitted');
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_van_reconciliations');
        Schema::dropIfExists('cash_van_expenses');
        Schema::dropIfExists('cash_van_return_items');
        Schema::dropIfExists('cash_van_returns');
        Schema::dropIfExists('cash_van_sale_items');
        Schema::dropIfExists('cash_van_sales');
        Schema::dropIfExists('cash_van_route_stops');
        Schema::dropIfExists('cash_van_routes');
        Schema::dropIfExists('cash_van_stock_transfer_items');
        Schema::dropIfExists('cash_van_stock_transfers');
        Schema::dropIfExists('cash_van_inventories');
        Schema::dropIfExists('cash_vans');
    }
};
