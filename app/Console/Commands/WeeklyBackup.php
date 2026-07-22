<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WeeklyBackup extends Command
{
    protected $signature = 'backup:weekly {--email=pauljohns730@gmail.com}';
    protected $description = 'Create weekly database backup and send via email';

    public function handle(): int
    {
        $email = $this->option('email');
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupFileName = "microfinance_backup_{$timestamp}.sql";
        $zipFileName = "microfinance_backup_{$timestamp}.zip";
        $backupPath = storage_path("app/backups/{$backupFileName}");
        $zipPath = storage_path("app/backups/{$zipFileName}");

        $this->info('Starting weekly backup...');
        
        // Ensure backups directory exists
        if (!Storage::exists('backups')) {
            Storage::makeDirectory('backups');
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

        // Cleanup old backups (keep last 4 weeks)
        $this->info('Cleaning up old backups...');
        $this->cleanupOldBackups();

        // Delete temporary SQL file
        Storage::delete("backups/{$backupFileName}");

        $this->info('Weekly backup completed successfully!');
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

    private function cleanupOldBackups()
    {
        $backups = Storage::files('backups');
        $backupFiles = array_filter($backups, function ($file) {
            return str_ends_with($file, '.zip');
        });

        // Sort by modification time (newest first)
        usort($backupFiles, function ($a, $b) {
            return Storage::lastModified($b) - Storage::lastModified($a);
        });

        // Keep only the 4 most recent backups
        $filesToDelete = array_slice($backupFiles, 4);
        
        foreach ($filesToDelete as $file) {
            Storage::delete($file);
            $this->info("Deleted old backup: " . basename($file));
        }
    }
}
