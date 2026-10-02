<?php

namespace App\Http\Controllers;

use App\Models\DataLogger;
use App\Models\MqttConfiguration;
use App\Models\Project;
use App\Services\AuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use ZipArchive;

class RednodeBuilderController extends Controller
{
    public function __construct(private readonly AuthorizationService $authorization)
    {
    }

    public function index(Request $request): View
    {
        abort_unless($this->authorization->canMutateAssetRegistry($request->user()), 403);

        $projectIds = $this->authorization
            ->scopeProjectsForUser($request->user(), Project::query())
            ->pluck('id');

        $dataLoggers = DataLogger::with(['monitoringStation.workspace', 'nodeRedMqttConfiguration'])
            ->where(function ($query) use ($projectIds) {
                $query->whereHas('monitoringStation', function ($stationQuery) use ($projectIds) {
                    $stationQuery->whereIn('project_id', $projectIds)
                        ->orWhereHas('workspace', fn ($workspaceQuery) => $workspaceQuery->whereIn('project_id', $projectIds));
                })
                    ->orWhereNull('monitoring_station_id');
            })
            ->latest('id')
            ->get();

        $mqttConfigurations = MqttConfiguration::with('project')
            ->whereIn('project_id', $projectIds)
            ->where('consumer_enabled', true)
            ->orderBy('name')
            ->get();

        return view('modules.rednode-builder.index', [
            'dataLoggers' => $dataLoggers,
            'mqttConfigurations' => $mqttConfigurations,
            'defaultAppUrl' => rtrim((string) (env('REDNODE_PUBLIC_APP_URL') ?: $request->getSchemeAndHttpHost() ?: env('APP_URL')), '/'),
            'defaultGatewayPath' => env('REDNODE_GATEWAY_PATH', '/root/rednode-gateway'),
            'defaultServiceName' => env('REDNODE_GATEWAY_SERVICE', 'rednode-gateway'),
            'defaultSerialPort' => env('REDNODE_SERIAL_PORT', '/dev/ttyS9'),
        ]);
    }

    public function build(Request $request): RedirectResponse
    {
        abort_unless($this->authorization->canMutateAssetRegistry($request->user()), 403);

        $data = $request->validate([
            'data_logger_id' => ['required', 'exists:data_loggers,id'],
            'gateway_zip' => ['required', 'file', 'mimes:zip', 'max:51200'],
            'mqtt_configuration_id' => ['nullable', 'exists:mqtt_configurations,id'],
            'app_url' => ['required', 'url', 'max:2048'],
            'gateway_path' => ['required', 'string', 'max:255'],
            'service_name' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_.@-]+$/'],
            'entry_file' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\\.\\/-]+$/'],
            'serial_port' => ['nullable', 'string', 'max:120'],
            'baud_rate' => ['required', 'integer', 'min:300', 'max:921600'],
            'poll_interval_ms' => ['required', 'integer', 'min:250', 'max:600000'],
            'install_dependencies' => ['nullable', 'boolean'],
            'start_service' => ['nullable', 'boolean'],
        ]);

        $logger = DataLogger::with(['monitoringStation.workspace', 'nodeRedMqttConfiguration'])->findOrFail($data['data_logger_id']);
        $this->authorizeLoggerAccess($request, $logger);

        if (! empty($data['mqtt_configuration_id'])) {
            $mqtt = MqttConfiguration::findOrFail($data['mqtt_configuration_id']);
            abort_unless($this->authorization->canAccessProject($request->user(), $mqtt->project_id), 403);
            $logger->node_red_mqtt_configuration_id = $mqtt->id;
            $logger->node_red_publish_topic = $logger->node_red_publish_topic ?: $mqtt->consumer_topic;
        }

        $this->assertSshReady($logger);
        $this->assertZipLooksDeployable($request->file('gateway_zip')->getRealPath());

        $remoteZip = '/tmp/resq-rednode-' . $logger->id . '-' . now()->format('YmdHis') . '.zip';
        $log = [];

        try {
            $this->uploadZip($logger, $request->file('gateway_zip')->getRealPath(), $remoteZip, $log);
            $command = $this->buildRemoteCommand(
                logger: $logger,
                remoteZip: $remoteZip,
                gatewayPath: $data['gateway_path'],
                serviceName: $data['service_name'],
                entryFile: $data['entry_file'] ?: 'gateway.js',
                appUrl: rtrim($data['app_url'], '/'),
                serialPort: $data['serial_port'] ?: env('REDNODE_SERIAL_PORT', '/dev/ttyS9'),
                baudRate: (int) $data['baud_rate'],
                pollIntervalMs: (int) $data['poll_interval_ms'],
                installDependencies: $request->boolean('install_dependencies'),
                startService: $request->boolean('start_service', true),
            );
            $output = $this->runSsh($logger, $command, 180);
            $log = array_merge($log, $this->logLines($output));

            $logger->forceFill([
                'remote_gateway_path' => $data['gateway_path'],
                'node_red_service_name' => $data['service_name'],
                'node_red_user_dir' => $data['gateway_path'],
                'node_red_environment_file' => rtrim($data['gateway_path'], '/') . '/.env',
                'node_red_restart_command' => 'systemctl restart ' . $data['service_name'],
                'node_red_last_applied_at' => now(),
                'node_red_last_status' => 'Built',
                'node_red_last_message' => Str::limit(implode("\n", $log), 2000, ''),
                'poll_interval_ms' => (int) $data['poll_interval_ms'],
            ])->save();
        } catch (ValidationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            $logger->forceFill([
                'node_red_last_applied_at' => now(),
                'node_red_last_status' => 'Failed',
                'node_red_last_message' => $error->getMessage(),
            ])->save();

            throw ValidationException::withMessages([
                'rednode_builder' => $error->getMessage(),
            ]);
        }

        return back()
            ->with('message', 'RedNode gateway berhasil dibuild dan dikirim ke ' . $logger->logger_code . '.')
            ->with('rednode_builder_log', $log);
    }

    private function authorizeLoggerAccess(Request $request, DataLogger $logger): void
    {
        $projectId = $logger->monitoringStation?->workspace?->project_id;
        $projectId = $logger->monitoringStation?->project_id ?: $projectId;
        if ($projectId) {
            abort_unless($this->authorization->canAccessProject($request->user(), $projectId), 403);
        }
    }

    private function assertSshReady(DataLogger $logger): void
    {
        if (! $logger->remote_host || ! $logger->remote_ssh_user || ! $logger->remote_ssh_password) {
            throw ValidationException::withMessages([
                'data_logger_id' => 'Lengkapi IP / Host Remote, SSH User, dan SSH Password di menu Data Loggers dulu.',
            ]);
        }
    }

    private function assertZipLooksDeployable(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['gateway_zip' => 'File ZIP tidak bisa dibuka.']);
        }

        $hasDeployableFile = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (preg_match('#(^|/)(gateway\.js|server\.js|package\.json)$#', $name)) {
                $hasDeployableFile = true;
                break;
            }
        }
        $zip->close();

        if (! $hasDeployableFile) {
            throw ValidationException::withMessages([
                'gateway_zip' => 'ZIP harus berisi gateway.js, server.js, atau package.json.',
            ]);
        }
    }

    private function uploadZip(DataLogger $logger, string $localPath, string $remoteZip, array &$log): void
    {
        $sftp = new SFTP($logger->remote_host, (int) ($logger->remote_ssh_port ?: 22), 15);
        if (! $sftp->login($logger->remote_ssh_user ?: 'root', $logger->remote_ssh_password)) {
            throw ValidationException::withMessages([
                'rednode_builder' => 'Login SFTP gagal. Cek kredensial SSH Data Logger.',
            ]);
        }

        $log[] = '[sftp] upload ZIP ke ' . $remoteZip;
        if (! $sftp->put($remoteZip, $localPath, SFTP::SOURCE_LOCAL_FILE)) {
            throw ValidationException::withMessages([
                'rednode_builder' => 'Upload ZIP ke logger gagal.',
            ]);
        }
    }

    private function runSsh(DataLogger $logger, string $command, int $timeoutSeconds): string
    {
        $ssh = new SSH2($logger->remote_host, (int) ($logger->remote_ssh_port ?: 22), 15);
        $ssh->setTimeout($timeoutSeconds);

        if (! $ssh->login($logger->remote_ssh_user ?: 'root', $logger->remote_ssh_password)) {
            throw ValidationException::withMessages([
                'rednode_builder' => 'Login SSH gagal. Cek kredensial SSH Data Logger.',
            ]);
        }

        $exitMarker = '__RESQ_BUILDER_EXIT__';
        $output = $ssh->exec($command . "\nprintf '\\n" . $exitMarker . ":%s\\n' \"$?\"");
        $exitStatus = $ssh->getExitStatus();

        if (preg_match('/\R?' . preg_quote($exitMarker, '/') . ':(-?\d+)\s*$/', $output ?: '', $matches)) {
            $exitStatus = (int) $matches[1];
            $output = preg_replace('/\R?' . preg_quote($exitMarker, '/') . ':-?\d+\s*$/', '', $output ?: '');
        }

        if ($exitStatus !== 0) {
            throw ValidationException::withMessages([
                'rednode_builder' => trim($output ?: '') ?: 'Build remote gagal tanpa output.',
            ]);
        }

        return $output ?: '';
    }

    private function buildRemoteCommand(
        DataLogger $logger,
        string $remoteZip,
        string $gatewayPath,
        string $serviceName,
        string $entryFile,
        string $appUrl,
        string $serialPort,
        int $baudRate,
        int $pollIntervalMs,
        bool $installDependencies,
        bool $startService,
    ): string {
        $gatewayPath = rtrim($gatewayPath, '/');
        $entryFile = ltrim($entryFile, '/');
        $env = $this->gatewayEnv($logger, $appUrl, $serialPort, $baudRate, $pollIntervalMs);
        $envContent = collect($env)->map(fn ($value, $key) => $this->dotenvLine($key, (string) $value))->implode("\n") . "\n";

        $installFlag = $installDependencies ? '1' : '0';
        $startFlag = $startService ? '1' : '0';

        $script = implode("\n", [
            'set -e',
            'echo "[builder] login remote: $(whoami)@$(hostname)"',
            'export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:$PATH"',
            'REMOTE_ZIP=' . escapeshellarg($remoteZip),
            'INSTALL_DIR=' . escapeshellarg($gatewayPath),
            'SERVICE_NAME=' . escapeshellarg($serviceName),
            'ENTRY_FILE=' . escapeshellarg($entryFile),
            'INSTALL_DEPS=' . escapeshellarg($installFlag),
            'START_SERVICE=' . escapeshellarg($startFlag),
            'TMP_DIR="/tmp/resq-rednode-build-$(date +%Y%m%d%H%M%S)"',
            'command -v unzip >/dev/null 2>&1 || { echo "unzip belum tersedia di logger"; exit 12; }',
            'command -v node >/dev/null 2>&1 || { echo "node belum tersedia di logger"; exit 13; }',
            'rm -rf "$TMP_DIR"',
            'mkdir -p "$TMP_DIR" "$INSTALL_DIR"',
            'echo "[builder] extract $REMOTE_ZIP"',
            'unzip -oq "$REMOTE_ZIP" -d "$TMP_DIR"',
            'SRC_DIR="$TMP_DIR"',
            'FIRST_ITEM="$(find "$TMP_DIR" -mindepth 1 -maxdepth 1 | head -n 1)"',
            'ITEM_COUNT="$(find "$TMP_DIR" -mindepth 1 -maxdepth 1 | wc -l | tr -d " ")"',
            'if [ "$ITEM_COUNT" = "1" ] && [ -d "$FIRST_ITEM" ]; then SRC_DIR="$FIRST_ITEM"; fi',
            'if [ ! -f "$SRC_DIR/$ENTRY_FILE" ] && [ -d "$SRC_DIR/modbus-server" ]; then SRC_DIR="$SRC_DIR/modbus-server"; fi',
            'if [ ! -f "$SRC_DIR/$ENTRY_FILE" ] && [ -f "$SRC_DIR/gateway.js" ]; then ENTRY_FILE="gateway.js"; fi',
            'if [ ! -f "$SRC_DIR/$ENTRY_FILE" ] && [ -f "$SRC_DIR/server.js" ]; then ENTRY_FILE="server.js"; fi',
            'if [ ! -f "$SRC_DIR/$ENTRY_FILE" ]; then echo "entry file tidak ditemukan: $ENTRY_FILE"; find "$SRC_DIR" -maxdepth 2 -type f | sed "s#^#[file] #"; exit 14; fi',
            'echo "[builder] sync files ke $INSTALL_DIR"',
            'cp -a "$SRC_DIR"/. "$INSTALL_DIR"/',
            'cat > "$INSTALL_DIR/.env" <<\'EOF_ENV\'',
            $envContent,
            'EOF_ENV',
            'chmod 600 "$INSTALL_DIR/.env" || true',
            'cd "$INSTALL_DIR"',
            'if [ "$INSTALL_DEPS" = "1" ] && [ -f package.json ]; then echo "[builder] npm install"; npm install --omit=dev || npm install --production; fi',
            'if command -v systemctl >/dev/null 2>&1; then',
            '  cat > "/etc/systemd/system/${SERVICE_NAME}.service" <<EOF_SERVICE',
            '[Unit]',
            'Description=RESQ RedNode Gateway',
            'After=network-online.target',
            'Wants=network-online.target',
            '',
            '[Service]',
            'Type=simple',
            'WorkingDirectory=' . $gatewayPath,
            'EnvironmentFile=' . $gatewayPath . '/.env',
            'ExecStart=/usr/bin/env node ' . $gatewayPath . '/${ENTRY_FILE}',
            'Restart=always',
            'RestartSec=3',
            '',
            '[Install]',
            'WantedBy=multi-user.target',
            'EOF_SERVICE',
            '  systemctl daemon-reload',
            '  systemctl enable "$SERVICE_NAME"',
            '  if [ "$START_SERVICE" = "1" ]; then systemctl restart "$SERVICE_NAME"; sleep 2; systemctl is-active "$SERVICE_NAME"; fi',
            'else',
            '  echo "[builder] systemctl tidak tersedia, fallback nohup"',
            '  if [ "$START_SERVICE" = "1" ]; then pkill -f "node .*${ENTRY_FILE}" 2>/dev/null || true; nohup node "$INSTALL_DIR/$ENTRY_FILE" >> "$INSTALL_DIR/gateway.log" 2>&1 & fi',
            'fi',
            'rm -rf "$TMP_DIR" "$REMOTE_ZIP"',
            'echo "[builder] done"',
        ]);

        return 'sh -c ' . escapeshellarg($script);
    }

    private function gatewayEnv(DataLogger $logger, string $appUrl, string $serialPort, int $baudRate, int $pollIntervalMs): array
    {
        return [
            'APP_URL' => $appUrl,
            'REDNODE_CONFIG_URL' => $appUrl . '/api/rednode/config',
            'REDNODE_CALLBACK_URL' => $appUrl . '/api/realtime-sensor-status',
            'REDNODE_HEARTBEAT_URL' => $appUrl . '/api/rednode/heartbeat',
            'REDNODE_CONFIG_TOKEN' => env('REDNODE_CONFIG_TOKEN') ?: env('MODBUS_CALLBACK_TOKEN') ?: env('MQTT_CALLBACK_TOKEN', ''),
            'REDNODE_CALLBACK_TOKEN' => env('REDNODE_CALLBACK_TOKEN') ?: env('MODBUS_CALLBACK_TOKEN') ?: env('MQTT_CALLBACK_TOKEN', ''),
            'REDNODE_LOGGER_CODE' => $logger->logger_code,
            'REDNODE_SERIAL_PORT' => $serialPort,
            'REDNODE_BAUD_RATE' => $baudRate,
            'REDNODE_DATA_BITS' => env('REDNODE_DATA_BITS', 8),
            'REDNODE_STOP_BITS' => env('REDNODE_STOP_BITS', 1),
            'REDNODE_PARITY' => env('REDNODE_PARITY', 'none'),
            'REDNODE_TIMEOUT_MS' => env('REDNODE_TIMEOUT_MS', 1500),
            'REDNODE_CONFIG_REFRESH_MS' => env('REDNODE_CONFIG_REFRESH_MS', 15000),
            'REDNODE_HEARTBEAT_MS' => env('REDNODE_HEARTBEAT_MS', 5000),
            'REDNODE_LOOP_TICK_MS' => max(250, min($pollIntervalMs, 5000)),
        ];
    }

    private function dotenvLine(string $key, string $value): string
    {
        $escaped = str_replace(["\\", "\"", "\n", "\r"], ["\\\\", "\\\"", "\\n", ''], $value);

        return $key . '="' . $escaped . '"';
    }

    private function logLines(string $output): array
    {
        return collect(preg_split('/\R/', $output) ?: [])
            ->map(fn ($line) => rtrim($line))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->all();
    }
}
