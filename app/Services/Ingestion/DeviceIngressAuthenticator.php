<?php

namespace App\Services\Ingestion;

use App\Models\DataLogger;
use App\Models\DeviceCredential;
use App\Models\Sensor;
use Illuminate\Http\Request;

final class DeviceIngressAuthenticator
{
    public function authenticate(Request $request): AuthenticatedDeviceContext
    {
        $token = (string) $request->bearerToken();

        if ($token === '') {
            throw new DeviceIngressAuthenticationException('Device credential is required.');
        }

        $credential = DeviceCredential::query()
            ->with(['dataLogger.monitoringStation.workspace', 'dataLogger.connectivityConfigs'])
            ->whereNotNull('device_token')
            ->whereNull('revoked_at')
            ->where('credential_status', 'Active')
            ->get()
            ->first(fn (DeviceCredential $candidate) => hash_equals((string) $candidate->device_token, $token));

        if ($credential?->dataLogger) {
            return $this->contextForLogger(
                $credential->dataLogger,
                'device_credential',
                $this->credentialIngressPaths($credential),
            );
        }

        $legacyTokens = collect(config('canonical.ingestion.legacy_callback_tokens', []))
            ->filter(fn ($candidate): bool => is_string($candidate) && $candidate !== '')
            ->filter(fn (string $candidate): bool => hash_equals($candidate, $token))
            ->keys()
            ->all();
        $legacyConfigToken = (string) config('canonical.ingestion.legacy_config_token', '');
        $legacyLoggerCode = (string) config('canonical.ingestion.legacy_logger_code', '');
        if ($legacyConfigToken !== '' && hash_equals($legacyConfigToken, $token)) {
            $legacyTokens[] = 'rednode_callback';
        }
        $legacyTokens = array_values(array_unique($legacyTokens));

        if ($legacyTokens === [] || $legacyLoggerCode === '') {
            throw new DeviceIngressAuthenticationException('Device credential is invalid or not source-bound.');
        }

        $logger = DataLogger::query()
            ->with('monitoringStation.workspace')
            ->where('logger_code', $legacyLoggerCode)
            ->first();

        if (! $logger) {
            throw new DeviceIngressAuthenticationException('Configured legacy device source was not found.');
        }

        return $this->contextForLogger($logger, 'legacy_bound_token', $legacyTokens);
    }

    public function resolveRealtimePath(AuthenticatedDeviceContext $context, mixed $transport): string
    {
        $path = match ((string) $transport) {
            'http' => 'http_callback',
            'modbus_tcp' => 'modbus_tcp',
            'mqtt' => 'mqtt',
            'modbus_rtu', 'rednode' => 'rednode_callback',
            default => null,
        };

        if ($path === null || ! in_array($path, $context->allowedIngressPaths, true)) {
            throw new DeviceIngressAuthenticationException('Transport is not authorized for the authenticated source.', 403);
        }

        return $path;
    }

    public function resolveSensor(
        AuthenticatedDeviceContext $context,
        mixed $sensorId,
        mixed $sensorCode,
        mixed $claimedLoggerId = null,
    ): Sensor {
        if ($claimedLoggerId !== null && (int) $claimedLoggerId !== $context->dataLogger->id) {
            throw new DeviceIngressAuthenticationException('Payload logger does not match the authenticated source.');
        }

        $sensor = $sensorId
            ? Sensor::query()->with('monitoringStation.workspace')->find((int) $sensorId)
            : Sensor::query()->with('monitoringStation.workspace')->where('sensor_code', (string) $sensorCode)->first();

        if (! $sensor) {
            throw new DeviceIngressAuthenticationException('Sensor source was not found.', 422);
        }

        $loggerStationId = $context->dataLogger->monitoring_station_id;
        if (! $loggerStationId || (int) $sensor->monitoring_station_id !== (int) $loggerStationId) {
            throw new DeviceIngressAuthenticationException('Sensor is outside the authenticated logger scope.');
        }

        return $sensor;
    }

    private function contextForLogger(DataLogger $logger, string $authenticationMethod, array $allowedIngressPaths): AuthenticatedDeviceContext
    {
        if (strcasecmp((string) $logger->logger_status, 'Active') !== 0) {
            throw new DeviceIngressAuthenticationException('Authenticated data logger is not active.');
        }

        $station = $logger->monitoringStation;
        if (! $station) {
            throw new DeviceIngressAuthenticationException('Authenticated data logger is not bound to a monitoring station.');
        }

        return new AuthenticatedDeviceContext(
            dataLogger: $logger,
            projectId: $station->workspace?->project_id,
            monitoringStationId: $station->id,
            authenticationMethod: $authenticationMethod,
            allowedIngressPaths: $allowedIngressPaths,
        );
    }

    private function credentialIngressPaths(DeviceCredential $credential): array
    {
        $paths = collect($credential->dataLogger?->connectivityConfigs ?? [])
            ->flatMap(function ($connectivity): array {
                $protocol = strtolower(trim(implode(' ', array_filter([
                    $connectivity->communication_type,
                    $connectivity->protocol,
                ]))));
                $paths = [];

                if (str_contains($protocol, 'mqtt')) {
                    $paths[] = 'mqtt';
                }
                if (str_contains($protocol, 'modbus tcp')) {
                    $paths[] = 'modbus_tcp';
                }
                if (str_contains($protocol, 'modbus rtu') || str_contains($protocol, 'rs485') || $connectivity->serial_port) {
                    $paths[] = 'rednode_callback';
                }
                if (str_contains($protocol, 'http') || str_contains($protocol, 'api')) {
                    $paths[] = 'http_callback';
                }

                return $paths;
            });

        if ($credential->mqtt_username) {
            $paths->push('mqtt');
        }

        $paths = $paths->unique()->values()->all();

        return $paths === [] ? ['http_callback'] : $paths;
    }
}
