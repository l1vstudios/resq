<?php

namespace App\Services;

use App\Models\CorridorMonitoring;
use App\Models\MonitoringStation;
use App\Models\Project;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientReportingService
{
    public const TARGET_CORRIDOR = 'corridor';

    public const TARGET_STATION = 'station';

    public function targetOptions(Project $project): array
    {
        $project->loadMissing(['corridors', 'monitoringStations.sensors']);

        return [
            'corridors' => $project->corridors
                ->map(fn (CorridorMonitoring $corridor) => [
                    'id' => $corridor->id,
                    'code' => $corridor->corridor_code,
                    'name' => $corridor->name,
                ])
                ->values()
                ->all(),
            'stations' => $project->monitoringStations
                ->map(fn (MonitoringStation $station) => [
                    'id' => $station->id,
                    'code' => $station->station_code,
                    'name' => $station->name,
                    'corridor_id' => $station->corridor_id,
                ])
                ->values()
                ->all(),
        ];
    }

    public function parameterOptions(Collection $stations): Collection
    {
        return $stations
            ->flatMap(fn (MonitoringStation $station) => $station->sensors)
            ->map(fn (Sensor $sensor) => [
                'sensor_id' => $sensor->id,
                'sensor_code' => $sensor->sensor_code,
                'parameter' => $sensor->parameter ?: $sensor->type,
                'unit' => $sensor->unit,
            ])
            ->unique(fn (array $row) => $row['parameter'].'|'.$row['unit'])
            ->values();
    }

    public function prepare(array $filters): array
    {
        $target = $this->target($filters['target_type'], (int) $filters['target_id']);
        $stations = $this->stationsForTarget($target, $filters['target_type']);
        $stations->each(fn (MonitoringStation $station) => $station->loadMissing(['project', 'corridor', 'sensors']));

        $from = Carbon::parse($filters['from'])->startOfMinute();
        $to = Carbon::parse($filters['to'])->endOfMinute();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $sensorIds = $stations
            ->flatMap(fn (MonitoringStation $station) => $station->sensors)
            ->when($filters['parameter'] ?? null, fn (Collection $sensors, string $parameter) => $sensors
                ->filter(fn (Sensor $sensor) => strcasecmp((string) ($sensor->parameter ?: $sensor->type), $parameter) === 0))
            ->pluck('id')
            ->unique()
            ->values();

        $rows = TelemetryReading::with(['sensor.monitoringStation'])
            ->whereIn('sensor_id', $sensorIds)
            ->whereBetween('received_at', [$from, $to])
            ->orderBy('received_at')
            ->orderBy('id')
            ->limit(5000)
            ->get()
            ->map(fn (TelemetryReading $reading) => $this->readingRow($reading))
            ->values();

        return [
            'target_type' => $filters['target_type'],
            'target' => $this->targetRow($target, $filters['target_type']),
            'project' => $stations->first()?->project,
            'from' => $from,
            'to' => $to,
            'parameter' => $filters['parameter'] ?? null,
            'rows' => $rows,
            'generated_at' => now(),
            'bounded_limit' => 5000,
        ];
    }

    public function csvResponse(array $report, string $format): StreamedResponse
    {
        $delimiter = $format === 'excel' ? "\t" : ',';
        $extension = $format === 'excel' ? 'xls' : 'csv';
        $filename = 'sentinel-report-'.$report['target_type'].'-'.$report['target']['code'].'-'.now()->format('YmdHis').'.'.$extension;

        return response()->streamDownload(function () use ($report, $delimiter) {
            $handle = fopen('php://output', 'w');
            $this->writeDelimitedRow($handle, ['Station', 'Parameter', 'Value', 'Unit', 'Status', 'Alert Level', 'Received At'], $delimiter);

            foreach ($report['rows'] as $row) {
                $this->writeDelimitedRow($handle, [
                    $row['station_code'],
                    $row['parameter'],
                    $row['value'],
                    $row['unit'],
                    $row['status'],
                    $row['alert_level'],
                    $row['received_at'],
                ], $delimiter);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => $format === 'excel'
                ? 'application/vnd.ms-excel; charset=UTF-8'
                : 'text/csv; charset=UTF-8',
        ]);
    }

    public function printableResponse(array $report): Response
    {
        return response()->view('modules.client-operations.reporting-printable', [
            'report' => $report,
        ]);
    }

    public function target(string $targetType, int $targetId): CorridorMonitoring|MonitoringStation
    {
        return match ($targetType) {
            self::TARGET_CORRIDOR => CorridorMonitoring::with(['project', 'referenceRoute'])->findOrFail($targetId),
            self::TARGET_STATION => MonitoringStation::with(['project', 'workspace.project', 'corridor', 'sensors'])->findOrFail($targetId),
            default => abort(404),
        };
    }

    public function targetProjectId(CorridorMonitoring|MonitoringStation $target, string $targetType): ?int
    {
        return $targetType === self::TARGET_CORRIDOR
            ? $target->project_id
            : ($target->project_id ?: $target->workspace?->project_id);
    }

    public function stationsForTarget(CorridorMonitoring|MonitoringStation $target, string $targetType): Collection
    {
        if ($targetType === self::TARGET_STATION) {
            return collect([$target]);
        }

        return MonitoringStation::with(['project', 'corridor', 'sensors'])
            ->where('project_id', $target->project_id)
            ->where('corridor_id', $target->id)
            ->orderBy('station_code')
            ->get();
    }

    private function targetRow(CorridorMonitoring|MonitoringStation $target, string $targetType): array
    {
        return [
            'id' => $target->id,
            'code' => $targetType === self::TARGET_CORRIDOR ? $target->corridor_code : $target->station_code,
            'name' => $target->name,
        ];
    }

    private function readingRow(TelemetryReading $reading): array
    {
        $sensor = $reading->sensor;

        return [
            'station_code' => $sensor?->monitoringStation?->station_code,
            'sensor_code' => $sensor?->sensor_code,
            'parameter' => $sensor?->parameter ?: $sensor?->type,
            'value' => $reading->numeric_value ?? $reading->value,
            'unit' => $sensor?->unit,
            'status' => $reading->status,
            'alert_level' => $reading->alert_level,
            'received_at' => optional($reading->received_at)->toISOString(),
        ];
    }

    private function writeDelimitedRow($handle, array $row, string $delimiter): void
    {
        if ($delimiter === ',') {
            fputcsv($handle, $row);

            return;
        }

        fwrite($handle, collect($row)->map(fn ($value) => str_replace(["\t", "\r", "\n"], ' ', (string) $value))->implode("\t")."\n");
    }
}
