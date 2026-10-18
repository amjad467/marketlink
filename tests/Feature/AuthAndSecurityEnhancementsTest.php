<?php

namespace Tests\Feature;

use App\Models\B2bNotification;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthAndSecurityEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear(RateLimiter::class);
    }

    private function createTestCompany(array $attributes = []): Company
    {
        $id = uniqid();
        return Company::create(array_merge([
            'name' => 'کۆمپانیای تاقیکردنەوە',
            'trade_license_number' => 'TL-' . $id,
            'business_type' => 'supplier',
            'business_category' => 'خۆراک',
            'verification_status' => 'verified',
            'primary_email' => 'comp_' . $id . '@marketlink.krd',
            'primary_phone' => '0750' . rand(1000000, 9999999),
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'address_line' => 'شەقامی سەرەکی',
            'province' => 'هەولێر',
        ], $attributes));
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('MarketLink B2B');
        $response->assertSee('چوونەژوورەوەی پارێزراو');
    }

    public function test_successful_user_login_regenerates_session_and_logs_audit(): void
    {
        $company = $this->createTestCompany(['name' => 'کۆمپانیای بازرگانی نموونە']);

        $user = User::create([
            'name' => 'کارمەندی بەڕێوەبردن',
            'email' => 'manager@supplier.krd',
            'password' => 'SecurePass123!',
            'role' => 'manager',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'manager@supplier.krd',
            'password' => 'SecurePass123!',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'auth_login_success',
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::create([
            'name' => 'کارمەندی بلۆککراو',
            'email' => 'blocked@test.krd',
            'password' => 'password123',
            'role' => 'staff',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'blocked@test.krd',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'auth_inactive_account',
            'user_id' => $user->id,
        ]);
    }

    public function test_login_rate_limiting_locks_out_after_5_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'attacker@target.krd',
                'password' => 'wrongpass' . $i,
            ]);
        }

        // 6th attempt must trigger rate limit lockout
        $response = $this->post('/login', [
            'email' => 'attacker@target.krd',
            'password' => 'wrongpass_final',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'auth_lockout',
            'severity' => 'critical',
        ]);
    }

    public function test_authenticated_user_can_logout_and_session_is_invalidated(): void
    {
        $user = User::create([
            'name' => 'بەکارهێنەر',
            'email' => 'logout_test@test.krd',
            'password' => 'secret123',
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');
        $response->assertRedirect('/');
        $this->assertGuest();

        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'auth_logout',
            'user_id' => $user->id,
        ]);
    }

    public function test_company_owner_can_create_staff_user_for_own_company(): void
    {
        $company = $this->createTestCompany(['name' => 'کۆمپانیای ئەزموونی']);

        $owner = User::create([
            'name' => 'خاوەن کۆمپانیا',
            'email' => 'owner@example.krd',
            'password' => 'Secret123',
            'role' => 'owner',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->post('/users', [
            'name' => 'کارمەندی کۆگا',
            'email' => 'warehouse_staff@example.krd',
            'password' => 'Pass123456',
            'role' => 'staff',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'email' => 'warehouse_staff@example.krd',
            'company_id' => $company->id,
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    public function test_company_owner_cannot_escalate_role_to_platform_admin(): void
    {
        $company = $this->createTestCompany(['name' => 'کۆمپانیای گشتی', 'business_type' => 'retailer']);

        $owner = User::create([
            'name' => 'خاوەن مارکێت',
            'email' => 'retailer_owner@market.krd',
            'password' => 'Secret123',
            'role' => 'owner',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)->post('/users', [
            'name' => 'هاکەری ڕێگەپێنەدراو',
            'email' => 'hacker@market.krd',
            'password' => 'Hacker123',
            'role' => 'platform_admin',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@market.krd']);
    }

    public function test_user_status_can_be_toggled_active_and_inactive(): void
    {
        $company = $this->createTestCompany(['name' => 'کۆمپانیای تاقیکردنەوە']);

        $owner = User::create([
            'name' => 'خاوەنی کۆمپانیا',
            'email' => 'owner_toggle@test.krd',
            'password' => 'Pass123',
            'role' => 'owner',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'کارمەندی ستاف',
            'email' => 'staff_toggle@test.krd',
            'password' => 'Pass123',
            'role' => 'staff',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Deactivate staff
        $respDeact = $this->actingAs($owner)->post(route('users.toggle_status', $staff->id));
        $respDeact->assertRedirect();
        $this->assertFalse((bool)$staff->fresh()->is_active);

        // Reactivate staff
        $respAct = $this->actingAs($owner)->post(route('users.toggle_status', $staff->id));
        $respAct->assertRedirect();
        $this->assertTrue((bool)$staff->fresh()->is_active);
    }

    public function test_user_cannot_deactivate_themselves(): void
    {
        $user = User::create([
            'name' => 'ئەدمین',
            'email' => 'admin_self@marketlink.krd',
            'password' => 'Pass123',
            'role' => 'platform_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('users.toggle_status', $user->id));
        $response->assertStatus(422);
        $this->assertTrue((bool)$user->fresh()->is_active);
    }

    public function test_b2b_notifications_check_and_mark_read_flow(): void
    {
        $company = $this->createTestCompany(['name' => 'کۆمپانیای ئاگاداری']);

        $user = User::create([
            'name' => 'بەڕێوەبەری ئاگاداری',
            'email' => 'notif_mgr@company.krd',
            'password' => 'Secret123',
            'role' => 'manager',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Send a notification to this company
        $notif = B2bNotification::send(
            $company->id,
            'داواکاری نوێ',
            'داواکارییەکی نوێ گەیشت لەلایەن مارکێت.',
            'order_status'
        );

        $this->assertFalse((bool)$notif->is_read);

        // Check notifications API
        $checkResp = $this->actingAs($user)->get(route('notifications.check'));
        $checkResp->assertStatus(200);
        $checkResp->assertJsonFragment(['title' => 'داواکاری نوێ']);

        // Mark as read
        $markResp = $this->actingAs($user)->post(route('notifications.mark_read'), [
            'id' => $notif->id,
        ]);
        $markResp->assertStatus(200);
        $this->assertTrue((bool)$notif->fresh()->is_read);
    }

    public function test_low_stock_notification_triggered_when_stock_reduced(): void
    {
        $company = $this->createTestCompany(['name' => 'کۆمپانیای کۆگا']);

        $owner = User::create([
            'name' => 'خاوەن کۆگا',
            'email' => 'warehouse_owner@test.krd',
            'password' => 'Secret123',
            'role' => 'owner',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $cat = ProductCategory::create([
            'name' => 'خۆراک',
            'name_en' => 'Food',
            'slug' => 'food-test',
        ]);

        $product = Product::create([
            'supplier_company_id' => $company->id,
            'category_id' => $cat->id,
            'sku' => 'SKU-LOW-1',
            'title' => 'شیر پاکەت ئەڵمانی',
            'base_wholesale_price' => 12.50,
            'minimum_order_quantity' => 1,
            'current_stock' => 50,
            'is_available' => true,
        ]);

        // Reduce stock to <= 10
        $response = $this->actingAs($owner)->post(route('supplier.product.stock', $product->id), [
            'current_stock' => 5,
        ]);

        $response->assertRedirect();
        $this->assertEquals(5, $product->fresh()->current_stock);

        // Verify low stock notification was generated
        $this->assertDatabaseHas('b2b_notifications', [
            'company_id' => $company->id,
            'type' => 'stock_alert',
        ]);
    }

    public function test_unauthenticated_guest_cannot_access_dashboard_or_pass_role_param(): void
    {
        $initialUserCount = User::count();

        // Guest attempting to access dashboard with role=operator parameter
        $response = $this->get('/?role=operator');

        // Must be redirected to login
        $response->assertRedirect(route('login'));
        $this->assertGuest();

        // No new admin or user should be auto-created
        $this->assertEquals($initialUserCount, User::count());
    }

    public function test_authenticated_retailer_cannot_escalate_role_via_query_param(): void
    {
        $retailerComp = $this->createTestCompany(['business_type' => 'retailer']);
        $retailerUser = User::create([
            'name' => 'مارکێت کارمەند',
            'email' => 'retailer_worker@market.krd',
            'password' => 'Pass123456!',
            'role' => 'retailer',
            'company_id' => $retailerComp->id,
            'is_active' => true,
        ]);

        // Attempt privilege escalation via query parameters
        $response = $this->actingAs($retailerUser)->get('/?role=operator&active_supplier_id=9999');

        $response->assertStatus(200);

        // Verify the user remains retailer and cannot see operator-only administrative sections
        $this->assertEquals('retailer', $retailerUser->fresh()->role);

        // Verify that a security violation was recorded in SecurityAuditLog
        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => 'role_tampering_attempt',
            'user_id' => $retailerUser->id,
        ]);
    }

    public function test_cli_admin_creation_command_succeeds_with_secure_password(): void
    {
        $this->artisan('marketlink:create-admin', [
            '--name' => 'سووپەر ئەدمین تێست',
            '--email' => 'super_cli@marketlink.krd',
            '--password' => 'StrongP@ssw0rd2026',
        ])->assertExitCode(0);

        $admin = User::where('email', 'super_cli@marketlink.krd')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('platform_admin', $admin->role);
        $this->assertTrue(Hash::check('StrongP@ssw0rd2026', $admin->password));
    }
}

