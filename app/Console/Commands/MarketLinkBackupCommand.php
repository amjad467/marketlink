<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\B2bBackupService;

class MarketLinkBackupCommand extends Command
{
    protected $signature = 'marketlink:backup 
                            {--media : Include uploaded media files in the backup} 
                            {--description= : Custom note or description for this backup}';

    protected $description = 'Create a secure and verified database & assets backup for MarketLink';

    public function handle(B2bBackupService $backupService): int
    {
        $this->info('دەستپێکردنی دروستکردنی کۆپی پارێزەری داتابەیس (MarketLink Backup)...');

        $includeMedia = (bool) $this->option('media');
        $description = (string) ($this->option('description') ?: 'Artisan console backup');

        try {
            $result = $backupService->createBackup([
                'include_media' => $includeMedia,
                'description' => $description,
            ]);

            $this->newLine();
            $this->info('✓ باکئەپ بە سەرکەوتوویی دروستکرا!');
            $this->table(
                ['تایبەتمەندی', 'بەها'],
                [
                    ['ناوی فایل', $result['filename']],
                    ['قەبارە', $result['size_formatted']],
                    ['جۆری داتابەیس', $result['manifest']['db_connection']],
                    ['کۆدی پەنجەمۆر (SHA256)', substr($result['checksum'], 0, 16) . '...'],
                    ['میدیای هاوپێچکراو', $includeMedia ? 'بەڵێ (' . $result['manifest']['media_files_count'] . ' فایل)' : 'نەخێر'],
                    ['کۆی بەکارهێنەران', $result['manifest']['record_counts']['users']],
                    ['کۆی کۆمپانیاکان', $result['manifest']['record_counts']['companies']],
                    ['کۆی بەرهەمەکان', $result['manifest']['record_counts']['products']],
                    ['کۆی داواکارییەکان', $result['manifest']['record_counts']['purchase_orders']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('شکستی هێنا لە دروستکردنی باکئەپ: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
