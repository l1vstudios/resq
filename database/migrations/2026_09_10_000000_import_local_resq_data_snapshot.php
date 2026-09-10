<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SNAPSHOT_FILE = 'resq_local_data_snapshot.ndjson.gz';

    public $withinTransaction = false;

    private array $columnMetadata = [];

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

        $stagingDir = null;

        try {
            $this->expandPostgresStringColumnsForSnapshot($path, $tables);
            $this->clearTables($tables);
            $stagingDir = $this->stageRows($handle, $tables);
            $orderedTables = $this->orderTablesByDependencies($tables);

            foreach ($orderedTables as $table) {
                $this->insertStagedRows($table, $this->stagingPath($stagingDir, $table));
            }

            foreach ($tables as $table) {
                $this->syncAutoIncrement($table);
            }
        } finally {
            gzclose($handle);
            if ($stagingDir) {
                $this->removeDirectory($stagingDir);
            }
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

    private function clearTables(array $tables): void
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Target table does not exist: {$table}");
            }
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $quotedTables = array_map(fn (string $table): string => $this->quoteIdentifier($table), $tables);
            DB::statement('TRUNCATE TABLE ' . implode(', ', $quotedTables) . ' RESTART IDENTITY CASCADE');

            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach (array_reverse($tables) as $table) {
                DB::table($table)->delete();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function expandPostgresStringColumnsForSnapshot(string $snapshotPath, array $tables): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $limits = $this->postgresStringColumnLimits($tables);
        if ($limits === []) {
            return;
        }

        $needsText = [];
        $handle = gzopen($snapshotPath, 'rb');
        if (! $handle) {
            throw new RuntimeException("Unable to inspect local data snapshot: {$snapshotPath}");
        }

        try {
            $this->readManifest($handle);

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
                if (! isset($limits[$table])) {
                    continue;
                }

                foreach ($limits[$table] as $column => $limit) {
                    $value = $record['data'][$column] ?? null;
                    if ($value === null) {
                        continue;
                    }

                    if ($this->stringLength((string) $value) > $limit) {
                        $needsText[$table][$column] = true;
                    }
                }
            }
        } finally {
            gzclose($handle);
        }

        foreach ($needsText as $table => $columns) {
            foreach (array_keys($columns) as $column) {
                DB::statement(
                    'ALTER TABLE ' . $this->quoteIdentifier($table)
                    . ' ALTER COLUMN ' . $this->quoteIdentifier($column)
                    . ' TYPE text'
                );
            }
        }
    }

    private function postgresStringColumnLimits(array $tables): array
    {
        $placeholders = implode(', ', array_fill(0, count($tables), '?'));
        $rows = DB::select(<<<SQL
            SELECT table_name, column_name, character_maximum_length
            FROM information_schema.columns
            WHERE table_schema = 'public'
                AND table_name IN ({$placeholders})
                AND data_type IN ('character varying', 'character')
                AND character_maximum_length IS NOT NULL
        SQL, $tables);

        $limits = [];
        foreach ($rows as $row) {
            $limits[$row->table_name][$row->column_name] = (int) $row->character_maximum_length;
        }

        return $limits;
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function stageRows($handle, array $tables): string
    {
        $baseDir = storage_path('framework/cache');
        if (! is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        $stagingDir = $baseDir . '/resq-import-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
        mkdir($stagingDir, 0755, true);

        $tableLookup = array_fill_keys($tables, true);
        $handles = [];

        try {
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
                if (! isset($tableLookup[$table])) {
                    continue;
                }

                if (! isset($handles[$table])) {
                    $handles[$table] = fopen($this->stagingPath($stagingDir, $table), 'wb');
                    if (! $handles[$table]) {
                        throw new RuntimeException("Unable to create staging file for table: {$table}");
                    }
                }

                fwrite($handles[$table], json_encode($record['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n");
            }
        } finally {
            foreach ($handles as $tableHandle) {
                fclose($tableHandle);
            }
        }

        return $stagingDir;
    }

    private function insertStagedRows(string $table, string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException("Unable to read staging file for table: {$table}");
        }

        $rows = [];

        try {
            while (($line = fgets($handle)) !== false) {
                $rows[] = $this->normalizeRow($table, json_decode(trim($line), true, 512, JSON_THROW_ON_ERROR));

                if (count($rows) >= 500) {
                    $this->insertRows($table, $rows);
                }
            }

            $this->insertRows($table, $rows);
        } finally {
            fclose($handle);
        }
    }

    private function orderTablesByDependencies(array $tables): array
    {
        $dependencies = $this->foreignKeyDependencies($tables);
        $remaining = array_fill_keys($tables, true);
        $ordered = [];

        do {
            $progress = false;

            foreach ($tables as $table) {
                if (! isset($remaining[$table])) {
                    continue;
                }

                $parents = $dependencies[$table] ?? [];
                $hasPendingParent = false;

                foreach ($parents as $parent => $_) {
                    if (isset($remaining[$parent])) {
                        $hasPendingParent = true;
                        break;
                    }
                }

                if ($hasPendingParent) {
                    continue;
                }

                $ordered[] = $table;
                unset($remaining[$table]);
                $progress = true;
            }
        } while ($progress && $remaining !== []);

        foreach ($tables as $table) {
            if (isset($remaining[$table])) {
                $ordered[] = $table;
            }
        }

        return $ordered;
    }

    private function foreignKeyDependencies(array $tables): array
    {
        $dependencies = array_fill_keys($tables, []);
        $tableLookup = array_fill_keys($tables, true);
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $rows = DB::select(<<<'SQL'
                SELECT tc.table_name AS child_table, ccu.table_name AS parent_table
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage ccu
                    ON ccu.constraint_name = tc.constraint_name
                    AND ccu.constraint_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                    AND tc.table_schema = 'public'
            SQL);
        } elseif ($driver === 'mysql') {
            $rows = DB::select(<<<'SQL'
                SELECT TABLE_NAME AS child_table, REFERENCED_TABLE_NAME AS parent_table
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND REFERENCED_TABLE_NAME IS NOT NULL
            SQL);
        } else {
            return $dependencies;
        }

        foreach ($rows as $row) {
            $child = $row->child_table;
            $parent = $row->parent_table;

            if ($child !== $parent && isset($tableLookup[$child], $tableLookup[$parent])) {
                $dependencies[$child][$parent] = true;
            }
        }

        return $dependencies;
    }

    private function stagingPath(string $stagingDir, string $table): string
    {
        return $stagingDir . '/' . md5($table) . '.ndjson';
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;
            is_dir($itemPath) ? $this->removeDirectory($itemPath) : @unlink($itemPath);
        }

        @rmdir($path);
    }

    private function normalizeRow(string $table, array $row): array
    {
        $metadata = $this->columnMetadata($table);
        $normalized = [];

        foreach ($row as $column => $value) {
            if (! isset($metadata[$column])) {
                continue;
            }

            $type = $metadata[$column];

            if ($value !== null && $type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ((int) $value === 1);
            }

            if ($value !== null && in_array($type, ['date', 'timestamp without time zone', 'timestamp with time zone', 'datetime'], true)) {
                $value = str_starts_with((string) $value, '0000-00-00') ? null : $value;
            }

            $normalized[$column] = $value;
        }

        return $normalized;
    }

    private function columnMetadata(string $table): array
    {
        if (isset($this->columnMetadata[$table])) {
            return $this->columnMetadata[$table];
        }

        if (DB::getDriverName() === 'pgsql') {
            $rows = DB::select(<<<'SQL'
                SELECT column_name, data_type
                FROM information_schema.columns
                WHERE table_schema = 'public'
                    AND table_name = ?
            SQL, [$table]);
        } else {
            $rows = DB::select(<<<'SQL'
                SELECT COLUMN_NAME AS column_name, DATA_TYPE AS data_type
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
            SQL, [$table]);
        }

        return $this->columnMetadata[$table] = collect($rows)
            ->mapWithKeys(fn ($column): array => [$column->column_name => $column->data_type])
            ->all();
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
        if (DB::getDriverName() === 'pgsql') {
            $this->syncPostgresSequence($table);

            return;
        }

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

    private function syncPostgresSequence(string $table): void
    {
        $column = DB::selectOne(<<<'SQL'
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = 'public'
                AND table_name = ?
                AND column_default LIKE 'nextval(%'
            LIMIT 1
        SQL, [$table])?->column_name;

        if (! $column) {
            return;
        }

        $sequence = DB::selectOne('SELECT pg_get_serial_sequence(?, ?) AS sequence_name', [$table, $column])?->sequence_name;
        if (! $sequence) {
            return;
        }

        $max = (int) (DB::table($table)->max($column) ?? 0);
        DB::select('SELECT setval(?::regclass, ?, ?)', [$sequence, max($max, 1), $max > 0]);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
};
