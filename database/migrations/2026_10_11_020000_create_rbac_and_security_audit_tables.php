<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance users table with company tenancy and RBAC role
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 30)->default('owner')->after('company_id'); // platform_admin, owner, manager, staff, driver
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });

        // 2. Dedicated Security Audit Log for unauthorized attempts & IDOR monitoring
        Schema::create('security_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('event_type', 60); // idor_attempt, unauthorized_tenant_access, role_permission_denied, suspicious_id_tampering
            $table->string('severity', 20)->default('warning'); // info, warning, critical
            $table->string('endpoint')->nullable();
            $table->string('method', 10)->default('GET');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'role', 'is_active']);
        });
    }
};
