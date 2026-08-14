<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WeeklyBackup extends Command
{
    protected $signature = 'backup:weekly {--email=pauljohns730@gmail.com} {--type=weekly}';
    protected $description = 'Create database backup (daily or weekly) and send via email';

    public function handle(): int
    {
        $email = $this->option('email');
        $type = $this->option('type'); // 'daily' or 'weekly'
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $dateFolder = Carbon::now()->format('Y-m-d');
        $weekFolder = 'week-' . Carbon::now()->weekOfYear;
        
        $backupFileName = "microfinance_backup_{$timestamp}.sql";
        $zipFileName = "microfinance_backup_{$timestamp}.zip";
        
        // Create folder structure based on backup type
        if ($type === 'daily') {
            $backupPath = storage_path("app/backups/daily/{$dateFolder}/{$backupFileName}");
            $zipPath = storage_path("app/backups/daily/{$dateFolder}/{$zipFileName}");
            $this->info('Starting daily backup...');
        } else {
            $backupPath = storage_path("app/backups/weekly/{$weekFolder}/{$backupFileName}");
            $zipPath = storage_path("app/backups/weekly/{$weekFolder}/{$zipFileName}");
            $this->info('Starting weekly backup...');
        }
        
        // Ensure backups directory structure exists
        if (!Storage::exists('backups')) {
            Storage::makeDirectory('backups');
        }
        if (!Storage::exists('backups/daily')) {
            Storage::makeDirectory('backups/daily');
        }
        if (!Storage::exists('backups/weekly')) {
            Storage::makeDirectory('backups/weekly');
        }
        if ($type === 'daily' && !Storage::exists("backups/daily/{$dateFolder}")) {
            Storage::makeDirectory("backups/daily/{$dateFolder}");
        }
        if ($type === 'weekly' && !Storage::exists("backups/weekly/{$weekFolder}")) {
            Storage::makeDirectory("backups/weekly/{$weekFolder}");
        }

        // Ensure the physical directory exists
        if (!is_dir(dirname($backupPath))) {
            mkdir(dirname($backupPath), 0755, true);
        }

        // Get database configuration
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Create database dump using mysqldump
        $this->info('Creating database dump...');
        $command = sprintf(
            'mysqldump -h%s -P%s -u%s -p%s --ssl=0 %s > %s 2>&1',
            $dbHost,
            $dbPort,
            $dbUser,
            $dbPass,
            $dbName,
            $backupPath
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->error('Database dump failed!');
            return self::FAILURE;
        }

        $this->info('Database dump created successfully.');

        // Create zip file
        $this->info('Creating zip archive...');
        $zip = new \ZipArchive();
        
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            $this->error('Could not create zip file!');
            Storage::delete("backups/{$backupFileName}");
            return self::FAILURE;
        }

        // Add database dump to zip
        $zip->addFile($backupPath, $backupFileName);

        // Optionally add important files (storage/app/public, .env)
        if (Storage::exists('public')) {
            $this->info('Adding public storage files...');
            $this->addFolderToZip(storage_path('app/public'), 'public', $zip);
        }

        $zip->close();

        // Get file size
        $fileSize = filesize($zipPath);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);

        $this->info("Backup created: {$zipFileName} ({$fileSizeMB} MB)");

        // Send email with backup
        $this->info('Sending backup via email...');
        
        try {
            Mail::raw(
                "Weekly System Backup\n\n" .
                "Date: " . Carbon::now()->format('Y-m-d H:i:s') . "\n" .
                "Database: {$dbName}\n" .
                "File: {$zipFileName}\n" .
                "Size: {$fileSizeMB} MB\n\n" .
                "This is an automated backup. Please keep it safe.",
                function ($message) use ($email, $zipPath, $zipFileName) {
                    $message->to($email)
                        ->subject('Microfinance Weekly Backup - ' . Carbon::now()->format('Y-m-d'))
                        ->attach($zipPath, ['as' => $zipFileName, 'mime' => 'application/zip']);
                }
            );

            $this->info('Backup sent successfully to: ' . $email);
        } catch (\Exception $e) {
            $this->error('Failed to send email: ' . $e->getMessage());
            // Keep the backup file locally even if email fails
            $this->warn('Backup file saved locally at: ' . $zipPath);
        }

        // Cleanup old backups based on type
        $this->info('Cleaning up old backups...');
        if ($type === 'daily') {
            $this->cleanupOldDailyBackups();
        } else {
            $this->cleanupOldWeeklyBackups();
        }

        // Delete temporary SQL file
        if ($type === 'daily') {
            Storage::delete("backups/daily/{$dateFolder}/{$backupFileName}");
        } else {
            Storage::delete("backups/weekly/{$weekFolder}/{$backupFileName}");
        }

        $backupType = ucfirst($type);
        $this->info("{$backupType} backup completed successfully!");
        return self::SUCCESS;
    }

    private function addFolderToZip($folder, $zipFolder, $zip)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($folder) + 1);
                $zip->addFile($filePath, $zipFolder . '/' . $relativePath);
            }
        }
    }

    private function cleanupOldWeeklyBackups()
    {
        $backups = Storage::directories('backups/weekly');
        
        // Sort by modification time (newest first)
        usort($backups, function ($a, $b) {
            return Storage::lastModified($b) - Storage::lastModified($a);
        });

        // Keep only the 4 most recent weekly folders
        $foldersToDelete = array_slice($backups, 4);
        
        foreach ($foldersToDelete as $folder) {
            Storage::deleteDirectory($folder);
            $this->info("Deleted old weekly backup folder: " . basename($folder));
        }
    }

    private function cleanupOldDailyBackups()
    {
        $dailyFolders = Storage::directories('backups/daily');
        
        // Sort by modification time (newest first)
        usort($dailyFolders, function ($a, $b) {
            return Storage::lastModified($b) - Storage::lastModified($a);
        });

        // Keep only the 7 most recent daily folders (one week)
        $foldersToDelete = array_slice($dailyFolders, 7);
        
        foreach ($foldersToDelete as $folder) {
            Storage::deleteDirectory($folder);
            $this->info("Deleted old daily backup folder: " . basename($folder));
        }
    }
}
