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
        Schema::create('market_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('title');
            $table->integer('quantity');
            $table->string('unit')->default('carton');
            $table->decimal('target_unit_price', 12, 2)->nullable();
            $table->enum('status', ['open', 'fulfilled', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_demands');
    }
};
