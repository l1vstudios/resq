<?php

namespace App\Services\Ingestion;

use App\Models\DataLogger;
use App\Models\Sensor;

final readonly class AuthenticatedDeviceContext
{
    public function __construct(
        public DataLogger $dataLogger,
        public ?int $projectId,
        public int $monitoringStationId,
        public string $authenticationMethod,
        public array $allowedIngressPaths,
    ) {
    }

    public function defaultIngressPath(): string
    {
        if (count($this->allowedIngressPaths) === 1) {
            return $this->allowedIngressPaths[0];
        }

        return in_array('http_callback', $this->allowedIngressPaths, true)
            ? 'http_callback'
            : $this->allowedIngressPaths[0];
    }

    public function sourceSnapshot(?Sensor $sensor = null): array
    {
        return [
            'project_id' => $this->projectId,
            'monitoring_station_id' => $this->monitoringStationId,
            'data_logger_id' => $this->dataLogger->id,
            'logger_code' => $this->dataLogger->logger_code,
            'sensor_id' => $sensor?->id,
            'sensor_code' => $sensor?->sensor_code,
            'authentication_method' => $this->authenticationMethod,
        ];
    }
}
