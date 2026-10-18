<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Supplier Products (Owned exclusively by Supplier Company)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('product_categories')->restrictOnDelete();
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('unit_of_measure')->default('piece'); // piece, box, carton, pallet, kg
            $table->decimal('base_wholesale_price', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->integer('minimum_order_quantity')->default(1);
            $table->integer('current_stock')->default(0);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['supplier_company_id', 'sku']);
        });

        // 2. Volume-based Wholesale Pricing Tiers
        Schema::create('product_volume_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('min_quantity');
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();

            $table->unique(['product_id', 'min_quantity']);
        });

        // 3. Customer-Specific Price Lists (Private agreements between Supplier & Retailer)
        Schema::create('customer_price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('negotiated_unit_price', 12, 2);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['supplier_company_id', 'retailer_company_id', 'product_id'], 'uq_cust_price_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_price_lists');
        Schema::dropIfExists('product_volume_tiers');
        Schema::dropIfExists('products');
    }
};
