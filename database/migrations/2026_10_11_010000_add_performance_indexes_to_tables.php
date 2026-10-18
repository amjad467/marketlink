<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('barcode');
            $table->index(['supplier_company_id', 'is_available']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('delivery_otp');
            $table->index(['retailer_company_id', 'status']);
            $table->index(['supplier_company_id', 'status']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->index(['business_type', 'verification_status']);
            $table->index('province');
        });

        Schema::table('market_demands', function (Blueprint $table) {
            $table->index(['status', 'category_id']);
        });

        Schema::table('b2b_messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['barcode']);
            $table->dropIndex(['supplier_company_id', 'is_available']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['delivery_otp']);
            $table->dropIndex(['retailer_company_id', 'status']);
            $table->dropIndex(['supplier_company_id', 'status']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['business_type', 'verification_status']);
            $table->dropIndex(['province']);
        });

        Schema::table('market_demands', function (Blueprint $table) {
            $table->dropIndex(['status', 'category_id']);
        });

        Schema::table('b2b_messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id', 'is_read']);
        });
    }
};
