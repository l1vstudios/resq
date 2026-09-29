<?php

namespace App\Services;

use App\Models\TdeMatrixVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class TdeMatrixImportService
{
    private const PARAMETERS = ['AT', 'RH', 'DP', 'DPS', 'AP'];

    private const SYMBOLS = ['<', '=', '>'];

    private const HEADER_ALIASES = [
        'AT' => ['at', 'airtemperature', 'air_temperature'],
        'RH' => ['rh', 'relativehumidity', 'relative_humidity'],
        'DP' => ['dp', 'dewpoint', 'dew_point'],
        'DPS' => ['dps', 'dewpointspread', 'dew_point_spread'],
        'AP' => ['ap', 'atmosphericpressure', 'atmospheric_pressure'],
        'type' => ['type', 'jenis', 'category', 'kategori', 'fungsi', 'function'],
        'summary' => ['summary', 'diagnosis', 'kesimpulan', 'hasil', 'output', 'result'],
        'basis' => ['basis', 'keterangan', 'reason', 'description', 'deskripsi'],
        'confidence' => ['confidence', 'keyakinan'],
        'output_code' => ['outputcode', 'output_code', 'kode', 'code', 'diagnosiscode', 'diagnosis_code'],
    ];

    public function import(UploadedFile|string $file, array $attributes): TdeMatrixVersion
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $extension = Str::lower($file instanceof UploadedFile ? $file->getClientOriginalExtension() : pathinfo($path, PATHINFO_EXTENSION));
        $filename = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($path);

        if (! $path || ! is_file($path)) {
            throw new InvalidArgumentException('TDE matrix file is not readable.');
        }

        $rows = match ($extension) {
            'xlsx' => $this->rowsFromXlsx($path),
            'csv' => $this->rowsFromCsv($path),
            default => throw new InvalidArgumentException('Use .xlsx or .csv for TDE matrix import. Legacy .xls is not supported.'),
        };

        $parsed = $this->parseMatrixRows($rows);
        $code = trim((string) ($attributes['matrix_code'] ?? ''));
        $code = $code !== '' ? $code : $this->defaultMatrixCode($attributes['name'] ?? $filename, $attributes['version_label'] ?? null);
        $projectId = $attributes['project_id'] ?? null;

        return TdeMatrixVersion::updateOrCreate(
            ['matrix_code' => $code],
            [
                'project_id' => $projectId === '' ? null : $projectId,
                'name' => $attributes['name'] ?? pathinfo($filename, PATHINFO_FILENAME),
                'version_label' => $attributes['version_label'] ?? null,
                'source_filename' => $filename,
                'matrix_rows' => $parsed['matrix'],
                'import_summary' => $parsed['summary'],
                'imported_by_user_id' => $attributes['imported_by_user_id'] ?? null,
                'status' => $attributes['status'] ?? 'active',
            ]
        );
    }

    public function parseMatrixRows(array $rows): array
    {
        $rows = collect($rows)
            ->map(fn (array $row) => array_map(fn ($value) => trim((string) $value), $row))
            ->filter(fn (array $row) => collect($row)->contains(fn (string $value) => $value !== ''))
            ->values();

        if ($rows->isEmpty()) {
            throw new InvalidArgumentException('TDE matrix file is empty.');
        }

        $headers = $this->headers($rows->shift());
        $missingHeaders = collect(self::PARAMETERS)
            ->filter(fn (string $parameter) => ! in_array($parameter, $headers, true))
            ->values()
            ->all();

        if ($missingHeaders !== []) {
            throw new InvalidArgumentException('Missing required TDE matrix columns: '.implode(', ', $missingHeaders).'.');
        }

        if (! in_array('summary', $headers, true)) {
            throw new InvalidArgumentException('Missing required TDE matrix output column: summary/diagnosis/kesimpulan.');
        }

        $matrix = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $assoc = $this->associateRow($headers, $row);

            if ($this->emptyMatrixRow($assoc)) {
                continue;
            }

            $symbols = [];

            foreach (self::PARAMETERS as $parameter) {
                $symbol = $this->normalizeSymbol($assoc[$parameter] ?? '');

                if (! in_array($symbol, self::SYMBOLS, true)) {
                    $errors[] = "Row {$rowNumber}: {$parameter} must be one of <, =, >.";

                    continue;
                }

                $symbols[$parameter] = $symbol;
            }

            if (count($symbols) !== count(self::PARAMETERS)) {
                continue;
            }

            $pattern = implode('', Arr::only($symbols, self::PARAMETERS));

            if (isset($matrix[$pattern])) {
                $errors[] = "Row {$rowNumber}: duplicate combination pattern {$pattern}.";

                continue;
            }

            $summary = trim((string) ($assoc['summary'] ?? ''));
            if ($summary === '') {
                $errors[] = "Row {$rowNumber}: summary/diagnosis is required.";

                continue;
            }

            $matrix[$pattern] = [
                'pattern' => $pattern,
                ...$symbols,
                'type' => $assoc['type'] ?? 'diagnosis',
                'summary' => $summary,
                'basis' => $assoc['basis'] ?? null,
                'confidence' => $assoc['confidence'] ?? null,
                'output_code' => $assoc['output_code'] ?? null,
            ];
        }

        $expectedPatterns = $this->expectedPatterns();
        $missingPatterns = array_values(array_diff($expectedPatterns, array_keys($matrix)));
        $extraCount = count(array_diff(array_keys($matrix), $expectedPatterns));

        if ($missingPatterns !== []) {
            $sample = implode(', ', array_slice($missingPatterns, 0, 10));
            $errors[] = 'Matrix must contain all 243 unique combinations. Missing '.count($missingPatterns).' pattern(s), e.g. '.$sample.'.';
        }

        if ($extraCount > 0) {
            $errors[] = "Matrix contains {$extraCount} pattern(s) outside the TDE combination space.";
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        ksort($matrix);

        return [
            'matrix' => $matrix,
            'summary' => [
                'row_count' => count($matrix),
                'expected_row_count' => count($expectedPatterns),
                'parameter_order' => self::PARAMETERS,
                'symbol_space' => self::SYMBOLS,
                'imported_at' => now()->toISOString(),
            ],
        ];
    }

    public function templateRows(): array
    {
        $rows = [['AT', 'RH', 'DP', 'DPS', 'AP', 'type', 'summary', 'basis', 'confidence', 'output_code']];

        foreach ($this->expectedPatterns() as $pattern) {
            $symbols = str_split($pattern);
            $rows[] = [
                $symbols[0],
                $symbols[1],
                $symbols[2],
                $symbols[3],
                $symbols[4],
                '',
                '',
                '',
                '',
                '',
            ];
        }

        return $rows;
    }

    private function rowsFromCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException('Unable to open CSV file.');
        }

        try {
            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function rowsFromXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open XLSX file.');
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheetPath = $this->firstWorksheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);

            if ($sheetXml === false) {
                throw new RuntimeException('Unable to read first worksheet from XLSX file.');
            }

            $sheet = new SimpleXMLElement($sheetXml);
            $rows = [];

            foreach ($sheet->sheetData->row as $row) {
                $cells = [];
                foreach ($row->c as $cell) {
                    $reference = (string) $cell['r'];
                    $column = $this->columnIndex($reference);
                    $cells[$column] = $this->cellValue($cell, $sharedStrings);
                }

                if ($cells !== []) {
                    ksort($cells);
                    $max = max(array_keys($cells));
                    $rows[] = collect(range(1, $max))
                        ->map(fn (int $index) => $cells[$index] ?? '')
                        ->all();
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $strings = [];
        $document = new SimpleXMLElement($xml);

        foreach ($document->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;

                continue;
            }

            $strings[] = collect($item->r ?? [])
                ->map(fn ($run) => (string) $run->t)
                ->implode('');
        }

        return $strings;
    }

    private function firstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = new SimpleXMLElement($workbookXml);
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheet = $workbook->sheets->sheet[0] ?? null;
        $relationshipId = $sheet ? (string) $sheet->attributes('r', true)->id : null;

        if (! $relationshipId) {
            return 'xl/worksheets/sheet1.xml';
        }

        $rels = new SimpleXMLElement($relsXml);
        foreach ($rels->Relationship as $relationship) {
            if ((string) $relationship['Id'] === $relationshipId) {
                $target = (string) $relationship['Target'];

                return Str::startsWith($target, 'xl/')
                    ? $target
                    : 'xl/'.ltrim($target, '/');
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            return $sharedStrings[(int) $cell->v] ?? '';
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        return (string) ($cell->v ?? '');
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $match);
        $letters = strtoupper($match[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max($index, 1);
    }

    private function headers(array $headerRow): array
    {
        return collect($headerRow)
            ->map(fn ($header) => $this->canonicalHeader((string) $header))
            ->all();
    }

    private function canonicalHeader(string $header): string
    {
        $normalized = Str::lower(preg_replace('/[^a-z0-9]+/i', '', $header) ?: $header);

        foreach (self::HEADER_ALIASES as $canonical => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $canonical;
            }
        }

        return $normalized;
    }

    private function associateRow(array $headers, array $row): array
    {
        $assoc = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $assoc[$header] = $row[$index] ?? '';
        }

        return $assoc;
    }

    private function emptyMatrixRow(array $row): bool
    {
        return collect(self::PARAMETERS)
            ->every(fn (string $parameter) => trim((string) ($row[$parameter] ?? '')) === '');
    }

    private function normalizeSymbol(string $symbol): string
    {
        $symbol = trim($symbol);

        return match (Str::lower($symbol)) {
            'lt', 'less', 'turun', 'menurun', 'menyempit', '-' => '<',
            'eq', 'same', 'tetap', 'stabil', 'tidakberubah', 'tidak berubah', '0' => '=',
            'gt', 'greater', 'naik', 'meningkat', 'melebar', '+' => '>',
            default => $symbol,
        };
    }

    private function expectedPatterns(): array
    {
        $patterns = [''];

        foreach (self::PARAMETERS as $_parameter) {
            $patterns = collect($patterns)
                ->flatMap(fn (string $prefix) => collect(self::SYMBOLS)->map(fn (string $symbol) => $prefix.$symbol))
                ->all();
        }

        return $patterns;
    }

    private function defaultMatrixCode(string $name, ?string $version): string
    {
        return Str::upper(Str::slug($name.' '.($version ?: now()->format('YmdHis')), '-'));
    }
}
