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
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('address_line');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->text('google_maps_url')->nullable()->after('longitude');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('delivery_latitude', 10, 8)->nullable()->after('delivery_address');
            $table->decimal('delivery_longitude', 11, 8)->nullable()->after('delivery_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'google_maps_url']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_latitude', 'delivery_longitude']);
        });
    }
};
