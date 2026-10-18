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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('waybill_number')->nullable()->after('po_number');
            $table->timestamp('dispatched_at')->nullable()->after('actual_delivery_date');
            $table->string('driver_name')->nullable()->after('dispatched_at');
            $table->string('driver_phone')->nullable()->after('driver_name');
            $table->string('vehicle_plate')->nullable()->after('driver_phone');
            $table->timestamp('received_at')->nullable()->after('vehicle_plate');
            $table->string('inspected_by')->nullable()->after('received_at');
            $table->text('inspection_notes')->nullable()->after('inspected_by');
            $table->decimal('paid_amount', 12, 2)->default(0.00)->after('total_amount');
            $table->decimal('remaining_debt', 12, 2)->default(0.00)->after('paid_amount');
            $table->string('payment_status')->default('unpaid')->after('remaining_debt');
            $table->string('payment_method')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'waybill_number',
                'dispatched_at',
                'driver_name',
                'driver_phone',
                'vehicle_plate',
                'received_at',
                'inspected_by',
                'inspection_notes',
                'paid_amount',
                'remaining_debt',
                'payment_status',
                'payment_method',
            ]);
        });
    }
};
