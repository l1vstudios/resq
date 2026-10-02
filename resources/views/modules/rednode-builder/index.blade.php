@extends('layouts.master')

@section('title') RedNode Builder @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Device Setup @endslot
@slot('title') RedNode Builder @endslot
@endcomponent

@php
    $buildLog = session('rednode_builder_log', []);
@endphp

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <h4 class="card-title mb-1">RedNode Gateway Builder</h4>
                        <p class="text-muted mb-0">Upload ZIP gateway, pilih logger tujuan, lalu deploy konfigurasi runtime via SSH.</p>
                    </div>
                    <a href="{{ route('data-loggers.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-server me-1"></i> Data Loggers
                    </a>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <div class="fw-semibold mb-1">Build belum berhasil.</div>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif

                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Logger</th>
                                <th>Remote</th>
                                <th>Gateway</th>
                                <th>MQTT</th>
                                <th>Last Build</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dataLoggers as $logger)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $logger->logger_code }}</div>
                                        <small class="text-muted">{{ $logger->monitoringStation?->station_code ?? $logger->monitoringStation?->name ?? 'No station' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $logger->remote_host ?: '-' }}</div>
                                        <small class="text-muted">{{ ($logger->remote_ssh_user ?: '-') . '@' . ($logger->remote_ssh_port ?: 22) }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $logger->remote_gateway_path ?: $defaultGatewayPath }}</div>
                                        <small class="text-muted">{{ $logger->node_red_service_name ?: $defaultServiceName }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $logger->nodeRedMqttConfiguration?->configuration_code ?: '-' }}</div>
                                        <small class="text-muted">{{ $logger->nodeRedMqttConfiguration?->broker_url ?: 'MQTT belum dipilih' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $logger->node_red_last_status === 'Built' ? 'bg-success' : ($logger->node_red_last_status === 'Failed' ? 'bg-danger' : 'bg-secondary') }}">
                                            {{ $logger->node_red_last_status ?: '-' }}
                                        </span>
                                        <div><small class="text-muted">{{ optional($logger->node_red_last_applied_at)->diffForHumans() ?: '-' }}</small></div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada data logger.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('rednode-builder.build') }}" enctype="multipart/form-data" id="rednode-builder-form">
                    @csrf
                    <div class="row">
                        <div class="col-xl-5">
                            <div class="border rounded p-3 mb-3">
                                <h5 class="font-size-14 mb-3">Target Logger</h5>
                                <div class="mb-3">
                                    <label class="form-label">Data Logger</label>
                                    <select name="data_logger_id" class="form-select" id="builder-logger-select" required @disabled($dataLoggers->isEmpty())>
                                        @foreach($dataLoggers as $logger)
                                            <option
                                                value="{{ $logger->id }}"
                                                data-host="{{ $logger->remote_host }}"
                                                data-user="{{ $logger->remote_ssh_user ?: 'root' }}"
                                                data-port="{{ $logger->remote_ssh_port ?: 22 }}"
                                                data-path="{{ $logger->remote_gateway_path ?: $defaultGatewayPath }}"
                                                data-service="{{ $logger->node_red_service_name ?: $defaultServiceName }}"
                                                data-mqtt="{{ $logger->node_red_mqtt_configuration_id }}"
                                                data-poll="{{ $logger->poll_interval_ms ?: 1000 }}"
                                            >
                                                {{ $logger->logger_code }} - {{ $logger->remote_host ?: 'IP belum diisi' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">IP, user, dan password SSH diambil dari menu Data Loggers.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">MQTT Configuration</label>
                                    <select name="mqtt_configuration_id" class="form-select" id="builder-mqtt-select">
                                        <option value="">Pakai setting logger saat ini</option>
                                        @foreach($mqttConfigurations as $config)
                                            <option value="{{ $config->id }}">
                                                {{ $config->configuration_code }} - {{ $config->name }}
                                                @if($config->project)
                                                    ({{ $config->project->project_code }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row">
                                    <div class="col-md-7 mb-3">
                                        <label class="form-label">Gateway Path</label>
                                        <input name="gateway_path" id="builder-gateway-path" class="form-control" value="{{ old('gateway_path', $defaultGatewayPath) }}" required>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <label class="form-label">Service Name</label>
                                        <input name="service_name" id="builder-service-name" class="form-control" value="{{ old('service_name', $defaultServiceName) }}" required>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">APP URL untuk Logger</label>
                                    <input name="app_url" class="form-control" value="{{ old('app_url', $defaultAppUrl) }}" placeholder="http://192.168.1.9:8000" required>
                                    <div class="form-text">Gunakan IP Mac yang bisa diakses dari jaringan RedNode.</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-7">
                            <div class="border rounded p-3 mb-3">
                                <h5 class="font-size-14 mb-3">Build Package</h5>
                                <div class="mb-3">
                                    <label class="form-label">ZIP RedNode Gateway</label>
                                    <input type="file" name="gateway_zip" class="form-control" accept=".zip,application/zip" required>
                                    <div class="form-text">ZIP harus berisi <code>gateway.js</code>, <code>server.js</code>, atau <code>package.json</code>.</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Entry File</label>
                                        <input name="entry_file" class="form-control" value="{{ old('entry_file', 'gateway.js') }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Serial Port</label>
                                        <input name="serial_port" class="form-control" value="{{ old('serial_port', $defaultSerialPort) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Baud Rate</label>
                                        <input type="number" name="baud_rate" class="form-control" value="{{ old('baud_rate', 9600) }}" min="300" max="921600" required>
                                    </div>
                                </div>
                                <div class="row align-items-end">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Poll Interval</label>
                                        <input type="number" name="poll_interval_ms" id="builder-poll-interval" class="form-control" value="{{ old('poll_interval_ms', 1000) }}" min="250" max="600000" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input type="hidden" name="install_dependencies" value="0">
                                            <input class="form-check-input" type="checkbox" name="install_dependencies" value="1" id="install-deps" checked>
                                            <label class="form-check-label" for="install-deps">npm install</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input type="hidden" name="start_service" value="0">
                                            <input class="form-check-input" type="checkbox" name="start_service" value="1" id="start-service" checked>
                                            <label class="form-check-label" for="start-service">Start setelah build</label>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-primary" @disabled($dataLoggers->isEmpty())>
                                    <i class="bx bx-play-circle me-1"></i> Build
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if(! empty($buildLog))
                    <div class="border rounded p-3 mt-3">
                        <h5 class="font-size-14 mb-3">Build Log</h5>
                        <pre class="bg-light border rounded p-3 mb-0 small" style="max-height: 420px; overflow:auto; white-space: pre-wrap;">{{ implode("\n", $buildLog) }}</pre>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(() => {
    const loggerSelect = document.getElementById('builder-logger-select');
    const mqttSelect = document.getElementById('builder-mqtt-select');
    const gatewayPath = document.getElementById('builder-gateway-path');
    const serviceName = document.getElementById('builder-service-name');
    const pollInterval = document.getElementById('builder-poll-interval');

    if (!loggerSelect) return;

    function syncLoggerDefaults() {
        const selected = loggerSelect.options[loggerSelect.selectedIndex];
        if (!selected) return;

        gatewayPath.value = selected.dataset.path || gatewayPath.value;
        serviceName.value = selected.dataset.service || serviceName.value;
        pollInterval.value = selected.dataset.poll || pollInterval.value;

        if (selected.dataset.mqtt && mqttSelect) {
            mqttSelect.value = selected.dataset.mqtt;
        }
    }

    loggerSelect.addEventListener('change', syncLoggerDefaults);
    syncLoggerDefaults();
})();
</script>
@endsection
