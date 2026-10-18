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
        Schema::create('order_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->integer('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('remaining_amount', 12, 2);
            $table->string('status', 30)->default('pending'); // pending, partially_paid, paid, overdue
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('payment_receipt_voucher_id')->nullable()->constrained('payment_receipt_vouchers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['purchase_order_id', 'status']);
            $table->index(['due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_installments');
    }
};
