<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Platform Operators (Infrastructure, Governance, Neutral Moderation)
        Schema::create('platform_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('superadmin'); // superadmin, compliance, support
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tenant Companies (Suppliers and Retailers - Strict 2-Sided Separation)
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trade_license_number')->unique();
            $table->string('tax_number')->nullable();
            $table->enum('business_type', ['supplier', 'retailer']);
            $table->enum('verification_status', ['pending', 'verified', 'rejected', 'suspended'])->default('pending');
            $table->enum('account_status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('primary_email');
            $table->string('primary_phone');
            $table->string('website')->nullable();
            $table->string('country');
            $table->string('city');
            $table->text('address_line');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('platform_admins')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamps();
        });

        // 3. KYB Documents for Verification
        Schema::create('business_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('document_type'); // trade_license, tax_certificate, id_card
            $table->string('file_path');
            $table->date('document_expiry_date')->nullable();
            $table->enum('review_status', ['submitted', 'approved', 'rejected'])->default('submitted');
            $table->foreignId('reviewed_by')->nullable()->constrained('platform_admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_comments')->nullable();
            $table->timestamps();
        });

        // 4. Supplier Profiles
        Schema::create('supplier_profiles', function (Blueprint $table) {
            $table->foreignId('company_id')->primary()->constrained('companies')->cascadeOnDelete();
            $table->decimal('minimum_order_total', 12, 2)->default(0.00);
            $table->text('shipping_policy')->nullable();
            $table->text('return_policy')->nullable();
            $table->string('default_payment_terms')->nullable(); // Net 30, COD, Advance
            $table->integer('lead_time_days')->default(2);
            $table->string('currency', 10)->default('USD');
            $table->timestamps();
        });

        // 5. Retailer Profiles
        Schema::create('retailer_profiles', function (Blueprint $table) {
            $table->foreignId('company_id')->primary()->constrained('companies')->cascadeOnDelete();
            $table->integer('store_count')->default(1);
            $table->text('default_delivery_address')->nullable();
            $table->string('preferred_payment_method')->nullable();
            $table->string('receiving_hours')->nullable();
            $table->timestamps();
        });

        // 6. Global Taxonomies
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('slug')->unique();
            $table->string('icon_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        // 7. Platform Audit Logs
        Schema::create('platform_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('platform_admins')->nullOnDelete();
            $table->string('action_type');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_audit_logs');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('retailer_profiles');
        Schema::dropIfExists('supplier_profiles');
        Schema::dropIfExists('business_verifications');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('platform_admins');
    }
};
