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
        Schema::create('delivery_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('received_by_name', 150)->nullable();
            $table->text('signature_image_path')->nullable();
            $table->string('otp_code', 10)->nullable();
            $table->decimal('delivered_latitude', 10, 8)->nullable();
            $table->decimal('delivered_longitude', 10, 8)->nullable();
            $table->decimal('cash_collected', 12, 2)->default(0.00);
            $table->enum('status', ['full_delivery', 'partial_delivery', 'rejected'])->default('full_delivery');
            $table->timestamp('delivered_at')->nullable();
            $table->boolean('synced_offline')->default(false);
            $table->timestamps();
        });

        Schema::create('delivery_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_proof_id')->constrained('delivery_proofs')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->cascadeOnDelete();
            $table->integer('ordered_quantity')->default(0);
            $table->integer('delivered_quantity')->default(0);
            $table->enum('reason', ['damaged', 'missing', 'expired', 'rejected_by_store'])->default('damaged');
            $table->string('evidence_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_discrepancies');
        Schema::dropIfExists('delivery_proofs');
    }
};
