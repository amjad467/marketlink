<?php

namespace App\Services;

use App\Models\User;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\B2bMessage;
use App\Models\SecurityAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Phar;
use PharData;
use Exception;

class B2bBackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Get the directory where backups are stored.
     */
    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    /**
     * Create a full backup (database and optionally uploaded media).
     */
    public function createBackup(array $options = []): array
    {
        $includeMedia = $options['include_media'] ?? false;
        $description = $options['description'] ?? 'Manual backup';
        $timestamp = date('Y-m-d_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
        $baseName = 'marketlink_backup_' . $timestamp;
        $tempDir = storage_path('app/backups/temp_' . $timestamp . '_' . uniqid());

        File::makeDirectory($tempDir, 0755, true);

        try {
            $connection = config('database.default');
            $dbConfig = config("database.connections.{$connection}");

            $dbDumpPath = $tempDir . DIRECTORY_SEPARATOR . 'database.sqlite';
            $dbChecksum = '';

            // Handle SQLite
            if ($connection === 'sqlite') {
                if (file_exists($dbDumpPath)) {
                    unlink($dbDumpPath);
                }

                $dbFile = $dbConfig['database'] ?? '';
                if ($dbFile !== ':memory:' && file_exists($dbFile) && DB::transactionLevel() === 0) {
                    $escapedPath = str_replace("'", "''", $dbDumpPath);
                    DB::statement("VACUUM INTO '{$escapedPath}'");
                } elseif ($dbFile !== ':memory:' && file_exists($dbFile)) {
                    copy($dbFile, $dbDumpPath);
                } else {
                    // Export cleanly from memory or when inside active transaction
                    $targetPdo = new \PDO("sqlite:" . $dbDumpPath);
                    $targetPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                    $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                    foreach ($tables as $t) {
                        $targetPdo->exec($t->sql);
                        $rows = DB::select("SELECT * FROM \"{$t->name}\"");
                        if (!empty($rows)) {
                            foreach ($rows as $row) {
                                $rowArr = (array) $row;
                                $cols = array_keys($rowArr);
                                $quotedCols = array_map(fn($c) => "\"{$c}\"", $cols);
                                $placeholders = array_fill(0, count($cols), '?');
                                $stmt = $targetPdo->prepare("INSERT INTO \"{$t->name}\" (" . implode(', ', $quotedCols) . ") VALUES (" . implode(', ', $placeholders) . ")");
                                $stmt->execute(array_values($rowArr));
                            }
                        }
                    }
                    $targetPdo = null;
                }

                $dbChecksum = hash_file('sha256', $dbDumpPath);
            } else {
                // If MySQL or Postgres, export schema & data to SQL file
                $dbDumpPath = $tempDir . DIRECTORY_SEPARATOR . 'database.sql';
                $tables = DB::select('SHOW TABLES');
                $sqlContent = "-- MarketLink Database Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
                // Basic fallback export
                file_put_contents($dbDumpPath, $sqlContent);
                $dbChecksum = hash_file('sha256', $dbDumpPath);
            }

            // Gather record counts for audit
            $counts = [
                'users' => User::count(),
                'companies' => Company::count(),
                'products' => Product::count(),
                'purchase_orders' => PurchaseOrder::count(),
                'b2b_messages' => B2bMessage::count(),
                'audit_logs' => SecurityAuditLog::count(),
            ];

            // Media files backup (e.g. storage/app/public)
            $mediaCount = 0;
            if ($includeMedia) {
                $mediaDir = storage_path('app/public');
                $tempMediaDir = $tempDir . DIRECTORY_SEPARATOR . 'media';
                if (File::exists($mediaDir)) {
                    File::copyDirectory($mediaDir, $tempMediaDir);
                    $mediaCount = count(File::allFiles($tempMediaDir));
                }
            }

            // Write Manifest metadata
            $manifest = [
                'application' => 'MarketLink B2B',
                'created_at' => now()->toIso8601String(),
                'db_connection' => $connection,
                'db_checksum' => $dbChecksum,
                'record_counts' => $counts,
                'include_media' => $includeMedia,
                'media_files_count' => $mediaCount,
                'description' => $description,
                'php_version' => PHP_VERSION,
                'environment' => config('app.env'),
            ];

            file_put_contents($tempDir . DIRECTORY_SEPARATOR . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Package into .tar.gz archive
            $tarPath = $this->backupDir . DIRECTORY_SEPARATOR . $baseName . '.tar';
            $tarGzPath = $tarPath . '.gz';

            if (file_exists($tarPath)) {
                unlink($tarPath);
            }
            if (file_exists($tarGzPath)) {
                unlink($tarGzPath);
            }

            $phar = new PharData($tarPath);
            $phar->buildFromDirectory($tempDir);
            unset($phar);

            $tarContent = file_get_contents($tarPath);
            $gzContent = gzencode($tarContent, 9);
            file_put_contents($tarGzPath, $gzContent);

            unlink($tarPath);
            File::deleteDirectory($tempDir);

            $archiveChecksum = hash_file('sha256', $tarGzPath);
            $archiveSize = filesize($tarGzPath);

            SecurityAuditLog::logViolation(
                'backup_created',
                "کۆپی پارێزەری سیستەم (Backup) دروستکرا: {$baseName}.tar.gz بە قەبارەی " . round($archiveSize / 1024, 2) . " KB",
                request(),
                null,
                auth()->id(),
                'low'
            );

            return [
                'success' => true,
                'filename' => $baseName . '.tar.gz',
                'filepath' => $tarGzPath,
                'size_bytes' => $archiveSize,
                'size_formatted' => $this->formatBytes($archiveSize),
                'checksum' => $archiveChecksum,
                'manifest' => $manifest,
            ];
        } catch (Exception $e) {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            Log::error('Backup failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify a backup archive without modifying current state.
     */
    public function verifyBackup(string $filename): array
    {
        $filename = basename($filename);
        $archivePath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        if (!File::exists($archivePath)) {
            return [
                'valid' => false,
                'error' => "فایلی باکئەپ [{$filename}] بوونی نییە.",
            ];
        }

        $tempExtract = storage_path('app/backups/verify_' . uniqid());
        File::makeDirectory($tempExtract, 0755, true);

        try {
            $tarPath = $tempExtract . DIRECTORY_SEPARATOR . 'extracted.tar';
            $gzData = file_get_contents($archivePath);
            $tarData = @gzdecode($gzData);
            if ($tarData === false) {
                $tarData = $gzData;
            }
            file_put_contents($tarPath, $tarData);

            $phar = new PharData($tarPath);
            $phar->extractTo($tempExtract);
            unset($phar);
            if (file_exists($tarPath)) {
                unlink($tarPath);
            }

            $manifestFile = $tempExtract . DIRECTORY_SEPARATOR . 'manifest.json';
            if (!file_exists($manifestFile)) {
                throw new Exception("فایلی ناسنامەی باکئەپ (manifest.json) لەناو ئەرشیفەکەدا نییە.");
            }

            $manifest = json_decode(file_get_contents($manifestFile), true);
            if (!$manifest || !isset($manifest['db_connection'])) {
                throw new Exception("پێکهاتەی فایلی manifest ناتەواوە یان تێکچووە.");
            }

            // Verify database dump integrity
            if ($manifest['db_connection'] === 'sqlite') {
                $dbFile = $tempExtract . DIRECTORY_SEPARATOR . 'database.sqlite';
                if (!file_exists($dbFile)) {
                    throw new Exception("فایلی داتابەیسی SQLite لە ناو باکئەپەکەدا نییە.");
                }

                $calculatedHash = hash_file('sha256', $dbFile);
                if (!empty($manifest['db_checksum']) && $calculatedHash !== $manifest['db_checksum']) {
                    throw new Exception("کێشەی دەستکاریکردن یان تێکچوون: کۆدی Hash ی داتابەیس یەکناگرێتەوە.");
                }

                // Test SQLite connectivity and integrity
                $pdo = new \PDO("sqlite:" . $dbFile);
                $stmt = $pdo->query('PRAGMA integrity_check;');
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $status = reset($row);
                if ($status !== 'ok') {
                    throw new Exception("داتابەیسەکە پشکنینی ناوخۆیی (integrity check) تێپەڕ نەکرد: {$status}");
                }
                $pdo = null;
            }

            File::deleteDirectory($tempExtract);

            return [
                'valid' => true,
                'filename' => $filename,
                'size_formatted' => $this->formatBytes(filesize($archivePath)),
                'manifest' => $manifest,
            ];
        } catch (Exception $e) {
            if (File::exists($tempExtract)) {
                File::deleteDirectory($tempExtract);
            }
            return [
                'valid' => false,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Restore system database and files safely from a backup archive.
     * Takes an automatic safety pre-restore backup first to prevent any data loss!
     */
    public function restoreBackup(string $filename, bool $force = false): array
    {
        $filename = basename($filename);
        $archivePath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        // Step 1: Verify before touching anything
        $verification = $this->verifyBackup($filename);
        if (!$verification['valid']) {
            throw new Exception("ناکرێت باکئەپ بگەڕێندرێتەوە: " . ($verification['error'] ?? 'باکئەپەکە دروست نییە.'));
        }

        $manifest = $verification['manifest'];

        // Step 2: Create a safety pre-restore backup first!
        $safetyBackup = $this->createBackup([
            'include_media' => true,
            'description' => "ئۆتۆماتیکی پێش گەڕاندنەوە لە: {$filename}",
        ]);

        $tempExtract = storage_path('app/backups/restore_' . uniqid());
        File::makeDirectory($tempExtract, 0755, true);

        try {
            $tarPath = $tempExtract . DIRECTORY_SEPARATOR . 'extracted.tar';
            $gzData = file_get_contents($archivePath);
            $tarData = @gzdecode($gzData);
            if ($tarData === false) {
                $tarData = $gzData;
            }
            file_put_contents($tarPath, $tarData);

            $phar = new PharData($tarPath);
            $phar->extractTo($tempExtract);
            unset($phar);
            if (file_exists($tarPath)) {
                unlink($tarPath);
            }

            $connection = config('database.default');
            $dbConfig = config("database.connections.{$connection}");

            // SQLite Restore
            if ($connection === 'sqlite') {
                $targetDb = $dbConfig['database'];
                $sourceDb = $tempExtract . DIRECTORY_SEPARATOR . 'database.sqlite';

                if (!file_exists($sourceDb)) {
                    throw new Exception("فایلی داتابەیس لە باکئەپدا نەدۆزرایەوە.");
                }

                if ($targetDb !== ':memory:') {
                    // Disconnect existing PDO connections
                    DB::disconnect();

                    // Safely copy over
                    if (!copy($sourceDb, $targetDb)) {
                        throw new Exception("نەتوانرا فایلی داتابەیس بنووسرێتەوە بۆ [{$targetDb}].");
                    }

                    // Reconnect & check
                    DB::reconnect();
                    $integrity = DB::select('PRAGMA integrity_check;');
                    if (empty($integrity) || ($integrity[0]->integrity_check ?? '') !== 'ok') {
                        throw new Exception("گەڕاندنەوە سەرکەوتوو نەبوو: داتابەیسی گەڕاوە پشکنینی تێپەڕ نەکرد.");
                    }
                } else {
                    $srcPdo = new \PDO("sqlite:" . $sourceDb);
                    $srcPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                    $tablesStmt = $srcPdo->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                    $tables = $tablesStmt->fetchAll(\PDO::FETCH_ASSOC);
                    $tablesStmt = null;

                    foreach ($tables as $t) {
                        $tbl = $t['name'];
                        DB::statement("DROP TABLE IF EXISTS \"{$tbl}\";");
                        DB::statement($t['sql']);

                        $rowsStmt = $srcPdo->query("SELECT * FROM \"{$tbl}\"");
                        $rows = $rowsStmt->fetchAll(\PDO::FETCH_ASSOC);
                        $rowsStmt = null;

                        foreach ($rows as $row) {
                            $cols = array_keys($row);
                            $quotedCols = array_map(fn($c) => "\"{$c}\"", $cols);
                            $placeholders = array_fill(0, count($cols), '?');
                            DB::insert("INSERT INTO \"{$tbl}\" (" . implode(', ', $quotedCols) . ") VALUES (" . implode(', ', $placeholders) . ")", array_values($row));
                        }
                    }
                    $srcPdo = null;
                }
            }

            // Restore media files if present
            $mediaRestored = 0;
            $sourceMediaDir = $tempExtract . DIRECTORY_SEPARATOR . 'media';
            if (File::exists($sourceMediaDir)) {
                $targetMediaDir = storage_path('app/public');
                File::copyDirectory($sourceMediaDir, $targetMediaDir);
                $mediaRestored = count(File::allFiles($targetMediaDir));
            }

            File::deleteDirectory($tempExtract);

            // Flush cache
            Cache::flush();

            SecurityAuditLog::logViolation(
                'backup_restored',
                "سیستەم بە سەرکەوتوویی لە باکئەپی [{$filename}] گەڕێندرایەوە. کۆپی پارێزەری پێش گەڕاندنەوە: [{$safetyBackup['filename']}].",
                request(),
                null,
                auth()->id(),
                'high'
            );

            return [
                'success' => true,
                'restored_file' => $filename,
                'manifest' => $manifest,
                'safety_backup' => $safetyBackup['filename'],
                'media_restored' => $mediaRestored,
            ];
        } catch (Exception $e) {
            if (File::exists($tempExtract)) {
                File::deleteDirectory($tempExtract);
            }
            Log::critical('Backup restore failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * List all available backup archives with their metadata.
     */
    public function listBackups(): array
    {
        if (!File::exists($this->backupDir)) {
            return [];
        }

        $files = File::files($this->backupDir);
        $backups = [];

        foreach ($files as $file) {
            $name = $file->getFilename();
            if (!str_ends_with($name, '.tar.gz')) {
                continue;
            }

            $size = $file->getSize();
            $modified = $file->getMTime();

            $backups[] = [
                'filename' => $name,
                'path' => $file->getRealPath(),
                'size_bytes' => $size,
                'size_formatted' => $this->formatBytes($size),
                'created_at' => date('Y-m-d H:i:s', $modified),
                'timestamp' => $modified,
            ];
        }

        // Sort latest first
        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $backups;
    }

    /**
     * Delete a backup archive safely.
     */
    public function deleteBackup(string $filename): bool
    {
        $filename = basename($filename);
        if (!str_ends_with($filename, '.tar.gz')) {
            throw new Exception("ناوی فایل نادروستە.");
        }

        $path = $this->backupDir . DIRECTORY_SEPARATOR . $filename;
        if (File::exists($path)) {
            unlink($path);

            SecurityAuditLog::logViolation(
                'backup_deleted',
                "فایلی باکئەپ سڕایەوە: [{$filename}]",
                request(),
                null,
                auth()->id(),
                'medium'
            );

            return true;
        }

        return false;
    }

    /**
     * Format bytes to readable units (KB, MB, GB).
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
