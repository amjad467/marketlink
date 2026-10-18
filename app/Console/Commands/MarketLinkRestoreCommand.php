<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\B2bBackupService;

class MarketLinkRestoreCommand extends Command
{
    protected $signature = 'marketlink:restore 
                            {file : The filename of the backup archive in storage/app/backups} 
                            {--verify-only : Only verify the integrity of the backup without restoring} 
                            {--force : Force restore without interactive confirmation}';

    protected $description = 'Safely restore MarketLink database and assets from an archive with pre-restore safety snapshot';

    public function handle(B2bBackupService $backupService): int
    {
        $file = $this->argument('file');
        $verifyOnly = (bool) $this->option('verify-only');
        $force = (bool) $this->option('force');

        $this->info("پشکنینی فایلی باکئەپ [{$file}]...");

        $verification = $backupService->verifyBackup($file);
        if (!$verification['valid']) {
            $this->error("فایلی باکئەپ دروست نییە: " . ($verification['error'] ?? 'کێشەی نەزانراو'));
            return Command::FAILURE;
        }

        $this->info('✓ فایلی باکئەپ پشکنینی تێپەڕاند و کۆدی هاوتا (Checksum) تەواوە.');
        $manifest = $verification['manifest'];
        $this->line("بەروار: " . $manifest['created_at']);
        $this->line("داتابەیس: " . $manifest['db_connection']);

        if ($verifyOnly) {
            $this->info('ئۆپەراسیۆنەکە تەنها بۆ پشکنین بوو. هیچ گۆڕانکارییەک لە سیستەم نەکرا.');
            return Command::SUCCESS;
        }

        if (!$force) {
            $confirmed = $this->confirm(
                'ئاگاداری: ئەم کردارە داتابەیسی ئێستا دەگۆڕێتەوە بە باکئەپەکە. ئایا دڵنیایت لە ئەنجامدانی؟',
                false
            );

            if (!$confirmed) {
                $this->warn('کرداری گەڕاندنەوە هەڵوەشێنرایەوە لەسەر داوای بەکارهێنەر.');
                return Command::SUCCESS;
            }
        }

        $this->info('دەستپێکردنی گەڕاندنەوە (لەگەڵ دروستکردنی باکئەپی پارێزەری لەناکاو پێش دەستکاری)...');

        try {
            $result = $backupService->restoreBackup($file, true);

            $this->newLine();
            $this->info('✓ سیستەمی MarketLink بە سەرکەوتوویی گەڕێندرایەوە!');
            $this->line("باکئەپی سەرچاوە: {$result['restored_file']}");
            $this->line("کۆپی پارێزەری نوێی دروستکراو (Safety Backup): {$result['safety_backup']}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('شکستی هێنا لە گەڕاندنەوەی باکئەپ: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
