<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PublicTelemetryController extends Controller
{
    public function latest(Request $request): JsonResponse
    {
        if (! $this->authorizePublicRead($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid public API token.',
            ], 403);
        }

        if (! Schema::hasTable('sensors') || ! Schema::hasTable('telemetry_readings')) {
            return response()->json([
                'ok' => false,
                'message' => 'Telemetry tables are not ready.',
            ], 503);
        }

        $limit = min(max((int) $request->query('limit', 100), 1), 500);
        $freshAfter = now()->subSeconds((int) $request->query('fresh_seconds', env('PROJECT_MONITORING_FRESH_SECONDS', 90)));
        $sensorQuery = Sensor::with(['workspace.project', 'monitoringStation', 'warningStation', 'dataLogger'])
            ->orderBy('sensor_code')
            ->limit($limit);

        if ($request->filled('sensor_code')) {
            $sensorQuery->where('sensor_code', $request->query('sensor_code'));
        }

        if ($request->filled('logger_code')) {
            $sensorQuery->whereHas('dataLogger', fn ($query) => $query->where('logger_code', $request->query('logger_code')));
        }

        if ($request->filled('project_code')) {
            $projectCode = $request->query('project_code');
            $sensorQuery->whereHas('workspace.project', function ($query) use ($projectCode) {
                $query->where('project_code', $projectCode);
                if (is_numeric($projectCode)) {
                    $query->orWhere('id', (int) $projectCode);
                }
            });
        }

        $sensors = $sensorQuery->get();

        return response()->json([
            'ok' => true,
            'generated_at' => now()->toISOString(),
            'fresh_after' => $freshAfter->toISOString(),
            'count' => $sensors->count(),
            'sensors' => $this->sensorRows($sensors, $freshAfter),
        ]);
    }

    public function sensor(string $sensorCode, Request $request): JsonResponse
    {
        $request->merge(['sensor_code' => $sensorCode]);

        return $this->latest($request);
    }

    public function project(string $projectCode, Request $request): JsonResponse
    {
        if (! $this->authorizePublicRead($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid public API token.',
            ], 403);
        }

        $project = Project::query()
            ->where('project_code', $projectCode)
            ->when(is_numeric($projectCode), fn ($query) => $query->orWhere('id', (int) $projectCode))
            ->first();

        if (! $project) {
            return response()->json([
                'ok' => false,
                'message' => 'Project not found.',
            ], 404);
        }

        $request->merge(['project_code' => $project->project_code]);
        $payload = $this->latest($request)->getData(true);
        $payload['project'] = [
            'id' => $project->id,
            'project_code' => $project->project_code,
            'name' => $project->name,
            'status' => $project->status,
        ];

        return response()->json($payload);
    }

    private function sensorRows($sensors, $freshAfter)
    {
        $readings = TelemetryReading::with('dataLogger')
            ->whereIn('sensor_id', $sensors->pluck('id'))
            ->latest('received_at')
            ->latest()
            ->get()
            ->unique('sensor_id')
            ->keyBy('sensor_id');

        return $sensors->map(function (Sensor $sensor) use ($readings, $freshAfter) {
            $reading = $readings->get($sensor->id);
            $receivedAt = $reading?->received_at ?? $sensor->last_seen_at;
            $fresh = $receivedAt && $receivedAt->gt($freshAfter);
            $parameterValues = collect($reading?->parameter_values ?? [])
                ->filter(fn ($item) => is_array($item))
                ->map(fn ($item) => array_merge($item, [
                    'value_text' => $this->valueWithUnit($item['value_text'] ?? ($item['value'] ?? null), $item['unit'] ?? null),
                ]))
                ->values();
            $value = $sensor->type === 'weather_station' && $parameterValues->isNotEmpty()
                ? $parameterValues->pluck('value_text')->filter()->implode(', ')
                : $this->valueWithUnit($reading?->value ?? $sensor->value, $sensor->unit);

            return [
                'sensor_id' => $sensor->id,
                'sensor_code' => $sensor->sensor_code,
                'sensor_type' => $sensor->type,
                'parameter' => $sensor->parameter,
                'value' => $value,
                'raw_value' => $reading?->value,
                'unit' => $sensor->unit,
                'parameter_values' => $parameterValues,
                'status' => $reading?->status ?? $sensor->status,
                'alert_level' => $reading?->alert_level ?? $sensor->alert_level,
                'fresh' => (bool) $fresh,
                'received_at' => optional($receivedAt)->toISOString(),
                'logger_code' => $reading?->dataLogger?->logger_code ?? $sensor->dataLogger?->logger_code,
                'monitoring_station' => $sensor->monitoringStation?->station_code,
                'warning_station' => $sensor->warningStation?->station_code,
                'project_code' => $sensor->workspace?->project?->project_code,
                'workspace_code' => $sensor->workspace?->workspace_code,
                'location' => [
                    'lat' => $sensor->monitoringStation?->latitude ?? $sensor->workspace?->latitude,
                    'lng' => $sensor->monitoringStation?->longitude ?? $sensor->workspace?->longitude,
                ],
            ];
        })->values();
    }

    private function authorizePublicRead(Request $request): bool
    {
        $token = env('PUBLIC_API_TOKEN');

        return ! $token || hash_equals((string) $token, (string) $request->bearerToken());
    }

    private function valueWithUnit(mixed $value, ?string $unit): string
    {
        $text = trim((string) ($value ?? '-'));
        $unit = trim((string) $unit);

        if ($text === '' || $text === '-' || $unit === '' || $unit === '0') {
            return $text === '' ? '-' : $text;
        }

        if (! preg_match('/-?\d+([,.]\d+)?/', $text)) {
            return $text;
        }

        return Str::endsWith(Str::lower($text), Str::lower($unit))
            ? $text
            : trim($text . ' ' . $unit);
    }
}
