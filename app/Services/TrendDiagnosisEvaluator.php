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

    /**
     * Koefisien Magnus (varian Magnus-Tetens) untuk perhitungan dew point.
     *
     * Nilai b = 17.62 dan c = 243.12 degC adalah koefisien baku yang
     * direkomendasikan WMO (2008, CIMO Guide), identik dengan Sonntag (1990)
     * dan Alduchov & Eskridge (1996, dipakai NOAA). Valid untuk rentang suhu
     * -45 degC s.d. +60 degC dengan galat dew point < 0.4 degC.
     *
     * @see https://library.wmo.int/idurl/4/35625 (WMO-No. 8, CIMO Guide)
     */
    private const DEW_POINT_MAGNUS_B = 17.62;

    private const DEW_POINT_MAGNUS_C = 243.12;

    /**
     * Metadata ilmiah dew point untuk ditampilkan kepada peneliti sehingga
     * jelas bahwa DP & DPS adalah nilai turunan (derived), bukan hasil ukur
     * langsung sensor, lengkap dengan rumus dan rujukannya.
     */
    private const DEW_POINT_METADATA = [
        'parameter' => 'DP',
        'name' => 'Dew Point (Titik Embun)',
        'derived' => true,
        'derived_from' => ['AT', 'RH'],
        'method' => 'Magnus-Tetens',
        'formula' => 'Td = (c * γ) / (b - γ), dengan γ = ln(RH/100) + (b * T) / (c + T)',
        'coefficients' => [
            'b' => self::DEW_POINT_MAGNUS_B,
            'c' => self::DEW_POINT_MAGNUS_C,
            'c_unit' => 'degC',
        ],
        'valid_range' => [
            'min_temperature_c' => -45.0,
            'max_temperature_c' => 60.0,
            'accuracy_c' => 0.4,
        ],
        'references' => [
            'WMO (2008), Guide to Meteorological Instruments and Methods of Observation (CIMO Guide), WMO-No. 8.',
            'Sonntag, D. (1990), Important new values of the physical constants of 1986, vapour pressure formulations based on the ITS-90. Z. Meteorol., 70(5), 340-344.',
            'Alduchov, O.A. & Eskridge, R.E. (1996), Improved Magnus form approximation of saturation vapor pressure. J. Appl. Meteorol., 35(4), 601-609.',
        ],
    ];

    private const DEW_POINT_SPREAD_METADATA = [
        'parameter' => 'DPS',
        'name' => 'Dew-Point Spread (Selisih Titik Embun)',
        'derived' => true,
        'derived_from' => ['AT', 'DP'],
        'method' => 'Selisih langsung',
        'formula' => 'DPS = AT - DP',
        'references' => [
            'DPS mendekati nol menandakan udara mendekati jenuh (kelembapan relatif tinggi).',
        ],
    ];

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

        $stationSensors = $this->stationSensorSummary($station, $samples);
        $recentReadings = $this->recentReadingList($samples);

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
            'station_sensors' => $stationSensors,
            'recent_readings' => $recentReadings,
            'derived_data' => [
                'dew_point_formula' => 'Magnus formula',
                'dew_point_spread_formula' => 'Air Temperature - Dew Point',
                'dew_point_metadata' => self::DEW_POINT_METADATA,
                'dew_point_spread_metadata' => self::DEW_POINT_SPREAD_METADATA,
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

    /**
     * Ringkasan sensor station: parameter apa yang dipasok sensor mana, dan
     * parameter mana yang merupakan turunan (DP, DPS).
     *
     * @return array<int, array<string, mixed>>
     */
    private function stationSensorSummary(MonitoringStation $station, array $samples): array
    {
        $summary = [];

        foreach (self::PARAMETERS as $parameter) {
            $items = $samples[$parameter] ?? [];
            $last = $items === [] ? null : $items[count($items) - 1];
            $isDerived = in_array($parameter, ['DP', 'DPS'], true);

            $summary[] = [
                'parameter' => $parameter,
                'is_derived' => $isDerived,
                'source_type' => $isDerived ? 'Turunan' : 'Sensor',
                'sensor_code' => $last['sensor_code'] ?? null,
                'sensor_label' => $last['sensor_label'] ?? null,
                'sensor_id' => $last['sensor_id'] ?? null,
                'derived_from' => $last['derived_from'] ?? ($isDerived ? ($parameter === 'DP' ? ['AT', 'RH'] : ['AT', 'DP']) : null),
                'latest_value' => isset($last['value']) ? round((float) $last['value'], 3) : null,
                'unit' => self::UNITS[$parameter] ?? null,
                'sample_count' => count($items),
            ];
        }

        return $summary;
    }

    /**
     * Daftar pembacaan terakhir (List Data) lintas parameter, diurut terbaru,
     * untuk ditampilkan pada tab List Data TDE Forecast.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentReadingList(array $samples, int $limit = 50): array
    {
        $rows = [];

        foreach (self::PARAMETERS as $parameter) {
            foreach ($samples[$parameter] ?? [] as $sample) {
                $rows[] = [
                    'timestamp' => $sample['timestamp'] ?? null,
                    'parameter' => $parameter,
                    'value' => isset($sample['value']) ? round((float) $sample['value'], 3) : null,
                    'unit' => $sample['unit'] ?? (self::UNITS[$parameter] ?? null),
                    'source' => $sample['source'] ?? null,
                    'is_derived' => in_array($parameter, ['DP', 'DPS'], true),
                    'sensor_code' => $sample['sensor_code'] ?? null,
                    'sensor_label' => $sample['sensor_label'] ?? null,
                ];
            }
        }

        usort($rows, fn ($a, $b) => strcmp((string) ($b['timestamp'] ?? ''), (string) ($a['timestamp'] ?? '')));

        return array_slice($rows, 0, $limit);
    }

    private function samples(Collection $readings): array
    {
        $samples = collect([...self::PARAMETERS, 'RAINFALL'])
            ->mapWithKeys(fn (string $parameter) => [$parameter => []])
            ->all();

        foreach ($readings as $reading) {
            $timestamp = $reading->received_at;
            $rowValues = [];
            $sensorMeta = [
                'sensor_id' => $reading->sensor?->id,
                'sensor_code' => $reading->sensor?->sensor_code,
                'sensor_label' => $reading->sensor?->mappingProfile?->canonicalParameter?->field_identity
                    ?? $reading->sensor?->sensor_code
                    ?? $reading->sensor?->parameter
                    ?? $reading->sensor?->type,
            ];
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
                    'sensor_id' => $sensorMeta['sensor_id'],
                    'sensor_code' => $sensorMeta['sensor_code'],
                    'sensor_label' => $sensorMeta['sensor_label'],
                ];
                $samples[$parameter][] = $sample;
                $rowValues[$parameter] = $sample;
            }

            if (isset($rowValues['AT'], $rowValues['RH'])) {
                $dewPoint = $this->dewPoint($rowValues['AT']['value'], $rowValues['RH']['value']);
                $derivedFrom = array_values(array_unique(array_filter([
                    $rowValues['AT']['sensor_code'] ?? null,
                    $rowValues['RH']['sensor_code'] ?? null,
                ])));
                $samples['DP'][] = [
                    'timestamp' => $timestamp->toISOString(),
                    'value' => $dewPoint,
                    'unit' => self::UNITS['DP'],
                    'source' => 'derived',
                    'sensor_id' => null,
                    'sensor_code' => $derivedFrom === [] ? null : implode(' + ', $derivedFrom),
                    'sensor_label' => 'Turunan dari AT & RH',
                    'derived_from' => $derivedFrom,
                ];
                $samples['DPS'][] = [
                    'timestamp' => $timestamp->toISOString(),
                    'value' => $rowValues['AT']['value'] - $dewPoint,
                    'unit' => self::UNITS['DPS'],
                    'source' => 'derived',
                    'sensor_id' => null,
                    'sensor_code' => $derivedFrom === [] ? null : implode(' + ', $derivedFrom),
                    'sensor_label' => 'Turunan dari AT & DP',
                    'derived_from' => $derivedFrom,
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
        $derivedFrom = array_values(array_unique(array_filter([
            $temperature['sensor_code'] ?? null,
            $humidity['sensor_code'] ?? null,
        ])));
        return [
            'timestamp' => max($temperature['timestamp'], $humidity['timestamp']),
            'value' => $this->dewPoint((float) $temperature['value'], (float) $humidity['value']),
            'unit' => self::UNITS['DP'],
            'source' => 'derived_endpoint',
            'sensor_id' => null,
            'sensor_code' => $derivedFrom === [] ? null : implode(' + ', $derivedFrom),
            'sensor_label' => 'Turunan dari AT & RH',
            'derived_from' => $derivedFrom,
        ];
    }

    private function derivedDewPointSpreadSample(array $temperature, array $dewPoint): array
    {
        $derivedFrom = array_values(array_unique(array_filter([
            $temperature['sensor_code'] ?? null,
            $dewPoint['sensor_code'] ?? null,
        ])));
        return [
            'timestamp' => max($temperature['timestamp'], $dewPoint['timestamp']),
            'value' => (float) $temperature['value'] - (float) $dewPoint['value'],
            'unit' => self::UNITS['DPS'],
            'source' => 'derived_endpoint',
            'sensor_id' => null,
            'sensor_code' => $derivedFrom === [] ? null : implode(' + ', $derivedFrom),
            'sensor_label' => 'Turunan dari AT & DP',
            'derived_from' => $derivedFrom,
        ];
    }

    private function parameterResult(string $parameter, array $samples, array $thresholds): array
    {
        $threshold = (float) ($thresholds[$parameter] ?? self::DEFAULT_THRESHOLDS[$parameter]);
        $thresholdDown = round(-$threshold, 4);
        $thresholdUp = round($threshold, 4);
        $downLabel = $parameter === 'DPS' ? 'Menyempit' : 'Menurun';
        $upLabel = $parameter === 'DPS' ? 'Melebar' : 'Meningkat';

        if (count($samples) < 2) {
            $only = $samples[0] ?? null;

            return [
                'value_start' => $only['value'] ?? null,
                'value_end' => $only['value'] ?? null,
                'change' => null,
                'threshold' => $threshold,
                'threshold_down' => $thresholdDown,
                'threshold_up' => $thresholdUp,
                'threshold_down_label' => $downLabel,
                'threshold_up_label' => $upLabel,
                'classification' => '?',
                'label' => 'Data tidak cukup',
                'sample_count' => count($samples),
                'unit' => self::UNITS[$parameter],
                'is_derived' => in_array($parameter, ['DP', 'DPS'], true),
                'sensor_code' => $only['sensor_code'] ?? null,
                'sensor_label' => $only['sensor_label'] ?? null,
                'sensor_id' => $only['sensor_id'] ?? null,
                'derived_from' => $only['derived_from'] ?? null,
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
            'threshold_down' => $thresholdDown,
            'threshold_up' => $thresholdUp,
            'threshold_down_label' => $downLabel,
            'threshold_up_label' => $upLabel,
            'classification' => $this->classify($change, $threshold),
            'label' => $this->classificationLabel($parameter, $this->classify($change, $threshold)),
            'sample_count' => count($samples),
            'unit' => self::UNITS[$parameter],
            'source_start' => $start['source'] ?? null,
            'source_end' => $end['source'] ?? null,
            'is_derived' => in_array($parameter, ['DP', 'DPS'], true),
            'sensor_code' => $end['sensor_code'] ?? $start['sensor_code'] ?? null,
            'sensor_label' => $end['sensor_label'] ?? $start['sensor_label'] ?? null,
            'sensor_id' => $end['sensor_id'] ?? $start['sensor_id'] ?? null,
            'derived_from' => $end['derived_from'] ?? $start['derived_from'] ?? null,
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
        $gamma = log($humidityRatio) + ((self::DEW_POINT_MAGNUS_B * $temperatureC) / (self::DEW_POINT_MAGNUS_C + $temperatureC));

        return (self::DEW_POINT_MAGNUS_C * $gamma) / (self::DEW_POINT_MAGNUS_B - $gamma);
    }

    /**
     * Metadata ilmiah dew point & dew-point spread untuk ditampilkan di UI
     * (TDE Forecast & Master TDE) agar peneliti mengetahui asal nilai DP/DPS.
     *
     * @return array<string, mixed>
     */
    public static function dewPointMetadata(): array
    {
        return [
            'dew_point' => self::DEW_POINT_METADATA,
            'dew_point_spread' => self::DEW_POINT_SPREAD_METADATA,
        ];
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
