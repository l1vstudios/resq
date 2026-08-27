<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MqttConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'configuration_code', 'name', 'broker_url', 'username',
        'password_ciphertext', 'consumer_enabled', 'consumer_topic', 'consumer_qos',
        'example_payload', 'sensor_code_path', 'producer_enabled', 'producer_topic',
        'producer_qos', 'producer_retain', 'publish_canonical', 'publish_warning',
        'canonical_parameter_ids', 'warning_levels', 'canonical_template',
        'warning_template', 'is_active', 'connection_status', 'last_connected_at',
        'last_received_at', 'last_published_at', 'last_error', 'runtime_metrics',
    ];

    protected $hidden = ['password_ciphertext'];

    protected $casts = [
        'consumer_enabled' => 'boolean',
        'producer_enabled' => 'boolean',
        'producer_retain' => 'boolean',
        'publish_canonical' => 'boolean',
        'publish_warning' => 'boolean',
        'is_active' => 'boolean',
        'example_payload' => 'array',
        'canonical_parameter_ids' => 'array',
        'warning_levels' => 'array',
        'runtime_metrics' => 'array',
        'last_connected_at' => 'datetime',
        'last_received_at' => 'datetime',
        'last_published_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    public function nodeRedDataLoggers()
    {
        return $this->hasMany(DataLogger::class, 'node_red_mqtt_configuration_id');
    }

    public function outboxMessages()
    {
        return $this->hasMany(MqttOutboxMessage::class);
    }

    /**
     * Build a dynamic sensor payload from the logger's sensors and their mapping profiles.
     *
     * Fully plug-and-play: no manual JSON input needed.
     * The payload structure is determined by whatever sensors and presets
     * are attached to the data logger linked to this MQTT configuration.
     */
    public function currentSensorPayload(): array|null
    {
        $sensors = $this->resolveSensors();

        if ($sensors->isEmpty()) {
            return $this->example_payload;
        }

        $sensorReadings = $sensors->map(function ($sensor) {
            $entry = [
                'sensor_code' => $sensor->sensor_code,
                'unit' => $sensor->unit,
                'threshold' => $sensor->threshold,
                'status' => $sensor->status ?? 'Normal',
                'last_seen_at' => $sensor->last_seen_at?->toISOString(),
            ];

            // Build parameters from mapping profiles (preset-driven)
            $profiles = $sensor->mappingProfiles()
                ->where('status', 'active')
                ->with('canonicalParameter')
                ->orderBy('id')
                ->get();

            // Get latest telemetry reading for live values
            $latestReading = $sensor->telemetryReadings()
                ->latest('received_at')
                ->first();

            $liveParameterValues = collect($latestReading?->parameter_values ?? []);

            if ($profiles->isNotEmpty()) {
                // Multi-parameter sensor with mapping profiles (from preset)
                $entry['parameters'] = $profiles
                    ->filter(fn ($profile) => $profile->canonicalParameter !== null)
                    ->map(function ($profile) use ($liveParameterValues) {
                        $canonical = $profile->canonicalParameter;
                        $fieldIdentity = $canonical->field_identity;

                        // Try to get live value from telemetry
                        $liveItem = $liveParameterValues->first(fn ($item) =>
                            ($item['parameter'] ?? null) === $fieldIdentity
                            || ($item['canonical_field'] ?? null) === $fieldIdentity
                        );

                        return [
                            'parameter' => $fieldIdentity,
                            'value' => $liveItem['value'] ?? 0,
                            'unit' => $canonical->canonical_unit ?? $profile->source_unit,
                            'value_text' => $liveItem['value_text'] ?? (number_format(0, 2) . ' ' . ($canonical->canonical_unit ?? '')),
                        ];
                    })
                    ->values()
                    ->all();

                $entry['value'] = $latestReading?->numeric_value
                    ?? ($entry['parameters'][0]['value'] ?? (is_numeric($sensor->value) ? (float) $sensor->value : 0));
            } elseif ($liveParameterValues->isNotEmpty()) {
                // Has telemetry parameter_values but no profiles (legacy weather_parameters)
                $entry['parameters'] = $liveParameterValues->map(fn ($item) => [
                    'parameter' => $item['parameter'] ?? $item['label'] ?? null,
                    'value' => $item['value'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'value_text' => $item['value_text'] ?? null,
                ])->values()->all();
                $entry['value'] = $latestReading?->numeric_value
                    ?? ($liveParameterValues->first()['value'] ?? $sensor->value);
            } else {
                // Single value sensor (no profiles, no telemetry parameters)
                $entry['value'] = is_numeric($sensor->value) ? (float) $sensor->value : ($sensor->value ?? 0);
            }

            return $entry;
        })->all();

        return [
            '_current_sensors' => $sensorReadings,
        ];
    }

    /**
     * Resolve sensors linked to this MQTT configuration through any path:
     * direct link, data logger, or project fallback.
     */
    private function resolveSensors(): \Illuminate\Database\Eloquent\Collection
    {
        // 1. Sensors directly linked to this MQTT config
        $sensors = $this->sensors()->limit(20)->get();

        // 2. Sensors via data loggers that use this MQTT config
        if ($sensors->isEmpty()) {
            $loggerIds = DataLogger::where('node_red_mqtt_configuration_id', $this->id)->pluck('id');

            if ($loggerIds->isNotEmpty()) {
                $sensors = Sensor::whereIn('data_logger_id', $loggerIds)->limit(20)->get();
            }
        }

        // 3. Fallback: sensors of the same project
        if ($sensors->isEmpty() && $this->project_id) {
            $sensors = Sensor::whereHas('workspace', fn ($q) => $q->where('project_id', $this->project_id))
                ->limit(20)
                ->get();
        }

        return $sensors;
    }
}
