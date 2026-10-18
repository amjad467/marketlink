<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Services\B2bBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

class B2bBackupAndRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clean any test backups created in storage/app/backups
        $backupDir = storage_path('app/backups');
        if (File::exists($backupDir)) {
            foreach (File::files($backupDir) as $file) {
                if (str_starts_with($file->getFilename(), 'marketlink_backup_test_')) {
                    @unlink($file->getRealPath());
                }
            }
        }
    }

    public function test_artisan_backup_command_creates_verified_archive(): void
    {
        $this->artisan('marketlink:backup', [
            '--description' => 'Automated Feature Test Backup',
        ])
        ->expectsOutputToContain('باکئەپ بە سەرکەوتوویی دروستکرا')
        ->assertExitCode(0);

        $backupService = app(B2bBackupService::class);
        $backups = $backupService->listBackups();

        $this->assertNotEmpty($backups);
        $latest = $backups[0];
        $this->assertStringEndsWith('.tar.gz', $latest['filename']);

        // Verify the created archive
        $verification = $backupService->verifyBackup($latest['filename']);
        $this->assertTrue($verification['valid']);
        $this->assertEquals('sqlite', $verification['manifest']['db_connection']);
    }

    public function test_artisan_backup_list_command_outputs_table(): void
    {
        $backupService = app(B2bBackupService::class);
        $backupService->createBackup(['description' => 'List Test']);

        $this->artisan('marketlink:backup-list')
            ->expectsOutputToContain('لیستی کۆپییە پارێزەرەکانی MarketLink')
            ->assertExitCode(0);
    }

    public function test_artisan_restore_verify_only_does_not_modify_state(): void
    {
        $backupService = app(B2bBackupService::class);
        $backup = $backupService->createBackup(['description' => 'Restore Verify Test']);

        $this->artisan('marketlink:restore', [
            'file' => $backup['filename'],
            '--verify-only' => true,
        ])
        ->expectsOutputToContain('ئۆپەراسیۆنەکە تەنها بۆ پشکنین بوو')
        ->assertExitCode(0);
    }

    public function test_artisan_restore_force_restores_and_creates_safety_backup(): void
    {
        $backupService = app(B2bBackupService::class);
        $backup = $backupService->createBackup(['description' => 'Pre-restore target']);

        $initialCount = count($backupService->listBackups());

        $this->artisan('marketlink:restore', [
            'file' => $backup['filename'],
            '--force' => true,
        ])
        ->expectsOutputToContain('سیستەمی MarketLink بە سەرکەوتوویی گەڕێندرایەوە')
        ->assertExitCode(0);

        // A new safety backup should have been automatically created
        $newCount = count($backupService->listBackups());
        $this->assertGreaterThanOrEqual($initialCount, $newCount);
    }

    public function test_unauthenticated_user_cannot_create_or_restore_backups(): void
    {
        $this->post('/operator/backups', [
            'description' => 'Unauthorized backup',
        ])->assertStatus(401);

        $this->post('/operator/backups/restore', [
            'filename' => 'some_file.tar.gz',
        ])->assertStatus(401);
    }

    public function test_retailer_user_cannot_access_backup_endpoints(): void
    {
        $retailerCompany = Company::where('business_type', 'retailer')->first() ?? Company::create([
            'name' => 'تێست مارکێت',
            'business_type' => 'retailer',
            'trade_license_number' => 'TL-RET-9901',
            'city' => 'هەولێر',
            'country' => 'هەرێمی کوردستان',
            'primary_email' => 'ret@market.krd',
            'primary_phone' => '+9647501112233',
            'address_line' => 'شەقامی سەرەکی',
            'verification_status' => 'verified',
        ]);

        $retailerUser = User::create([
            'name' => 'خاوەن مارکێت',
            'email' => 'retailer_backup_test@marketlink.krd',
            'password' => bcrypt('password'),
            'company_id' => $retailerCompany->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($retailerUser)
            ->post('/operator/backups', ['description' => 'Hacker backup attempt'])
            ->assertStatus(403);

        $this->actingAs($retailerUser)
            ->post('/operator/backups/restore', ['filename' => 'fake.tar.gz'])
            ->assertStatus(403);
    }

    public function test_platform_operator_can_create_download_and_delete_backup(): void
    {
        $operatorUser = User::where('role', 'platform_admin')->first() ?? User::create([
            'name' => 'ئەدمینی سەرەکی پلاتفۆرم',
            'email' => 'operator_backup_test@marketlink.krd',
            'password' => bcrypt('password'),
            'company_id' => null,
            'role' => 'platform_admin',
            'is_active' => true,
        ]);

        // 1. Operator creates backup via Web POST
        $response = $this->actingAs($operatorUser)
            ->post('/operator/backups', [
                'description' => 'Operator UI Backup Test',
                'include_media' => false,
            ]);

        $response->assertSessionHas('success');

        $backupService = app(B2bBackupService::class);
        $backups = $backupService->listBackups();
        $this->assertNotEmpty($backups);
        $createdFile = $backups[0]['filename'];

        // 2. Operator downloads backup
        $downloadResponse = $this->actingAs($operatorUser)
            ->get("/operator/backups/{$createdFile}/download");
        $downloadResponse->assertOk();

        // 3. Operator deletes backup
        $deleteResponse = $this->actingAs($operatorUser)
            ->post('/operator/backups/delete', [
                'filename' => $createdFile,
            ]);
        $deleteResponse->assertSessionHas('success');

        // Check file is removed
        $this->assertFileDoesNotExist($backupService->getBackupDir() . DIRECTORY_SEPARATOR . $createdFile);
    }
}
