@extends('layouts.master')

@section('title') Data Loggers @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Device Setup @endslot
@slot('title') Data Loggers @endslot
@endcomponent

@php
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
    $dataLoggerDiscoveries = collect($dataLoggerDiscoveries ?? []);
    $monitoringStations = collect($monitoringStations ?? []);
    $mqttConfigurations = collect($mqttConfigurations ?? []);
@endphp

<div class="card">
    <div class="card-body">
        <ul class="nav nav-tabs nav-tabs-custom mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="logger-setup-tab" data-bs-toggle="tab" data-bs-target="#logger-setup-pane" type="button" role="tab" aria-controls="logger-setup-pane" aria-selected="true">
                    Setup Logger
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="logger-remote-tab" data-bs-toggle="tab" data-bs-target="#logger-remote-pane" type="button" role="tab" aria-controls="logger-remote-pane" aria-selected="false">
                    Remote Tools
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="logger-list-tab" data-bs-toggle="tab" data-bs-target="#logger-list-pane" type="button" role="tab" aria-controls="logger-list-pane" aria-selected="false">
                    Logger List
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="logger-setup-pane" role="tabpanel" aria-labelledby="logger-setup-tab" tabindex="0">
                <div class="card border mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Data Logger Setup</h4>
                        <form method="POST" action="{{ route('data-loggers.store') }}" id="data-logger-form">
                            @csrf
                            <input type="hidden" name="discovery_id">
                            <div class="mb-3">
                                <label class="form-label">Monitoring Station</label>
                                <select name="monitoring_station_id" class="form-select">
                                    <option value="">-</option>
                                    @foreach ($monitoringStations->whereNotNull('db_id') as $station)
                                        <option value="{{ $station['db_id'] }}">{{ $station['id'] }} - {{ $station['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logger ID</label>
                                <input name="logger_code" class="form-control" placeholder="DL-PDG-001" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Serial Number</label>
                                <input name="serial_number" class="form-control">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Model</label>
                                    <input name="logger_model" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Vendor</label>
                                    <input name="vendor" class="form-control">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Firmware</label>
                                    <input name="firmware_version" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Device Label / QR</label>
                                    <input name="device_label" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IP / Host Remote</label>
                                <input name="remote_host" class="form-control" placeholder="192.168.3.1">
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">SSH Port</label>
                                    <input type="number" name="remote_ssh_port" class="form-control" value="22" min="1" max="65535">
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">SSH User</label>
                                    <input name="remote_ssh_user" class="form-control" placeholder="root">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SSH Password</label>
                                <input type="password" name="remote_ssh_password" class="form-control" placeholder="Biarkan kosong kalau sudah pernah disimpan">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gateway Path</label>
                                <input name="remote_gateway_path" class="form-control" placeholder="/root/rednode-gateway">
                            </div>
                            <hr class="my-4">
                            <h5 class="mb-3">Node-RED MQTT Remote</h5>
                            <div class="mb-3">
                                <label class="form-label">MQTT Configuration</label>
                                <select name="node_red_mqtt_configuration_id" class="form-select">
                                    <option value="">-</option>
                                    @foreach ($mqttConfigurations->where('is_active', true)->where('consumer_enabled', true) as $config)
                                        <option value="{{ $config['db_id'] }}">
                                            {{ $config['configuration_code'] }} - {{ $config['name'] }}
                                            @if (! empty($config['project_code']))
                                                ({{ $config['project_code'] }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Pilih config broker MQTT yang akan dipakai Node-RED untuk publish telemetry ke RESQ.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Node-RED Publish Topic</label>
                                <input name="node_red_publish_topic" class="form-control" placeholder="resq/telemetry/* atau resq/telemetry/SNS-01">
                                <div class="form-text">Topic publish Node-RED. Isi <code>*</code> atau <code>#</code> di akhir untuk test publish ke semua sensor sekaligus. Kalau kosong, RESQ pakai consumer topic.</div>
                            </div>
                            <div class="alert alert-info py-3 mb-3">
                                <div class="fw-semibold mb-1">Plug and Play Mode</div>
                                <div>RESQ akan otomatis menulis konfigurasi MQTT Node-RED ke server remote dan restart service tanpa perlu isi folder, path, atau command manual.</div>
                                <div class="small mt-2">
                                    File config yang ditulis:
                                    <code>/etc/systemd/system/node-red.service.d/resq-mqtt.conf</code>
                                </div>
                                <div class="small">
                                    Service yang direstart:
                                    <code>node-red</code>
                                </div>
                                <div class="small">
                                    Lokasi user dir yang diasumsikan:
                                    <code>/root/.node-red</code> atau <code>/home/&lt;ssh-user&gt;/.node-red</code>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="logger_status" class="form-select" required>
                                    <option>Active</option>
                                    <option>Inactive</option>
                                    <option>Maintenance</option>
                                    <option>Fault</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Save / Update Logger</button>
                        </form>
                    </div>
                </div>

                <div class="card border">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Detected Gateway Devices</h4>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Device</th>
                                        <th>Firmware</th>
                                        <th>IP</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($dataLoggerDiscoveries as $discovery)
                                        @php
                                            $claimCode = $discovery['logger_code']
                                                ?: ($discovery['serial_number'] ?: ($discovery['hostname'] ?: 'DL-' . str_pad((string) $discovery['db_id'], 3, '0', STR_PAD_LEFT)));
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $discovery['device_label'] ?: $discovery['hostname'] ?: $claimCode }}</div>
                                                <small class="text-muted d-block">{{ $discovery['serial_number'] ?: $discovery['device_uid'] ?: '-' }}</small>
                                                @if (! empty($discovery['matched_logger_code']))
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $discovery['matched_logger_code'] }}</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ $discovery['status'] ?? 'Detected' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div>{{ $discovery['firmware_version'] ?: '-' }}</div>
                                                <small class="text-muted">{{ $discovery['logger_model'] ?: '-' }}</small>
                                            </td>
                                            <td>
                                                <div>{{ $discovery['request_ip'] ?: '-' }}</div>
                                                <small class="text-muted">{{ $discovery['last_seen_at'] ?: '-' }}</small>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    data-edit-form="#data-logger-form"
                                                    data-edit-fields="{{ base64_encode(json_encode([
                                                        'discovery_id' => $discovery['db_id'] ?? '',
                                                        'logger_code' => $claimCode,
                                                        'serial_number' => $discovery['serial_number'] ?? '',
                                                        'logger_model' => $discovery['logger_model'] ?? '',
                                                        'vendor' => $discovery['vendor'] ?? '',
                                                        'firmware_version' => $discovery['firmware_version'] ?? '',
                                                        'device_label' => $discovery['device_label'] ?: ($discovery['hostname'] ?? ''),
                                                        'remote_host' => $discovery['request_ip'] ?? '',
                                                        'remote_ssh_port' => 22,
                                                        'remote_ssh_user' => 'root',
                                                        'remote_gateway_path' => '/root/rednode-gateway',
                                                        'logger_status' => 'Active',
                                                    ])) }}">Use</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Belum ada gateway yang terdeteksi.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="logger-remote-pane" role="tabpanel" aria-labelledby="logger-remote-tab" tabindex="0">
                <div class="card border mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Test Remote Logger</h4>
                        <div class="mb-3">
                            <label class="form-label">Data Logger</label>
                            <select class="form-select" id="remote-test-logger" @disabled($dataLoggers->whereNotNull('db_id')->isEmpty())>
                                @foreach ($dataLoggers->whereNotNull('db_id') as $logger)
                                    <option value="{{ $logger['db_id'] }}">{{ $logger['id'] }} - {{ $logger['remote_host'] ?? 'IP belum diisi' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="remote-test-run" @disabled($dataLoggers->whereNotNull('db_id')->isEmpty())>
                            <i class="bx bx-wifi me-1"></i> Ping IP Logger
                        </button>
                        <div class="form-text">Ping hanya cek IP bisa dijangkau. Scan gateway tetap butuh SSH, Node.js, folder gateway, dan file script di logger.</div>
                        <div class="alert alert-info mt-3 mb-0 d-none" id="remote-test-message"></div>
                    </div>
                </div>

                <div class="card border">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Node-RED MQTT Remote</h4>
                        <div class="mb-3">
                            <label class="form-label">Data Logger</label>
                            <select class="form-select" id="node-red-mqtt-logger" @disabled($dataLoggers->whereNotNull('db_id')->isEmpty())>
                                @foreach ($dataLoggers->whereNotNull('db_id') as $logger)
                                    <option value="{{ $logger['db_id'] }}" data-has-mqtt="{{ empty($logger['node_red_mqtt_configuration_id']) ? '0' : '1' }}">
                                        {{ $logger['id'] }} - {{ $logger['node_red_mqtt_configuration_code'] ?? 'MQTT belum dipilih' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary" id="node-red-mqtt-apply" @disabled($dataLoggers->whereNotNull('db_id')->isEmpty())>
                                <i class="bx bx-upload me-1"></i> Apply MQTT via SSH
                            </button>
                            <button type="button" class="btn btn-outline-success" id="node-red-mqtt-test" @disabled($dataLoggers->whereNotNull('db_id')->isEmpty())>
                                <i class="bx bx-check-shield me-1"></i> Test MQTT from Remote
                            </button>
                        </div>
                        <div class="form-text mt-2">RESQ akan login ke server remote logger via SSH, menulis config ke <code>/etc/systemd/system/node-red.service.d/resq-mqtt.conf</code>, restart service <code>node-red</code>, lalu mensimulasikan koneksi MQTT dari sisi server remote.</div>
                        <div class="alert alert-warning mt-3 mb-0 d-none" id="node-red-mqtt-empty-state">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <span>Pilih MQTT Configuration untuk logger ini dulu.</span>
                                <a href="{{ route('mqtt-configurations.index') }}" class="btn btn-warning btn-sm" target="_blank" rel="noopener noreferrer">
                                    <i class="bx bx-link-external me-1"></i> Buka MQTT Configuration
                                </a>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0 d-none" id="node-red-mqtt-message"></div>
                        <pre class="small bg-light border rounded p-3 mt-3 d-none" id="node-red-mqtt-log"></pre>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="logger-list-pane" role="tabpanel" aria-labelledby="logger-list-tab" tabindex="0">
                <div class="card border">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Data Logger List</h4>
                        <div class="alert alert-info d-none" id="gateway-mode-message"></div>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Logger ID</th>
                                        <th>Monitoring Station</th>
                                        <th>Serial Number</th>
                                        <th>Remote IP</th>
                                        <th>Node-RED MQTT</th>
                                        <th>Model</th>
                                        <th>Vendor</th>
                                        <th>Firmware</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($dataLoggers as $logger)
                                        <tr>
                                            <td>{{ $logger['id'] ?? '-' }}</td>
                                            <td>{{ $logger['monitoring_station_id'] ?? '-' }}</td>
                                            <td>{{ $logger['serial_number'] ?? '-' }}</td>
                                            <td>
                                                <div>{{ $logger['remote_host'] ?? '-' }}</div>
                                                <small class="text-muted">
                                                    {{ $logger['remote_ssh_user'] ?? '-' }}@{{ $logger['remote_ssh_port'] ?? 22 }}
                                                </small>
                                            </td>
                                            <td>
                                                <div>{{ $logger['node_red_mqtt_configuration_code'] ?? '-' }}</div>
                                                <small class="text-muted">{{ $logger['node_red_publish_topic'] ?? 'topic belum diisi' }}</small>
                                                @if (! empty($logger['node_red_last_status']))
                                                    <div><small class="{{ $logger['node_red_last_status'] === 'Success' || $logger['node_red_last_status'] === 'Applied' ? 'text-success' : 'text-danger' }}">{{ $logger['node_red_last_status'] }}</small></div>
                                                @endif
                                            </td>
                                            <td>{{ $logger['logger_model'] ?? '-' }}</td>
                                            <td>{{ $logger['vendor'] ?? '-' }}</td>
                                            <td>{{ $logger['firmware_version'] ?? '-' }}</td>
                                            <td>
                                                <span class="badge {{ ($logger['logger_status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary' }}">{{ $logger['logger_status'] ?? '-' }}</span>
                                                @if (! empty($logger['remote_last_status']))
                                                    <div><small class="{{ $logger['remote_last_status'] === 'Success' ? 'text-success' : 'text-danger' }}">{{ $logger['remote_last_status'] }}</small></div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @isset($logger['db_id'])
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                            data-logger-metadata="{{ base64_encode(json_encode($logger['metadata'] ?? [])) }}">
                                                            Metadata
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning btn-sm"
                                                            data-gateway-mode="development"
                                                            data-logger-id="{{ $logger['db_id'] }}">
                                                            Start Development
                                                        </button>
                                                        <button type="button" class="btn btn-outline-success btn-sm"
                                                            data-gateway-mode="production"
                                                            data-logger-id="{{ $logger['db_id'] }}">
                                                            Start Production
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#data-logger-form"
                                                            data-edit-fields="{{ base64_encode(json_encode([
                                                                'discovery_id' => '',
                                                                'monitoring_station_id' => $logger['monitoring_station_db_id'] ?? '',
                                                                'logger_code' => $logger['id'] ?? '',
                                                                'serial_number' => $logger['serial_number'] ?? '',
                                                                'logger_model' => $logger['logger_model'] ?? '',
                                                                'vendor' => $logger['vendor'] ?? '',
                                                                'firmware_version' => $logger['firmware_version'] ?? '',
                                                                'device_label' => $logger['device_label'] ?? '',
                                                                'remote_host' => $logger['remote_host'] ?? '',
                                                                'remote_ssh_port' => $logger['remote_ssh_port'] ?? 22,
                                                                'remote_ssh_user' => $logger['remote_ssh_user'] ?? '',
                                                                'remote_gateway_path' => $logger['remote_gateway_path'] ?? '',
                                                                'node_red_mqtt_configuration_id' => $logger['node_red_mqtt_configuration_id'] ?? '',
                                                                'node_red_publish_topic' => $logger['node_red_publish_topic'] ?? '',
                                                                'logger_status' => $logger['logger_status'] ?? 'Active',
                                                            ])) }}">Edit</button>
                                                        <form method="POST" action="{{ route('device-setup.destroy', ['type' => 'data-logger', 'id' => $logger['db_id']]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-outline-danger btn-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                @endisset
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">Belum ada data logger.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="logger-metadata-modal" tabindex="-1" aria-labelledby="logger-metadata-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logger-metadata-title">Logger Metadata</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <tbody id="logger-metadata-rows">
                            <tr>
                                <td class="text-muted">Metadata</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        const modalElement = document.getElementById('logger-metadata-modal');
        const title = document.getElementById('logger-metadata-title');
        const rows = document.getElementById('logger-metadata-rows');

        if (!modalElement || !title || !rows) {
            return;
        }

        const labels = {
            unique_device_key: 'Unique Device Key',
            unique_device_key_source: 'Unique Key Source',
            logger_code: 'Logger ID',
            monitoring_station: 'Monitoring Station',
            serial_number: 'Serial Number',
            logger_model: 'Model',
            vendor: 'Vendor',
            firmware_version: 'Firmware',
            device_label: 'Device Label / QR',
            remote_host: 'Remote IP / Host',
            remote_ssh_port: 'SSH Port',
            remote_ssh_user: 'SSH User',
            remote_gateway_path: 'Gateway Path',
            remote_last_tested_at: 'Last Connection Time',
            remote_last_status: 'Last Connection Status',
            remote_last_message: 'Last Connection Message',
            node_red_mqtt_configuration_code: 'Node-RED MQTT Config',
            node_red_publish_topic: 'Node-RED Publish Topic',
            node_red_service_name: 'Node-RED Service',
            node_red_user_dir: 'Node-RED User Dir',
            node_red_environment_file: 'Node-RED Drop-in File',
            node_red_restart_command: 'Node-RED Restart Command',
            node_red_last_applied_at: 'Node-RED Last Applied',
            node_red_last_tested_at: 'Node-RED Last Tested',
            node_red_last_status: 'Node-RED Last Status',
            node_red_last_message: 'Node-RED Last Message',
            detected_device_uid: 'Detected Device UID',
            detected_device_uid_source: 'Detected UID Source',
            detected_logger_code: 'Detected Logger ID',
            detected_serial_number: 'Detected Serial',
            detected_hostname: 'Detected Hostname',
            detected_ip: 'Detected IP',
            detected_mac_addresses: 'Detected MAC',
            detected_last_seen_at: 'Detected Last Seen',
            detected_status: 'Detected Status',
            gateway_version: 'Gateway Version',
            platform: 'Platform',
        };

        function parseMetadata(encoded) {
            try {
                return JSON.parse(atob(encoded || 'e30='));
            } catch (error) {
                return {};
            }
        }

        function formatValue(value) {
            if (Array.isArray(value)) {
                return value.length ? value.join(', ') : '-';
            }

            if (value && typeof value === 'object') {
                return JSON.stringify(value, null, 2);
            }

            return value === null || value === undefined || value === '' ? '-' : String(value);
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        document.querySelectorAll('[data-logger-metadata]').forEach((button) => {
            button.addEventListener('click', function () {
                const metadata = parseMetadata(button.dataset.loggerMetadata);
                const loggerCode = metadata.logger_code || 'Logger';
                title.textContent = loggerCode + ' Metadata';

                rows.innerHTML = Object.keys(labels).map((key) => {
                    const value = formatValue(metadata[key]);
                    const safeValue = escapeHtml(value);
                    const isLong = value.length > 80 || value.includes('{') || value.includes('[');

                    return '<tr>' +
                        '<td class="text-muted" style="width: 34%;">' + escapeHtml(labels[key]) + '</td>' +
                        '<td>' + (isLong
                            ? '<pre class="mb-0 small text-wrap">' + safeValue + '</pre>'
                            : safeValue) + '</td>' +
                    '</tr>';
                }).join('');

                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            });
        });
    })();

    (function () {
        const message = document.getElementById('gateway-mode-message');
        const buttons = document.querySelectorAll('[data-gateway-mode][data-logger-id]');

        if (!message || !buttons.length) {
            return;
        }

        function setMessage(text, type) {
            message.className = 'alert alert-' + (type || 'info');
            message.textContent = text || '';
            message.classList.toggle('d-none', !text);
        }

        buttons.forEach((button) => {
            button.addEventListener('click', async function () {
                const originalText = button.textContent;
                const mode = button.dataset.gatewayMode;
                const loggerId = button.dataset.loggerId;
                const modeLabel = mode === 'production' ? 'Production' : 'Development';

                button.disabled = true;
                button.textContent = 'Starting...';
                setMessage('Mengubah .env logger ke mode ' + modeLabel + ' dan restart gateway via SSH...', 'info');

                try {
                    const response = await fetch(@json(route('data-loggers.gateway-mode', [], false)), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({
                            data_logger_id: loggerId,
                            mode: mode,
                        }),
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || data.ok === false) {
                        throw new Error(data.message || 'Gagal menjalankan gateway mode ' + modeLabel + '.');
                    }

                    setMessage(data.message + ' APP_URL=' + data.app_url, 'success');
                } catch (error) {
                    setMessage(error.message, 'warning');
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        });
    })();

    (function () {
        const button = document.getElementById('remote-test-run');
        const select = document.getElementById('remote-test-logger');
        const message = document.getElementById('remote-test-message');

        if (!button || !select || !message) {
            return;
        }

        function setMessage(text, type) {
            message.className = 'alert mt-3 mb-0 alert-' + (type || 'info');
            message.textContent = text || '';
            message.classList.toggle('d-none', !text);
        }

        button.addEventListener('click', async function () {
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...';
            setMessage('Sedang ping IP logger dari server. Ini belum mengecek login SSH.', 'info');

            try {
                const response = await fetch(@json(route('data-loggers.test-remote', [], false)), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify({
                        data_logger_id: select.value,
                    }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    throw new Error(data.message || 'Ping logger gagal.');
                }

                setMessage(data.message + ' Host: ' + data.host, 'success');
            } catch (error) {
                setMessage(error.message, 'warning');
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        });
    })();

    (function () {
        const loggerSelect = document.getElementById('node-red-mqtt-logger');
        const applyButton = document.getElementById('node-red-mqtt-apply');
        const testButton = document.getElementById('node-red-mqtt-test');
        const emptyState = document.getElementById('node-red-mqtt-empty-state');
        const message = document.getElementById('node-red-mqtt-message');
        const logBox = document.getElementById('node-red-mqtt-log');

        if (!loggerSelect || !applyButton || !testButton || !emptyState || !message || !logBox) {
            return;
        }

        function setMessage(text, type) {
            message.className = 'alert mt-3 mb-0 alert-' + (type || 'info');
            message.textContent = text || '';
            message.classList.toggle('d-none', !text);
        }

        function setLog(lines) {
            const content = Array.isArray(lines) ? lines.filter(Boolean).join('\n') : '';
            logBox.textContent = content;
            logBox.classList.toggle('d-none', !content);
        }

        function syncEmptyState() {
            const selectedOption = loggerSelect.options[loggerSelect.selectedIndex];
            const hasMqtt = selectedOption?.dataset.hasMqtt === '1';

            emptyState.classList.toggle('d-none', hasMqtt);
        }

        async function run(action, button) {
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Processing...';
            setLog([]);
            setMessage(action === 'apply'
                ? 'RESQ sedang mengatur env MQTT Node-RED dan restart service via SSH...'
                : 'RESQ sedang mensimulasikan koneksi MQTT dari server remote via SSH...', 'info');

            try {
                const response = await fetch(action === 'apply'
                    ? @json(route('data-loggers.node-red-mqtt.apply', [], false))
                    : @json(route('data-loggers.node-red-mqtt.test', [], false)), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify({
                        data_logger_id: loggerSelect.value,
                    }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    throw {
                        message: data.message || 'Operasi Node-RED MQTT gagal.',
                        terminalLog: data.terminal_log || [],
                    };
                }

                setMessage(data.message || 'Berhasil.', 'success');
                setLog(data.terminal_log || []);
            } catch (error) {
                setMessage(error.message || 'Operasi Node-RED MQTT gagal.', 'warning');
                setLog(error.terminalLog || []);
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }

        applyButton.addEventListener('click', function () {
            run('apply', applyButton);
        });

        testButton.addEventListener('click', function () {
            run('test', testButton);
        });

        loggerSelect.addEventListener('change', syncEmptyState);
        syncEmptyState();
    })();
</script>
@endsection
