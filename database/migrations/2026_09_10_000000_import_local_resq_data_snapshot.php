<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SNAPSHOT_FILE = 'resq_local_data_snapshot.ndjson.gz';

    public function up(): void
    {
        if (! filter_var(env('RESQ_IMPORT_LOCAL_DATA', false), FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('Set RESQ_IMPORT_LOCAL_DATA=true before running this local data import migration.');
        }

        $path = database_path('data/' . self::SNAPSHOT_FILE);
        if (! is_file($path)) {
            throw new RuntimeException("Local data snapshot not found: {$path}");
        }

        DB::disableQueryLog();

        $handle = gzopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException("Unable to open local data snapshot: {$path}");
        }

        $manifest = $this->readManifest($handle);
        $tables = array_map(static fn (array $table): string => $table['name'], $manifest['tables']);

        Schema::disableForeignKeyConstraints();

        try {
            foreach (array_reverse($tables) as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Target table does not exist: {$table}");
                }

                DB::table($table)->delete();
            }

            $buffers = [];
            while (! gzeof($handle)) {
                $line = trim((string) gzgets($handle));
                if ($line === '') {
                    continue;
                }

                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (($record['type'] ?? null) !== 'row') {
                    continue;
                }

                $table = $record['table'];
                $buffers[$table][] = $record['data'];

                if (count($buffers[$table]) >= 500) {
                    $this->insertRows($table, $buffers[$table]);
                }
            }

            foreach ($buffers as $table => $rows) {
                $this->insertRows($table, $rows);
            }

            foreach ($tables as $table) {
                $this->syncAutoIncrement($table);
            }
        } finally {
            gzclose($handle);
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // Intentionally left blank. Restoring production data should be done from a database backup.
    }

    private function readManifest($handle): array
    {
        $line = trim((string) gzgets($handle));
        if ($line === '') {
            throw new RuntimeException('Local data snapshot is empty.');
        }

        $manifest = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        if (($manifest['type'] ?? null) !== 'manifest' || ! isset($manifest['tables']) || ! is_array($manifest['tables'])) {
            throw new RuntimeException('Local data snapshot manifest is invalid.');
        }

        return $manifest;
    }

    private function insertRows(string $table, array &$rows): void
    {
        if ($rows === []) {
            return;
        }

        DB::table($table)->insert($rows);
        $rows = [];
    }

    private function syncAutoIncrement(string $table): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('EXTRA', 'like', '%auto_increment%')
            ->value('COLUMN_NAME');

        if (! $column) {
            return;
        }

        $quotedTable = str_replace('`', '``', $table);
        $max = (int) (DB::table($table)->max($column) ?? 0);

        DB::statement("ALTER TABLE `{$quotedTable}` AUTO_INCREMENT = " . ($max + 1));
    }
};
