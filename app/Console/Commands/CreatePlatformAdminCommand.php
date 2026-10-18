<?php

namespace App\Console\Commands;

use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreatePlatformAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketlink:create-admin 
                            {--email= : The email address for the Super Admin}
                            {--name= : The full name for the Super Admin}
                            {--password= : The secure password for the Super Admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Securely create or update a MarketLink Platform Super Admin account via CLI';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==============================================');
        $this->info('  MarketLink B2B — Secure Platform Admin Setup');
        $this->info('==============================================');

        $email = $this->option('email') ?: $this->ask('Enter Super Admin Email:');
        $name = $this->option('name') ?: $this->ask('Enter Super Admin Full Name:', 'Super Admin (سەرپەرشتیاری گشتی)');
        $password = $this->option('password') ?: $this->secret('Enter Secure Password (min 8 characters):');

        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => ['required', 'string', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $err) {
                $this->error(' - ' . $err);
            }
            return self::FAILURE;
        }

        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            if (!$this->confirm("User [{$email}] already exists with role [{$existingUser->role}]. Promote to platform_admin and update password?", true)) {
                $this->warn('Operation cancelled.');
                return self::SUCCESS;
            }

            $existingUser->update([
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'platform_admin',
                'company_id' => null,
                'is_active' => true,
            ]);

            SecurityAuditLog::create([
                'event_type' => 'platform_admin_promoted_cli',
                'description' => "هەژماری بەکارهێنەر ({$email}) لە ڕێگەی فەرمانی پارێزراوی CLI بۆ Super Admin بەرزکرایەوە.",
                'ip_address' => '127.0.0.1 (CLI)',
                'severity' => 'critical',
                'user_id' => $existingUser->id,
            ]);

            $this->info("✓ Successfully promoted [{$email}] to Platform Super Admin.");
            return self::SUCCESS;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'platform_admin',
            'company_id' => null,
            'is_active' => true,
        ]);

        SecurityAuditLog::create([
            'event_type' => 'platform_admin_created_cli',
            'description' => "هەژماری نوێی Super Admin ({$email}) لە ڕێگەی فەرمانی پارێزراوی CLI دروستکرا.",
            'ip_address' => '127.0.0.1 (CLI)',
            'severity' => 'critical',
            'user_id' => $user->id,
        ]);

        $this->info("✓ Successfully created Super Admin [{$email}].");
        $this->info("  Role: platform_admin");
        $this->info("  Status: Active");

        return self::SUCCESS;
    }
}
