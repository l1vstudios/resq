<?php

namespace App\Services;

use App\Models\CanonicalObservation;
use App\Models\MqttConfiguration;
use App\Models\MqttOutboxMessage;
use App\Models\Sensor;
use App\Models\TelemetryReading;
use InvalidArgumentException;

class MqttOutboxService
{
    public const CANONICAL_DEFAULT_TEMPLATE = '{"event":"canonical","project":"{{project.code}}","station":"{{station.code}}","sensor":"{{sensor.code}}","observed_at":"{{canonical.observed_at}}","values":"{{canonical.values}}"}';

    public const WARNING_DEFAULT_TEMPLATE = '{"event":"warning","project":"{{project.code}}","station":"{{station.code}}","sensor":"{{sensor.code}}","level":"{{warning.level}}","previous_level":"{{warning.previous_level}}","occurred_at":"{{warning.occurred_at}}"}';

    public function enqueueCanonical(CanonicalObservation $observation, Sensor $sensor): void
    {
        $project = $sensor->workspace?->project;
        if (! $project) {
            return;
        }

        $observation->loadMissing('parameterValues.canonicalParameter');
        $values = $observation->parameterValues
            ->mapWithKeys(fn ($value) => [
                $value->canonicalParameter?->field_identity => $value->numeric_value ?? $value->string_value,
            ])->filter(fn ($value, $key) => $key !== null)->all();
        $parameterIds = $observation->parameterValues->pluck('canonical_parameter_id')->map(fn ($id) => (int) $id);
        $context = $this->baseContext($sensor) + [
            'canonical' => array_merge($values, [
                'observation_id' => $observation->id,
                'observed_at' => optional($observation->observed_at)->toISOString(),
                'domain' => $observation->domain,
                'values' => $values,
            ]),
        ];

        MqttConfiguration::query()
            ->where('project_id', $project->id)
            ->where('is_active', true)
            ->where('producer_enabled', true)
            ->where('publish_canonical', true)
            ->get()
            ->filter(function (MqttConfiguration $config) use ($parameterIds) {
                $filter = collect($config->canonical_parameter_ids ?? [])->map(fn ($id) => (int) $id);

                return $filter->isEmpty() || $filter->intersect($parameterIds)->isNotEmpty();
            })
            ->each(fn (MqttConfiguration $config) => $this->createMessage(
                $config,
                'canonical',
                CanonicalObservation::class,
                $observation->id,
                $context,
                $config->canonical_template ?: self::CANONICAL_DEFAULT_TEMPLATE,
                hash('sha256', json_encode($values).$observation->updated_at?->format('U.u'))
            ));
    }

    public function enqueueWarning(Sensor $sensor, string $previousLevel, string $level, int $sourceId): void
    {
        if ($previousLevel === $level) {
            return;
        }

        $project = $sensor->workspace?->project;
        if (! $project) {
            return;
        }

        $context = $this->baseContext($sensor) + [
            'warning' => [
                'level' => $level,
                'previous_level' => $previousLevel,
                'occurred_at' => now()->toISOString(),
            ],
        ];

        MqttConfiguration::query()
            ->where('project_id', $project->id)
            ->where('is_active', true)
            ->where('producer_enabled', true)
            ->where('publish_warning', true)
            ->get()
            ->filter(fn (MqttConfiguration $config) => empty($config->warning_levels)
                || in_array($level, $config->warning_levels, true))
            ->each(fn (MqttConfiguration $config) => $this->createMessage(
                $config,
                'warning',
                TelemetryReading::class,
                $sourceId,
                $context,
                $config->warning_template ?: self::WARNING_DEFAULT_TEMPLATE,
                hash('sha256', $previousLevel.'>'.$level.':'.$sourceId)
            ));
    }

    public function validateTemplate(string $template): void
    {
        $decoded = json_decode($template, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Template wajib berupa JSON valid: '.json_last_error_msg());
        }

        preg_match_all('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', $template, $matches);
        foreach ($matches[1] ?? [] as $placeholder) {
            $allowed = preg_match('/^(project|station)\.(id|code)$/', $placeholder)
                || preg_match('/^sensor\.(id|code|unit)$/', $placeholder)
                || preg_match('/^canonical\.(observation_id|observed_at|domain|values|[a-zA-Z0-9_]+)$/', $placeholder)
                || preg_match('/^warning\.(level|previous_level|occurred_at)$/', $placeholder);
            if (! $allowed) {
                throw new InvalidArgumentException("Placeholder {{$placeholder}} tidak diizinkan.");
            }
        }
    }

    public function render(string $template, array $context): array
    {
        $this->validateTemplate($template);
        $data = json_decode($template, true, flags: JSON_THROW_ON_ERROR);

        return $this->renderValue($data, $context);
    }

    private function createMessage(
        MqttConfiguration $config,
        string $eventType,
        string $sourceType,
        int $sourceId,
        array $context,
        string $template,
        string $version
    ): void {
        $fingerprint = hash('sha256', implode('|', [$config->id, $eventType, $sourceType, $sourceId, $version]));

        MqttOutboxMessage::firstOrCreate(['fingerprint' => $fingerprint], [
            'mqtt_configuration_id' => $config->id,
            'event_type' => $eventType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'topic' => $config->producer_topic,
            'qos' => $config->producer_qos,
            'retain' => $config->producer_retain,
            'payload' => $this->render($template, $context),
            'status' => 'pending',
            'available_at' => now(),
        ]);
    }

    private function baseContext(Sensor $sensor): array
    {
        $sensor->loadMissing(['workspace.project', 'monitoringStation']);

        return [
            'project' => ['id' => $sensor->workspace?->project?->id, 'code' => $sensor->workspace?->project?->project_code],
            'station' => ['id' => $sensor->monitoringStation?->id, 'code' => $sensor->monitoringStation?->station_code],
            'sensor' => ['id' => $sensor->id, 'code' => $sensor->sensor_code, 'unit' => $sensor->unit],
        ];
    }

    private function renderValue(mixed $value, array $context): mixed
    {
        if (is_array($value)) {
            return collect($value)->map(fn ($item) => $this->renderValue($item, $context))->all();
        }
        if (! is_string($value)) {
            return $value;
        }
        if (preg_match('/^\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}$/', $value, $match)) {
            return data_get($context, $match[1]);
        }

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', fn ($match) => json_encode(data_get($context, $match[1]), JSON_UNESCAPED_SLASHES), $value);
    }
}
