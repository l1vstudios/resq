<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportLocalDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export-local
                            {--full : Export full database including structure}
                            {--data-only : Export data only (default)}
                            {--output= : Output directory (default: storage/app/db-export)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export local database to SQL file for production deployment';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $outputDir = $this->option('output') ?? storage_path('app/db-export');
        $full = $this->option('full');
        $dataOnly = $this->option('data-only') || !$full;

        // Ensure output directory exists
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', 3306);
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $this->info("Database: {$dbName}");
        $this->info("Host: {$dbHost}:{$dbPort}");
        $this->newLine();

        // Check mysqldump availability
        $mysqldump = trim(shell_exec('which mysqldump') ?? '');
        if (empty($mysqldump)) {
            $this->error('mysqldump not found in PATH');
            return Command::FAILURE;
        }

        $timestamp = date('Ymd_His');

        // Build mysqldump command
        $baseCmd = sprintf(
            '%s --protocol=TCP -h %s -P %d -u %s %s --set-gtid-purged=OFF --complete-insert --default-character-set=utf8mb4',
            escapeshellarg($mysqldump),
            escapeshellarg($dbHost),
            $dbPort,
            escapeshellarg($dbUser),
            $dbPass ? '-p' . escapeshellarg($dbPass) : ''
        );

        $files = [];

        if ($full) {
            $outputFile = "{$outputDir}/resq_full.sql";
            $cmd = "{$baseCmd} {$dbName} > " . escapeshellarg($outputFile) . " 2>&1";

            $this->info('Exporting full database (structure + data)...');
            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0) {
                $this->error('Export failed: ' . implode("\n", $output));
                return Command::FAILURE;
            }

            $files[] = $outputFile;
            $this->info("Exported: {$outputFile}");
        }

        if ($dataOnly) {
            $outputFile = "{$outputDir}/resq_data_only.sql";
            $cmd = "{$baseCmd} --no-create-info {$dbName} > " . escapeshellarg($outputFile) . " 2>&1";

            $this->info('Exporting data only...');
            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0) {
                $this->error('Export failed: ' . implode("\n", $output));
                return Command::FAILURE;
            }

            $files[] = $outputFile;
            $this->info("Exported: {$outputFile}");
        }

        $this->newLine();
        $this->info('Export completed successfully!');
        $this->newLine();

        // Show file sizes
        $this->info('Exported files:');
        foreach ($files as $file) {
            $size = filesize($file);
            $sizeFormatted = $this->formatBytes($size);
            $lines = count(file($file));
            $this->info(sprintf("  %s (%s, %d lines)", $file, $sizeFormatted, $lines));
        }

        $this->newLine();
        $this->info('Next steps:');
        $this->info('  1. Commit files to git: git add storage/app/db-export/');
        $this->info('  2. Push to repository: git push origin main');
        $this->info('  3. On production, run: php artisan db:seed --class=ProductionDataSeeder');

        return Command::SUCCESS;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
