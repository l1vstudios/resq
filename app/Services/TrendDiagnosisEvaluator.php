<?php

namespace App\Services;

use App\Models\MonitoringStation;
use App\Models\StationFunctionConfiguration;
use App\Models\TdeMatrixVersion;
use App\Models\TelemetryReading;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TrendDiagnosisEvaluator
{
    private const PARAMETERS = ['AT', 'RH', 'DP', 'DPS', 'AP'];

    private const DEFAULT_THRESHOLDS = [
        'AT' => 1.0,
        'RH' => 3.0,
        'DP' => 1.7,
        'DPS' => 0.8,
        'AP' => 2.0,
    ];

    private const UNITS = [
        'AT' => 'degC',
        'RH' => '%RH',
        'DP' => 'degC',
        'DPS' => 'degC',
        'AP' => 'hPa',
        'RAINFALL' => 'mm',
    ];

    public function evaluate(MonitoringStation $station, ?StationFunctionConfiguration $configuration = null, array $context = []): array
    {
        $station->loadMissing(['sensors.mappingProfile.canonicalParameter', 'sensors.mappingProfiles.canonicalParameter']);

        $windowMinutes = $this->windowMinutes($configuration?->configuration ?? [], $context);
        $to = ! empty($context['to']) ? Carbon::parse($context['to']) : now();
        $from = ! empty($context['from'])
            ? Carbon::parse($context['from'])
            : $to->copy()->subMinutes($windowMinutes);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $readings = $this->readings($station, $from, $to);
        $samples = $this->samples($readings);
        $this->deriveEndpointSamples($samples, 'DP');
        $this->deriveEndpointSamples($samples, 'DPS');

        $thresholds = $this->thresholds($configuration?->configuration ?? []);
        $parameterResults = collect(self::PARAMETERS)
            ->mapWithKeys(fn (string $parameter) => [$parameter => $this->parameterResult($parameter, $samples[$parameter] ?? [], $thresholds)])
            ->all();
        $pattern = collect(self::PARAMETERS)
            ->map(fn (string $parameter) => $parameterResults[$parameter]['classification'])
            ->all();
        $rainfall = $this->rainfallCondition($samples['RAINFALL'] ?? []);
        $matrixVersion = $this->matrixVersion($configuration?->configuration ?? []);
        $matrixRows = $configuration?->configuration['diagnosis_matrix'] ?? $matrixVersion?->matrix_rows;
        $matrix = $this->matrixMatch($pattern, $matrixRows);
        $fallback = $matrix ? null : $this->fallbackInterpretation($pattern, $parameterResults);
        $missing = collect($parameterResults)
            ->filter(fn (array $result) => $result['classification'] === '?')
            ->keys()
            ->values()
            ->all();
        $hasDiagnosticSamples = collect($parameterResults)
            ->contains(fn (array $result) => ($result['sample_count'] ?? 0) > 0);

        return [
            'evaluation_state' => $readings->isEmpty() || ! $hasDiagnosticSamples
                ? 'insufficient_data'
                : ($missing === [] ? 'evaluated' : 'partial'),
            'window' => [
                'minutes' => $windowMinutes,
                'from' => $from->toISOString(),
                'to' => $to->toISOString(),
                'reading_count' => $readings->count(),
            ],
            'condition_current' => $rainfall,
            'parameters' => $parameterResults,
            'combination_pattern' => [
                'order' => self::PARAMETERS,
                'symbols' => $pattern,
                'key' => implode('', $pattern),
            ],
            'diagnosis' => [
                'matrix_status' => $matrix ? 'matched' : ($matrixRows ? 'matrix_not_matched' : 'matrix_not_configured'),
                'matrix_version' => $matrixVersion ? [
                    'id' => $matrixVersion->id,
                    'matrix_code' => $matrixVersion->matrix_code,
                    'name' => $matrixVersion->name,
                    'version_label' => $matrixVersion->version_label,
                ] : null,
                'matrix_match' => $matrix,
                'fallback_interpretation' => $fallback,
                'missing_parameters' => $missing,
            ],
            'thresholds' => $thresholds,
            'derived_data' => [
                'dew_point_formula' => 'Magnus formula',
                'dew_point_spread_formula' => 'Air Temperature - Dew Point',
            ],
        ];
    }

    private function readings(MonitoringStation $station, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $sensorIds = $station->sensors->pluck('id')->filter()->values();

        if ($sensorIds->isEmpty()) {
            return collect();
        }

        return TelemetryReading::with(['sensor.mappingProfile.canonicalParameter'])
            ->whereIn('sensor_id', $sensorIds)
            ->whereBetween('received_at', [$from, $to])
            ->orderBy('received_at')
            ->orderBy('id')
            ->limit(10000)
            ->get();
    }

    private function samples(Collection $readings): array
    {
        $samples = collect([...self::PARAMETERS, 'RAINFALL'])
            ->mapWithKeys(fn (string $parameter) => [$parameter => []])
            ->all();

        foreach ($readings as $reading) {
            $timestamp = $reading->received_at;
            $rowValues = [];
            $items = collect($reading->parameter_values ?? [])
                ->filter(fn ($item) => is_array($item))
                ->values();

            if ($items->isEmpty()) {
                $items = collect([[
                    'parameter' => $reading->sensor?->mappingProfile?->canonicalParameter?->field_identity
                        ?? $reading->sensor?->parameter
                        ?? $reading->sensor?->type,
                    'value' => $reading->numeric_value ?? $reading->value,
                ]]);
            }

            foreach ($items as $item) {
                $parameter = $this->parameterAlias($item);
                $value = $this->numericValue($item['value'] ?? $item['numeric_value'] ?? $item['raw'] ?? null);

                if (! $parameter || $value === null || ! $timestamp) {
                    continue;
                }

                $sample = [
                    'timestamp' => $timestamp->toISOString(),
                    'value' => $value,
                    'unit' => self::UNITS[$parameter] ?? ($item['unit'] ?? null),
                    'source' => 'measured',
                ];
                $samples[$parameter][] = $sample;
                $rowValues[$parameter] = $sample;
            }

            if (isset($rowValues['AT'], $rowValues['RH'])) {
                $dewPoint = $this->dewPoint($rowValues['AT']['value'], $rowValues['RH']['value']);
                $samples['DP'][] = [
                    'timestamp' => $timestamp->toISOString(),
                    'value' => $dewPoint,
                    'unit' => self::UNITS['DP'],
                    'source' => 'derived',
                ];
                $samples['DPS'][] = [
                    'timestamp' => $timestamp->toISOString(),
                    'value' => $rowValues['AT']['value'] - $dewPoint,
                    'unit' => self::UNITS['DPS'],
                    'source' => 'derived',
                ];
            }
        }

        foreach ($samples as $parameter => $items) {
            $samples[$parameter] = collect($items)
                ->sortBy('timestamp')
                ->values()
                ->all();
        }

        return $samples;
    }

    private function deriveEndpointSamples(array &$samples, string $parameter): void
    {
        if (! empty($samples[$parameter])) {
            return;
        }

        $temperature = $samples['AT'] ?? [];
        $humidity = $samples['RH'] ?? [];
        $dewPoint = $samples['DP'] ?? [];

        if ($parameter === 'DP' && $temperature !== [] && $humidity !== []) {
            $samples['DP'] = [
                $this->derivedDewPointSample($temperature[0], $humidity[0]),
                $this->derivedDewPointSample(end($temperature), end($humidity)),
            ];
        }

        if ($parameter === 'DPS' && $temperature !== [] && $dewPoint !== []) {
            $samples['DPS'] = [
                $this->derivedDewPointSpreadSample($temperature[0], $dewPoint[0]),
                $this->derivedDewPointSpreadSample(end($temperature), end($dewPoint)),
            ];
        }
    }

    private function derivedDewPointSample(array $temperature, array $humidity): array
    {
        return [
            'timestamp' => max($temperature['timestamp'], $humidity['timestamp']),
            'value' => $this->dewPoint((float) $temperature['value'], (float) $humidity['value']),
            'unit' => self::UNITS['DP'],
            'source' => 'derived_endpoint',
        ];
    }

    private function derivedDewPointSpreadSample(array $temperature, array $dewPoint): array
    {
        return [
            'timestamp' => max($temperature['timestamp'], $dewPoint['timestamp']),
            'value' => (float) $temperature['value'] - (float) $dewPoint['value'],
            'unit' => self::UNITS['DPS'],
            'source' => 'derived_endpoint',
        ];
    }

    private function parameterResult(string $parameter, array $samples, array $thresholds): array
    {
        $threshold = (float) ($thresholds[$parameter] ?? self::DEFAULT_THRESHOLDS[$parameter]);

        if (count($samples) < 2) {
            return [
                'value_start' => $samples[0]['value'] ?? null,
                'value_end' => $samples[0]['value'] ?? null,
                'change' => null,
                'threshold' => $threshold,
                'classification' => '?',
                'label' => 'Data tidak cukup',
                'sample_count' => count($samples),
                'unit' => self::UNITS[$parameter],
            ];
        }

        $start = $samples[0];
        $end = $samples[count($samples) - 1];
        $change = (float) $end['value'] - (float) $start['value'];

        return [
            'value_start' => round((float) $start['value'], 3),
            'value_end' => round((float) $end['value'], 3),
            'change' => round($change, 3),
            'threshold' => $threshold,
            'classification' => $this->classify($change, $threshold),
            'label' => $this->classificationLabel($parameter, $this->classify($change, $threshold)),
            'sample_count' => count($samples),
            'unit' => self::UNITS[$parameter],
            'source_start' => $start['source'] ?? null,
            'source_end' => $end['source'] ?? null,
        ];
    }

    private function classify(float $change, float $threshold): string
    {
        if ($change < -$threshold) {
            return '<';
        }

        if ($change > $threshold) {
            return '>';
        }

        return '=';
    }

    private function rainfallCondition(array $samples): array
    {
        $latest = $samples[count($samples) - 1] ?? null;

        if (! $latest) {
            return [
                'state' => 'UNKNOWN',
                'basis' => 'Rainfall data tidak tersedia dalam window evaluasi.',
                'rainfall_value' => null,
                'threshold' => 0.4,
            ];
        }

        $value = (float) $latest['value'];

        return [
            'state' => $value > 0.4 ? 'BASAH' : 'KERING',
            'basis' => 'Rainfall > 0.4 mm dianggap terdeteksi.',
            'rainfall_value' => round($value, 3),
            'threshold' => 0.4,
            'timestamp' => $latest['timestamp'],
        ];
    }

    private function matrixMatch(array $pattern, mixed $matrix): ?array
    {
        if (! is_array($matrix) || $matrix === []) {
            return null;
        }

        $key = implode('', $pattern);

        if (isset($matrix[$key]) && is_array($matrix[$key])) {
            return ['pattern' => $key, ...$matrix[$key]];
        }

        foreach ($matrix as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rowPattern = $row['pattern'] ?? collect(self::PARAMETERS)
                ->map(fn (string $parameter) => $row[$parameter] ?? $row[Str::lower($parameter)] ?? null)
                ->implode('');

            if ($rowPattern === $key) {
                return ['pattern' => $key, ...$row];
            }
        }

        return null;
    }

    private function matrixVersion(array $configuration): ?TdeMatrixVersion
    {
        $matrixId = $configuration['tde_matrix_version_id'] ?? null;

        if (! $matrixId) {
            return null;
        }

        return TdeMatrixVersion::where('status', 'active')->find($matrixId);
    }

    private function fallbackInterpretation(array $pattern, array $parameters): array
    {
        [$at, $rh, $dp, $dps, $ap] = $pattern;

        if (in_array('?', $pattern, true)) {
            return [
                'type' => 'incomplete',
                'summary' => 'Data belum cukup untuk diagnosis TDE penuh.',
                'basis' => 'Minimal dua sampel untuk AT, RH, DP, DPS, dan AP diperlukan dalam window.',
            ];
        }

        if (($rh === '>' || $dp === '>') && $dps === '<') {
            return [
                'type' => 'projection',
                'summary' => 'Arah Kering -> Basah',
                'basis' => 'Kelembapan atau dew point meningkat dan dew-point spread menyempit.',
            ];
        }

        if (($rh === '<' || $dp === '<') && $dps === '>') {
            return [
                'type' => 'projection',
                'summary' => 'Arah Basah -> Kering',
                'basis' => 'Kelembapan atau dew point menurun dan dew-point spread melebar.',
            ];
        }

        if (collect($pattern)->every(fn (string $symbol) => $symbol === '=')) {
            return [
                'type' => 'projection',
                'summary' => 'Stabil',
                'basis' => 'Seluruh parameter tidak berubah signifikan terhadap threshold.',
            ];
        }

        return [
            'type' => 'pattern_ready',
            'summary' => 'Combination Pattern siap dicocokkan ke Matrix TDE resmi.',
            'basis' => 'Matrix 243 kombinasi belum tersedia pada konfigurasi station.',
            'supporting_changes' => collect($parameters)->map(fn (array $item) => $item['change'])->all(),
            'pressure_trend' => $ap,
            'temperature_trend' => $at,
        ];
    }

    private function parameterAlias(array $item): ?string
    {
        $name = Str::lower(preg_replace(
            '/[^a-z0-9]+/i',
            '',
            (string) ($item['parameter'] ?? $item['canonical_field'] ?? $item['field'] ?? $item['label'] ?? $item['source_parameter'] ?? '')
        ));

        return match (true) {
            in_array($name, ['temperature', 'airtemperature', 'atmospherictemperature'], true) => 'AT',
            in_array($name, ['humidity', 'relativehumidity', 'atmospherichumidity'], true) => 'RH',
            in_array($name, ['pressure', 'atmosphericpressure'], true) => 'AP',
            in_array($name, ['rainfall', 'rain'], true) => 'RAINFALL',
            in_array($name, ['dewpoint', 'dp'], true) => 'DP',
            in_array($name, ['dewpointspread', 'dps'], true) => 'DPS',
            default => null,
        };
    }

    private function numericValue(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value) && preg_match('/-?\d+(?:[.,]\d+)?/', $value, $match)) {
            return (float) str_replace(',', '.', $match[0]);
        }

        return null;
    }

    private function dewPoint(float $temperatureC, float $humidityPct): float
    {
        $humidityRatio = min(max($humidityPct / 100, 0.01), 1.0);
        $gamma = log($humidityRatio) + ((17.62 * $temperatureC) / (243.12 + $temperatureC));

        return (243.12 * $gamma) / (17.62 - $gamma);
    }

    private function classificationLabel(string $parameter, string $classification): string
    {
        if ($classification === '=') {
            return 'Tidak berubah signifikan';
        }

        if ($parameter === 'DPS') {
            return $classification === '<' ? 'Menyempit' : 'Melebar';
        }

        return $classification === '<' ? 'Menurun' : 'Meningkat';
    }

    private function windowMinutes(array $configuration, array $context): int
    {
        $window = $context['data_window'] ?? $configuration['data_window'] ?? ['value' => 30, 'unit' => 'minutes'];
        $value = max((int) ($window['value'] ?? 30), 1);

        return match ($window['unit'] ?? 'minutes') {
            'days' => $value * 1440,
            'hours' => $value * 60,
            default => $value,
        };
    }

    private function thresholds(array $configuration): array
    {
        return array_replace(self::DEFAULT_THRESHOLDS, $configuration['thresholds'] ?? []);
    }
}
