<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\B2bBackupService;

class MarketLinkBackupListCommand extends Command
{
    protected $signature = 'marketlink:backup-list';

    protected $description = 'List all available backup archives with size, date, and status';

    public function handle(B2bBackupService $backupService): int
    {
        $backups = $backupService->listBackups();

        if (empty($backups)) {
            $this->warn('هیچ فایلی باکئەپێک لە سیستەمدا بەردەست نییە.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($backups as $index => $b) {
            $rows[] = [
                $index + 1,
                $b['filename'],
                $b['size_formatted'],
                $b['created_at'],
            ];
        }

        $this->info("لیستی کۆپییە پارێزەرەکانی MarketLink (کۆی گشتی: " . count($backups) . "):");
        $this->table(['#', 'ناوی فایل', 'قەبارە', 'کاتی دروستکردن'], $rows);

        return Command::SUCCESS;
    }
}
