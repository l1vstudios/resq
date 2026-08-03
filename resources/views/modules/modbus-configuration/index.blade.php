@extends('layouts.master')

@section('title') Modbus Configuration @endsection

@section('css')
<style>
    .modbus-shell {
        background: #fff;
        border: 1px solid #eff2f7;
        color: #495057;
    }

    .modbus-title {
        color: #343a40;
        font-weight: 700;
    }

    .modbus-accent {
        color: #34c38f;
    }

    .modbus-panel {
        background: #fff;
        border: 1px solid #eff2f7;
        border-radius: .25rem;
    }

    .modbus-panel-title,
    .modbus-label {
        color: #495057;
        font-size: 14px;
        font-weight: 700;
    }

    .modbus-label {
        margin-bottom: 6px;
    }

    .modbus-btn-outline,
    .modbus-transport-toggle .btn {
        border-color: #34c38f;
        color: #34c38f;
        font-weight: 700;
    }

    .modbus-btn-outline:hover,
    .modbus-btn-outline.active,
    .modbus-transport-toggle .btn:hover,
    .modbus-transport-toggle .btn.active {
        background: #34c38f;
        border-color: #34c38f;
        color: #fff;
    }

    .modbus-btn-run,
    .modbus-btn-stop {
        background: #34c38f;
        border-color: #34c38f;
        color: #fff;
        font-weight: 800;
    }

    .modbus-status {
        background: rgba(52, 195, 143, .18);
        border: 1px solid rgba(52, 195, 143, .25);
        border-radius: 999px;
        color: #34c38f;
        font-size: 12px;
        font-weight: 800;
        padding: 4px 12px;
        text-transform: uppercase;
    }

    .modbus-status.offline {
        background: rgba(244, 106, 106, .18);
        border-color: rgba(244, 106, 106, .25);
        color: #f46a6a;
    }

    .modbus-table-wrap {
        min-height: 420px;
        background: #fff;
        border: 1px solid #eff2f7;
        border-radius: .25rem;
        overflow: hidden;
    }

    .modbus-table {
        color: #495057;
        margin-bottom: 0;
    }

    .modbus-table thead {
        background: #f8f9fa;
        color: #495057;
        text-transform: uppercase;
    }

    .modbus-table tbody tr {
        border-color: #eff2f7;
    }

    .modbus-value {
        color: #34c38f;
        font-weight: 800;
    }

    .modbus-hex {
        color: #f1b44c;
        font-weight: 800;
    }

    .modbus-footer {
        background: #f8f9fa;
        border-top: 1px solid #eff2f7;
        color: #74788d;
        font-weight: 700;
        min-height: 34px;
    }

    .mqtt-log-wrap {
        max-height: 280px;
        overflow: auto;
    }

    .mqtt-log-payload {
        color: #495057;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-size: 12px;
        white-space: normal;
        word-break: break-word;
    }

    .rednode-sensor-grid {
        border: 1px solid #eff2f7;
        border-radius: .25rem;
        max-height: 220px;
        overflow: auto;
    }

    .rednode-sensor-option {
        border-bottom: 1px solid #eff2f7;
        padding: 10px 12px;
    }

    .rednode-sensor-option:last-child {
        border-bottom: 0;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Device Setup @endslot
@slot('title') Modbus Configuration @endslot
@endcomponent

@php
    $sensors = collect($sensors ?? []);
    $sensorTypeLabels = [
        'water_level' => 'Sensor Tinggi Air',
        'rain_gauge' => 'Sensor Curah Hujan',
        'tide_level' => 'Sensor Pasang Surut',
        'seismic_vibration' => 'Sensor Getaran',
        'ground_movement' => 'Sensor Pergeseran Tanah',
        'soil_moisture' => 'Sensor Kelembapan Tanah',
        'river_flow' => 'Sensor Debit Air',
        'weather_station' => 'Sensor Cuaca',
        'temperature' => 'Sensor Suhu',
        'humidity' => 'Sensor Kelembapan',
        'pressure' => 'Sensor Tekanan Udara',
        'wind_speed' => 'Sensor Kecepatan Angin',
        'wind_direction' => 'Sensor Arah Angin',
        'battery_bms' => 'Sensor Baterai',
        'solar_charger' => 'Sensor Solar Charger',
        'device_health' => 'Sensor Kondisi Perangkat',
    ];
    $weatherParameterLabels = [
        'temperature' => 'Suhu',
        'humidity' => 'Kelembapan',
        'pressure' => 'Tekanan Udara',
        'wind_speed' => 'Kecepatan Angin',
        'wind_direction' => 'Arah Angin',
        'rainfall' => 'Curah Hujan',
        'solar_radiation' => 'Radiasi Matahari',
        'battery_voltage' => 'Tegangan Baterai',
    ];
    $sensorConfigs = $sensors->map(fn ($sensor) => [
        'db_id' => $sensor['db_id'] ?? $sensor['id'] ?? null,
        'code' => $sensor['id'] ?? '-',
        'label' => trim(($sensor['id'] ?? '-') . ' - ' . ($sensor['parameter'] ?? $sensor['type'] ?? 'Sensor')),
        'type' => $sensor['type'] ?? null,
        'parameter' => $sensor['parameter'] ?? null,
        'sensor_type_label' => $sensorTypeLabels[$sensor['type'] ?? ''] ?? (! empty($sensor['type']) ? 'Sensor ' . ucwords(str_replace('_', ' ', (string) $sensor['type'])) : 'Sensor'),
        'sensor_detail_label' => collect($sensor['weather_parameters'] ?? [])
            ->map(fn ($parameter) => $weatherParameterLabels[$parameter] ?? ucwords(str_replace('_', ' ', (string) $parameter)))
            ->filter()
            ->implode(', ') ?: ($sensor['parameter'] ?? ''),
        'monitoring_station_db_id' => $sensor['monitoring_station_db_id'] ?? null,
        'slave_id' => $sensor['slave_id'] ?? 1,
        'function_code' => $sensor['function_code'] ?? 'FC03',
        'address' => $sensor['address'] ?? 0,
        'quantity' => $sensor['quantity'] ?? 1,
        'poll_interval_ms' => $sensor['poll_interval_ms'] ?? 1000,
        'data_type' => $sensor['data_type'] ?? 'float32',
        'threshold' => $sensor['threshold'] ?? null,
        'scale_factor' => $sensor['scale_factor'] ?? 1,
        'offset' => $sensor['offset'] ?? 0,
        'unit' => $sensor['unit'] ?? '',
        'alert_level' => $sensor['alert_level'] ?? 'Waspada',
        'rule' => $sensor['rule'] ?? null,
        'status' => $sensor['status'] ?? 'Normal',
        'monitoring_station_id' => $sensor['monitoring_station_id'] ?? '-',
    ])->values();
    $gatewayBaseUrl = rtrim(env('MODBUS_BACKEND_URL', ''), '/');
    $mqttBrokerUrl = env('MQTT_BROKER_URL', '');
    $mqttTopic = env('MQTT_TOPIC', 'resq/telemetry/#');
    $mqttUsername = env('MQTT_USERNAME', '');
    $mqttPassword = env('MQTT_PASSWORD', '');
    $dataLoggers = collect($dataLoggers ?? []);
    $connectivity = collect($connectivity ?? []);
    $rednodeLoggerCode = env('REDNODE_LOGGER_CODE', 'REDNODE-BLIIOT-01');
    $rednodeSerialConfig = $connectivity
        ->first(fn ($item) => ($item['logger_id'] ?? null) === $rednodeLoggerCode && (($item['protocol'] ?? null) === 'Modbus RTU' || ! empty($item['serial_port'])))
        ?? $connectivity->first(fn ($item) => ($item['protocol'] ?? null) === 'Modbus RTU' || ! empty($item['serial_port']))
        ?? [];
    $rednodeLoggerCode = $rednodeSerialConfig['logger_id'] ?? $rednodeLoggerCode;
    $rednodeLogger = $dataLoggers->firstWhere('id', $rednodeLoggerCode) ?? [];
    $rednodePublicBaseUrl = rtrim(env('REDNODE_PUBLIC_APP_URL') ?: config('app.url') ?: url('/'), '/');
    $rednodeConfigUrl = $rednodePublicBaseUrl . '/api/rednode/config?logger_code=' . urlencode($rednodeLoggerCode);
    $ttyOptions = [
        ['pins' => 'PIN 1-2', 'mapping' => 'Pin 1 = B, Pin 2 = A', 'port' => '/dev/ttyAS4'],
        ['pins' => 'PIN 3-4', 'mapping' => 'Pin 3 = B, Pin 4 = A', 'port' => '/dev/ttyAS5'],
        ['pins' => 'PIN 5-6', 'mapping' => 'Pin 5 = B, Pin 6 = A', 'port' => '/dev/ttyAS2'],
        ['pins' => 'PIN 7-8', 'mapping' => 'Pin 7 = B, Pin 8 = A', 'port' => '/dev/ttyAS3'],
    ];
    $selectedSerialPort = $rednodeSerialConfig['serial_port'] ?? $rednodeSerialConfig['host_or_endpoint'] ?? env('REDNODE_SERIAL_PORT', '/dev/ttyAS2');
    $selectedPinMapping = $rednodeSerialConfig['pin_mapping'] ?? $rednodeSerialConfig['topic_or_api_path'] ?? collect($ttyOptions)->firstWhere('port', $selectedSerialPort)['mapping'] ?? '';
    $rednodePollIntervalSeconds = (($rednodeSerialConfig['rednode_poll_interval_ms'] ?? env('REDNODE_POLL_INTERVAL_MS', 1000)) / 1000);
    $selectedRednodeSensorIds = collect($rednodeSerialConfig['monitored_sensor_ids'] ?? [])
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->values();
    if ($selectedRednodeSensorIds->isEmpty()) {
        $selectedRednodeSensorIds = $sensorConfigs->pluck('db_id')->filter()->map(fn ($id) => (int) $id)->values();
    }
    $rednodeStatusBaseUrl = '/rednode-status';
    $rednodeStatusUrl = $rednodeStatusBaseUrl . '?logger_code=' . urlencode($rednodeLoggerCode);
    $rednodeControlUrl = route('rednode-control.store', [], false);
@endphp

<div class="card modbus-shell mb-4" id="modbus-monitor" data-api-base="{{ $gatewayBaseUrl }}">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 px-3 py-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-cog modbus-accent fs-4"></i>
                <span class="modbus-title fs-4">Modbus Configuration</span>
                <span class="text-muted fw-bold">MQTT Online Monitor</span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm modbus-btn-outline" id="modbus-clear">Clear</button>
                <button type="button" class="btn btn-sm modbus-btn-outline active">MQTT Client</button>
                <button type="button" class="btn btn-sm modbus-btn-outline" disabled>Modbus Bridge</button>
            </div>
        </div>

        <div class="row g-0">
            <div class="col-12 p-3 border-bottom">
                <div class="modbus-panel p-0 mb-3">
                    <ul class="nav nav-tabs nav-justified px-2 pt-2" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#integration-mqtt" type="button" role="tab">MQTT</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#integration-sensor" type="button" role="tab">Sensor</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#integration-rednode" type="button" role="tab">RedNode</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3">
                        <div class="tab-pane fade show active" id="integration-mqtt" role="tabpanel">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="modbus-panel-title"><i class="bx bx-plug me-1"></i> Connection</div>
                        <span class="modbus-status offline" id="modbus-status">Offline</span>
                    </div>
                    <div class="btn-group w-100 mb-3 modbus-transport-toggle" role="group">
                        <button type="button" class="btn btn-sm active" data-protocol="mqtt">MQTT</button>
                        <button type="button" class="btn btn-sm" data-protocol="modbus">Modbus TCP</button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label modbus-label">Gateway URL</label>
                        <input type="text" class="form-control" id="modbus-api-base" value="{{ $gatewayBaseUrl }}" placeholder="https://mqtt-gateway.example.com">
                    </div>
                    <div class="mb-3 modbus-fields">
                        <label class="form-label modbus-label">Host / IP Address</label>
                        <input type="text" class="form-control" id="modbus-host" placeholder="IP perangkat Modbus jika masih memakai bridge">
                    </div>
                    <div class="row modbus-fields">
                        <div class="col-6 mb-3">
                            <label class="form-label modbus-label">Port</label>
                            <input type="number" class="form-control" id="modbus-port" value="502" min="1" max="65535">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label modbus-label">Timeout (ms)</label>
                            <input type="number" class="form-control" id="modbus-timeout" value="1000" min="100">
                        </div>
                    </div>
                    <div class="mqtt-fields d-none">
                        <div class="mb-3">
                            <label class="form-label modbus-label">Broker URL</label>
                            <input type="text" class="form-control" id="mqtt-broker" value="{{ $mqttBrokerUrl }}" placeholder="mqtts://broker.example.com:8883">
                        </div>
                        <div class="mb-3">
                            <label class="form-label modbus-label">Topic</label>
                            <input type="text" class="form-control" id="mqtt-topic" value="{{ $mqttTopic }}" placeholder="resq/telemetry/#">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label modbus-label">Username</label>
                                <input type="text" class="form-control" id="mqtt-username" value="{{ $mqttUsername }}">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label modbus-label">Password</label>
                                <input type="password" class="form-control" id="mqtt-password" value="{{ $mqttPassword }}">
                            </div>
                        </div>
                        <div class="row align-items-end">
                            <div class="col-6 mb-3">
                                <label class="form-label modbus-label">Test Value</label>
                                <input type="text" class="form-control" id="mqtt-test-value" value="12.4">
                            </div>
                            <div class="col-6 mb-3">
                                <button type="button" class="btn btn-sm modbus-btn-outline w-100" id="mqtt-test">Test Broker</button>
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="mqtt-save-config" checked>
                            <label class="form-check-label fw-bold text-muted" for="mqtt-save-config">Simpan config MQTT</label>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary modbus-btn-run w-100" id="modbus-connect">Connect</button>
                    <button type="button" class="btn btn-danger modbus-btn-stop w-100 d-none" id="modbus-disconnect">Disconnect</button>
                        </div>
                        <div class="tab-pane fade" id="integration-sensor" role="tabpanel">
                    <div class="modbus-panel-title mb-3"><i class="bx bx-data me-1"></i> Sensor Configuration</div>
                    <div class="mb-3">
                        <label class="form-label modbus-label">Sensor</label>
                        <select class="form-select" id="modbus-sensor" @disabled($sensorConfigs->isEmpty())>
                            <option value="">Pilih sensor</option>
                            @foreach ($sensorConfigs as $sensor)
                                <option value="{{ $loop->index }}">{{ $sensor['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label modbus-label">Slave ID</label>
                        <input type="number" class="form-control" id="modbus-unit" value="1" min="1" max="247">
                    </div>
                    <div class="mb-3">
                        <label class="form-label modbus-label">Function Code</label>
                        <select class="form-select" id="modbus-function">
                            <option value="FC03">FC03 - Read Holding Register</option>
                            <option value="FC04">FC04 - Read Input Register</option>
                            <option value="FC01">FC01 - Read Coils</option>
                            <option value="FC02">FC02 - Read Discrete Inputs</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label modbus-label">Start Address</label>
                            <input type="number" class="form-control" id="modbus-address" value="0" min="0">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label modbus-label">Quantity</label>
                            <input type="number" class="form-control" id="modbus-quantity" value="1" min="1" max="125">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label modbus-label">Poll Interval (ms)</label>
                        <input type="number" class="form-control" id="modbus-interval" value="1000" min="250">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary modbus-btn-run flex-fill" id="modbus-read">Read Once</button>
                        <button type="button" class="btn btn-primary modbus-btn-run flex-fill" id="modbus-poll">Start Poll</button>
                    </div>
                    <div class="d-flex gap-4 mt-3 pt-3 border-top">
                        <div><div class="text-muted fw-bold">TX</div><div class="modbus-accent fs-5 fw-bold" id="modbus-tx">0</div></div>
                        <div><div class="text-muted fw-bold">RX</div><div class="text-success fs-5 fw-bold" id="modbus-rx">0</div></div>
                        <div><div class="text-muted fw-bold">ERR</div><div class="text-danger fs-5 fw-bold" id="modbus-err">0</div></div>
                    </div>
                        </div>
                        <div class="tab-pane fade" id="integration-rednode" role="tabpanel">
                    <div class="modbus-panel-title mb-3"><i class="bx bx-chip me-1"></i> RedNode Bliiot</div>
                    <form method="POST" action="{{ route('rednode-serial-config.store') }}" id="rednode-serial-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label modbus-label">Data Logger</label>
                            <select name="data_logger_id" class="form-select" id="rednode-data-logger">
                                <option value="">Buat / pakai kode di bawah</option>
                                @foreach ($dataLoggers->whereNotNull('db_id') as $logger)
                                    <option
                                        value="{{ $logger['db_id'] }}"
                                        data-logger-code="{{ $logger['id'] }}"
                                        data-monitoring-station-id="{{ $logger['monitoring_station_db_id'] ?? '' }}"
                                        @selected(($logger['id'] ?? null) === $rednodeLoggerCode)
                                    >{{ $logger['id'] }} - {{ $logger['device_label'] ?? 'RedNode' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label modbus-label">Logger Code</label>
                            <input name="logger_code" type="text" class="form-control" id="rednode-logger-code" value="{{ $rednodeLoggerCode }}" required readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label modbus-label">Port / Pin RS485</label>
                            <select name="serial_port" class="form-select" id="rednode-serial-port" required>
                                @foreach ($ttyOptions as $option)
                                    <option value="{{ $option['port'] }}" data-pin-mapping="{{ $option['mapping'] }}" @selected($selectedSerialPort === $option['port'])>
                                        {{ $option['pins'] }} - {{ $option['port'] }} ({{ $option['mapping'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="pin_mapping" id="rednode-pin-mapping" value="{{ $selectedPinMapping }}">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label modbus-label">Baudrate</label>
                                <input name="baud_rate" type="number" class="form-control" value="{{ $rednodeSerialConfig['baud_rate'] ?? env('REDNODE_BAUD_RATE', 9600) }}" min="300" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label modbus-label">Timeout</label>
                                <input name="timeout_ms" type="number" class="form-control" value="{{ $rednodeSerialConfig['timeout_ms'] ?? env('REDNODE_TIMEOUT_MS', 1500) }}" min="100" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4 mb-3">
                                <label class="form-label modbus-label">Data Bits</label>
                                <input name="data_bits" type="number" class="form-control" value="{{ $rednodeSerialConfig['data_bits'] ?? env('REDNODE_DATA_BITS', 8) }}" min="5" max="8" required>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label modbus-label">Stop Bits</label>
                                <input name="stop_bits" type="number" class="form-control" value="{{ $rednodeSerialConfig['stop_bits'] ?? env('REDNODE_STOP_BITS', 1) }}" min="1" max="2" required>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label modbus-label">Parity</label>
                                <select name="parity" class="form-select" required>
                                    @foreach (['none', 'even', 'odd'] as $parity)
                                        <option value="{{ $parity }}" @selected(($rednodeSerialConfig['parity'] ?? env('REDNODE_PARITY', 'none')) === $parity)>{{ ucfirst($parity) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label modbus-label">Polling Interval RedNode (detik)</label>
                            <input name="rednode_poll_interval_seconds" type="number" class="form-control" value="{{ $rednodePollIntervalSeconds }}" min="0.25" max="3600" step="0.25" required>
                        </div>
                        <input type="hidden" name="monitored_sensor_ids_present" value="1">
                        <div class="mb-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <label class="form-label modbus-label mb-0">Sensor Yang Dimonitor</label>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted fw-bold small" id="rednode-sensor-count">0 sensor</span>
                                    <button type="button" class="btn btn-sm modbus-btn-outline" id="rednode-select-all">Select All</button>
                                </div>
                            </div>
                            <div class="rednode-sensor-grid">
                                @forelse ($sensorConfigs as $sensor)
                                    @php
                                        $sensorDbId = (int) ($sensor['db_id'] ?? 0);
                                    @endphp
                                    <label
                                        class="rednode-sensor-option d-flex align-items-start gap-2 mb-0"
                                        for="rednode-sensor-{{ $sensorDbId }}"
                                        data-rednode-sensor-option
                                        data-monitoring-station-id="{{ $sensor['monitoring_station_db_id'] ?? '' }}"
                                    >
                                        <input
                                            class="form-check-input mt-1"
                                            type="checkbox"
                                            id="rednode-sensor-{{ $sensorDbId }}"
                                            name="monitored_sensor_ids[]"
                                            value="{{ $sensorDbId }}"
                                            data-rednode-sensor-check
                                            @checked($selectedRednodeSensorIds->contains($sensorDbId))
                                            @disabled(! $sensorDbId)
                                        >
                                        <span>
                                            <span class="fw-bold d-block">{{ $sensor['code'] }}</span>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle mb-1">
                                                {{ $sensor['sensor_type_label'] }}
                                            </span>
                                            @if (! empty($sensor['sensor_detail_label']))
                                                <span class="text-muted small ms-1">{{ $sensor['sensor_detail_label'] }}</span>
                                            @endif
                                            <span class="text-muted small d-block">
                                                Slave {{ $sensor['slave_id'] }} |
                                                {{ $sensor['function_code'] }} |
                                                Addr {{ $sensor['address'] }} |
                                                Qty {{ $sensor['quantity'] }}
                                            </span>
                                        </span>
                                    </label>
                                @empty
                                    <div class="text-muted fw-bold p-3">Belum ada sensor yang punya konfigurasi slave/address.</div>
                                @endforelse
                            </div>
                            <div class="form-text">Sensor yang dicentang akan dikirim ke RedNode saat gateway reload config.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label modbus-label">Config URL</label>
                            <input type="text" class="form-control" id="rednode-config-url" value="{{ $rednodeConfigUrl }}" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary modbus-btn-run w-100">Simpan Perubahan RedNode</button>
                    </form>
                    <div class="border-top mt-3 pt-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="modbus-panel-title">Live Status</div>
                            <span class="modbus-status offline" id="rednode-live-status">Offline</span>
                        </div>
                        <div class="small text-muted fw-bold mb-1">Port: <span id="rednode-live-port">{{ $selectedSerialPort }}</span></div>
                        <div class="small text-muted fw-bold mb-2">Last seen: <span id="rednode-live-seen">-</span></div>
                        <div class="alert alert-warning py-2 px-3 mb-2 d-none" id="rednode-live-error"></div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <tbody id="rednode-live-readings">
                                    <tr><td class="text-muted">Belum ada nilai sensor.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                        </div>
                    </div>
                    <div class="border-top px-3 py-2">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="modbus-panel-title"><i class="bx bx-list-ul me-1"></i> Integration Log</div>
                            <span class="text-muted fw-bold small" id="integration-log-count">0 event</span>
                        </div>
                        <div class="table-responsive mqtt-log-wrap">
                            <table class="table table-sm modbus-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:84px;">Time</th>
                                        <th style="width:86px;">Source</th>
                                        <th>Message</th>
                                        <th style="width:82px;">Result</th>
                                    </tr>
                                </thead>
                                <tbody id="integration-log-rows">
                                    <tr><td colspan="4" class="text-center text-muted py-3">Belum ada log integrasi.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modbus-panel p-3">
                    <div class="modbus-panel-title mb-3"><i class="bx bx-edit-alt me-1"></i> Write Register</div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label modbus-label">Function Code</label>
                            <select class="form-select" id="modbus-write-function">
                                <option value="FC06">FC06 - Write Register</option>
                                <option value="FC05">FC05 - Write Coil</option>
                                <option value="FC16">FC16 - Write Registers</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label modbus-label">Address</label>
                            <input type="number" class="form-control" id="modbus-write-address" value="0" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label modbus-label">Value</label>
                        <input type="text" class="form-control" id="modbus-write-value" value="0">
                    </div>
                    <button type="button" class="btn btn-danger modbus-btn-outline w-100" id="modbus-write">Send Write</button>
                </div>
            </div>

            <div class="col-12 p-3">
                <div class="modbus-table-wrap d-flex flex-column">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 border-bottom">
                        <div class="fw-bold text-muted">
                            <span class="badge bg-success-subtle text-success border border-success me-2" id="modbus-fc-label">FC03</span>
                            Sensor: <span id="modbus-sensor-label">-</span>
                            <span class="ms-3">Addr: <span id="modbus-address-label">0 - 0</span></span>
                            <span class="ms-3">Last update: <span id="modbus-last-update">-</span></span>
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn modbus-btn-outline" data-modbus-mode="dec">DEC</button>
                            <button type="button" class="btn modbus-btn-outline" data-modbus-mode="hex">HEX</button>
                            <button type="button" class="btn modbus-btn-outline" data-modbus-mode="bin">BIN</button>
                            <button type="button" class="btn modbus-btn-outline active" data-modbus-mode="float">FLOAT</button>
                        </div>
                    </div>
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-sm modbus-table align-middle">
                            <thead>
                                <tr>
                                    <th style="width:56px;">#</th>
                                    <th>Address</th>
                                    <th>Alias</th>
                                    <th>Value</th>
                                    <th>Hex</th>
                                    <th>Binary</th>
                                </tr>
                            </thead>
                            <tbody id="modbus-rows">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">Belum ada data register.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modbus-footer d-flex align-items-center px-3" id="modbus-summary">0 register</div>
                </div>
                <div class="alert alert-warning mt-3 mb-0 d-none" id="modbus-message"></div>

                <div class="modbus-panel mt-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 border-bottom">
                        <div class="modbus-panel-title"><i class="bx bx-terminal me-1"></i> MQTT Live Logger</div>
                        <span class="text-muted fw-bold small" id="mqtt-log-count">0 message</span>
                    </div>
                    <div class="table-responsive mqtt-log-wrap">
                        <table class="table table-sm modbus-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:120px;">Time</th>
                                    <th style="width:220px;">Topic</th>
                                    <th>Payload</th>
                                    <th style="width:110px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="mqtt-log-rows">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Belum ada message MQTT.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        const root = document.getElementById('modbus-monitor');
        if (!root) {
            return;
        }

        const sensorConfigs = @json($sensorConfigs);
        const sensorsByIndex = {};
        sensorConfigs.forEach((sensor, index) => {
            sensorsByIndex[String(index)] = sensor;
        });

        const el = (id) => document.getElementById(id);
        const rowsEl = el('modbus-rows');
        const mqttLogRowsEl = el('mqtt-log-rows');
        const integrationLogRowsEl = el('integration-log-rows');
        const messageEl = el('modbus-message');
        const apiBaseInput = el('modbus-api-base');
        const csrfToken = @json(csrf_token());
        const gatewayStatusUrl = window.location.origin + '/api/realtime-sensor-status';
        const realtimeStatusUrl = gatewayStatusUrl;
        const rednodeStatusBaseUrl = @json($rednodeStatusBaseUrl);
        const rednodeControlUrl = @json($rednodeControlUrl);
        const rednodeConfigBaseUrl = @json($rednodePublicBaseUrl . '/api/rednode/config');
        const gatewayCallbackToken = @json(env('MQTT_CALLBACK_TOKEN') ?: env('MODBUS_CALLBACK_TOKEN', ''));
        const state = {
            connected: false,
            polling: false,
            protocol: 'modbus',
            mode: 'float',
            timer: null,
            rows: [],
            sensor: null,
        };

        const storedApiBase = localStorage.getItem('modbusApiBase') || '';
        const hasLocalApiBase = /^(https?:\/\/)?(localhost|127\.0\.0\.1)(:|\/|$)/i.test(storedApiBase);
        apiBaseInput.value = hasLocalApiBase ? (root.dataset.apiBase || '') : (storedApiBase || root.dataset.apiBase || '');
        const rednodeSerialPort = el('rednode-serial-port');
        const rednodePinMapping = el('rednode-pin-mapping');
        const rednodeSerialForm = el('rednode-serial-form');
        const rednodeDataLogger = el('rednode-data-logger');
        const rednodeSensorChecks = Array.from(document.querySelectorAll('[data-rednode-sensor-check]'));
        const rednodeSensorOptions = Array.from(document.querySelectorAll('[data-rednode-sensor-option]'));
        const integrationLogs = [];
        let lastRednodeOnline = null;
        const mqttConfigKeys = {
            broker: 'resqMqttBrokerUrl',
            topic: 'resqMqttTopic',
            username: 'resqMqttUsername',
            password: 'resqMqttPassword',
            testValue: 'resqMqttTestValue',
            sensorIndex: 'resqMqttSensorIndex',
            save: 'resqMqttSaveConfig',
        };

        function loadStoredMqttConfig() {
            const shouldSave = localStorage.getItem(mqttConfigKeys.save);
            el('mqtt-save-config').checked = shouldSave === null ? true : shouldSave === 'true';

            if (!el('mqtt-save-config').checked) {
                return;
            }

            [
                ['mqtt-broker', mqttConfigKeys.broker],
                ['mqtt-topic', mqttConfigKeys.topic],
                ['mqtt-username', mqttConfigKeys.username],
                ['mqtt-password', mqttConfigKeys.password],
                ['mqtt-test-value', mqttConfigKeys.testValue],
            ].forEach(([id, key]) => {
                const value = localStorage.getItem(key);
                if (value !== null && value !== '') {
                    el(id).value = value;
                }
            });
        }

        function saveMqttConfig() {
            localStorage.setItem(mqttConfigKeys.save, String(el('mqtt-save-config').checked));

            if (!el('mqtt-save-config').checked) {
                [
                    mqttConfigKeys.broker,
                    mqttConfigKeys.topic,
                    mqttConfigKeys.username,
                    mqttConfigKeys.password,
                    mqttConfigKeys.testValue,
                    mqttConfigKeys.sensorIndex,
                ].forEach((key) => localStorage.removeItem(key));
                return;
            }

            localStorage.setItem('modbusApiBase', apiBaseInput.value.trim().replace(/\/$/, ''));
            localStorage.setItem(mqttConfigKeys.broker, el('mqtt-broker').value.trim());
            localStorage.setItem(mqttConfigKeys.topic, el('mqtt-topic').value.trim());
            localStorage.setItem(mqttConfigKeys.username, el('mqtt-username').value.trim());
            localStorage.setItem(mqttConfigKeys.password, el('mqtt-password').value);
            localStorage.setItem(mqttConfigKeys.testValue, el('mqtt-test-value').value.trim());
            localStorage.setItem(mqttConfigKeys.sensorIndex, el('modbus-sensor').value);
        }

        function showMessage(message, type) {
            messageEl.className = 'alert mt-3 mb-0 alert-' + (type || 'warning');
            messageEl.textContent = message;
            messageEl.classList.toggle('d-none', !message);
        }

        function apiBase() {
            const value = apiBaseInput.value.trim().replace(/\/$/, '');
            if (!value) {
                throw new Error('Isi Gateway URL online terlebih dahulu, atau jalankan MQTT autostart di server.');
            }
            localStorage.setItem('modbusApiBase', value);
            return value;
        }

        function numberValue(id, fallback) {
            const value = Number(el(id).value);
            return Number.isFinite(value) ? value : fallback;
        }

        function connectionPayload() {
            return {
                host: el('modbus-host').value.trim(),
                port: numberValue('modbus-port', 502),
                timeout: numberValue('modbus-timeout', 1000),
                unitId: numberValue('modbus-unit', 1),
            };
        }

        function callbackPayload() {
            return {
                url: gatewayStatusUrl,
                token: gatewayCallbackToken,
            };
        }

        function sensorPayload() {
            return state.sensor ? { ...state.sensor } : null;
        }

        function readPayload() {
            return {
                ...connectionPayload(),
                functionCode: el('modbus-function').value,
                address: numberValue('modbus-address', 0),
                quantity: numberValue('modbus-quantity', 1),
                interval: numberValue('modbus-interval', 1000),
                sensor: sensorPayload(),
                callback: callbackPayload(),
            };
        }

        function mqttPayload() {
            saveMqttConfig();

            return {
                brokerUrl: el('mqtt-broker').value.trim(),
                topic: el('mqtt-topic').value.trim(),
                username: el('mqtt-username').value.trim(),
                password: el('mqtt-password').value,
                timeout: numberValue('modbus-timeout', 10000),
                sensor: sensorPayload(),
                callback: callbackPayload(),
            };
        }

        function mqttTestPayload() {
            if (!state.sensor) {
                throw new Error('Pilih sensor dulu untuk test broker MQTT.');
            }

            saveMqttConfig();

            return {
                topic: el('mqtt-topic').value.trim(),
                sensor: sensorPayload(),
                value: el('mqtt-test-value').value.trim() || '12.4',
                qos: 0,
            };
        }

        async function request(path, payload) {
            const response = await fetch(apiBase() + path, {
                method: payload ? 'POST' : 'GET',
                headers: payload ? { 'Content-Type': 'application/json' } : {},
                body: payload ? JSON.stringify(payload) : undefined,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) {
                throw new Error(data.message || 'Modbus request failed.');
            }
            return data;
        }

        async function laravelRequest(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    ...(gatewayCallbackToken ? { 'Authorization': 'Bearer ' + gatewayCallbackToken } : {}),
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.ok === false) {
                throw new Error(data.message || 'Realtime sensor update failed.');
            }

            return data;
        }

        function setConnected(connected) {
            state.connected = connected;
            el('modbus-status').textContent = connected ? 'Online' : 'Offline';
            el('modbus-status').classList.toggle('offline', !connected);
            el('modbus-connect').classList.toggle('d-none', connected);
            el('modbus-disconnect').classList.toggle('d-none', !connected);
        }

        function setProtocol(protocol) {
            state.protocol = protocol;
            document.querySelectorAll('[data-protocol]').forEach((button) => {
                button.classList.toggle('active', button.dataset.protocol === protocol);
            });
            document.querySelectorAll('.modbus-fields').forEach((item) => {
                item.classList.toggle('d-none', protocol !== 'modbus');
            });
            document.querySelectorAll('.mqtt-fields').forEach((item) => {
                item.classList.toggle('d-none', protocol !== 'mqtt');
            });
            el('modbus-connect').textContent = protocol === 'mqtt' ? 'Connect MQTT' : 'Connect';
            el('modbus-read').disabled = protocol === 'mqtt';
            if (!state.polling) {
                el('modbus-poll').textContent = protocol === 'mqtt' ? 'Subscribe' : 'Start Poll';
            }
        }

        function setStats(stats) {
            if (!stats) {
                return;
            }
            el('modbus-tx').textContent = stats.tx ?? 0;
            el('modbus-rx').textContent = stats.rx ?? 0;
            el('modbus-err').textContent = stats.err ?? 0;
            if (stats.lastUpdate) {
                el('modbus-last-update').textContent = new Date(stats.lastUpdate).toLocaleTimeString();
            }
        }

        function aliasFor(functionCode, address) {
            const bases = {
                FC01: 1,
                FC02: 100001,
                FC03: 400000,
                FC04: 300000,
            };
            return (bases[functionCode] || 0) + Number(address || 0);
        }

        function escapeHtml(value) {
            return String(value ?? '-')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function appendIntegrationLog(source, message, result) {
            integrationLogs.unshift({
                source: source,
                message: message,
                result: result || 'Info',
                time: new Date(),
            });

            if (integrationLogs.length > 50) {
                integrationLogs.pop();
            }

            el('integration-log-count').textContent = integrationLogs.length + (integrationLogs.length === 1 ? ' event' : ' events');
            integrationLogRowsEl.innerHTML = integrationLogs.map((item) => {
                const normalized = String(item.result || '').toLowerCase();
                const badge = normalized.includes('gagal') || normalized.includes('error') || normalized.includes('offline')
                    ? 'bg-danger'
                    : (normalized.includes('sukses') || normalized.includes('online') ? 'bg-success' : 'bg-secondary');

                return '<tr>' +
                    '<td>' + escapeHtml(item.time.toLocaleTimeString()) + '</td>' +
                    '<td class="fw-bold">' + escapeHtml(item.source) + '</td>' +
                    '<td>' + escapeHtml(item.message) + '</td>' +
                    '<td><span class="badge ' + badge + '">' + escapeHtml(item.result) + '</span></td>' +
                '</tr>';
            }).join('');
        }

        function selectedRednodeSensorIds() {
            return rednodeSensorChecks
                .filter((input) => input.checked && !input.disabled)
                .map((input) => Number(input.value))
                .filter((value) => Number.isFinite(value) && value > 0);
        }

        function selectedRednodeSensorCodes() {
            const selectedIds = new Set(selectedRednodeSensorIds().map((id) => String(id)));
            return new Set(sensorConfigs
                .filter((sensor) => selectedIds.has(String(sensor.db_id || '')))
                .map((sensor) => String(sensor.code || sensor.sensor_code || ''))
                .filter(Boolean));
        }

        function selectedRednodeSensorNames() {
            const selectedIds = new Set(selectedRednodeSensorIds().map((id) => String(id)));
            return sensorConfigs
                .filter((sensor) => selectedIds.has(String(sensor.db_id || '')))
                .map((sensor) => sensor.code || sensor.sensor_code || sensor.label || 'Sensor')
                .filter(Boolean);
        }

        function decodeRegisterPairs(registers) {
            const values = Array.isArray(registers) ? registers : [];
            const decoded = [];

            for (let index = 0; index + 1 < values.length; index += 2) {
                const high = Number(values[index]) & 0xffff;
                const low = Number(values[index + 1]) & 0xffff;
                const buffer = new ArrayBuffer(4);
                const view = new DataView(buffer);

                view.setUint16(0, high, false);
                view.setUint16(2, low, false);

                const float32 = view.getFloat32(0, false);
                if (!Number.isFinite(float32)) {
                    continue;
                }

                decoded.push({
                    label: 'Reg ' + index + '-' + (index + 1),
                    value: float32,
                    registers: [high, low],
                });
            }

            return decoded;
        }

        function renderDecodedValues(registers) {
            const decoded = decodeRegisterPairs(registers)
                .filter((item) => Math.abs(item.value) >= 0.000001 || item.registers.some((value) => value !== 0));

            if (!decoded.length) {
                return '';
            }

            return '<div class="small text-muted mt-1">' + decoded.slice(0, 6).map((item) => (
                '<span class="me-2">' + escapeHtml(item.label) + ': <strong>' + escapeHtml(item.value.toFixed(2)) + '</strong></span>'
            )).join('') + '</div>';
        }

        function renderLiveParameterValues(values) {
            if (!Array.isArray(values) || !values.length) {
                return '';
            }

            return '<div class="small text-muted mt-1">' + values.slice(0, 8).map((item) => (
                '<span class="me-2">' + escapeHtml(item.label || item.parameter || '-') + ': <strong>' + escapeHtml(item.value_text || item.value || '-') + '</strong></span>'
            )).join('') + '</div>';
        }

        function updateRednodeSensorCount() {
            const countEl = el('rednode-sensor-count');
            if (!countEl) {
                return;
            }

            const count = selectedRednodeSensorIds().length;
            countEl.textContent = count + ' sensor';

            const selectAllButton = el('rednode-select-all');
            if (selectAllButton) {
                const allChecked = rednodeSensorChecks.length > 0
                    && rednodeSensorChecks.every((input) => input.checked || input.disabled);
                selectAllButton.textContent = allChecked ? 'Clear' : 'Select All';
            }
        }

        function currentRednodeLoggerCode() {
            const formData = new FormData(rednodeSerialForm);
            return String(formData.get('logger_code') || '').trim();
        }

        function currentRednodeStatusUrl() {
            const url = new URL(rednodeStatusBaseUrl, window.location.origin);
            url.searchParams.set('logger_code', currentRednodeLoggerCode());
            return url.toString();
        }

        function updateRednodeConfigUrl() {
            const configInput = el('rednode-config-url');

            if (!configInput) {
                return;
            }

            const url = new URL(rednodeConfigBaseUrl);
            url.searchParams.set('logger_code', currentRednodeLoggerCode());
            configInput.value = url.toString();
        }

        function filterRednodeSensorsForLogger() {
            if (!rednodeDataLogger) {
                return;
            }

            const selectedOption = rednodeDataLogger.options[rednodeDataLogger.selectedIndex];
            const monitoringStationId = selectedOption?.dataset.monitoringStationId || '';
            const loggerCode = selectedOption?.dataset.loggerCode || '';
            const loggerInput = rednodeSerialForm.querySelector('[name="logger_code"]');

            if (loggerInput && loggerCode) {
                loggerInput.value = loggerCode;
            }

            updateRednodeConfigUrl();

            rednodeSensorOptions.forEach((option) => {
                const sameStation = !monitoringStationId || option.dataset.monitoringStationId === monitoringStationId;
                option.classList.toggle('d-none', !sameStation);

                const input = option.querySelector('[data-rednode-sensor-check]');
                if (input) {
                    input.disabled = !sameStation;
                    if (!sameStation) {
                        input.checked = false;
                    }
                }
            });

            updateRednodeSensorCount();
        }

        function renderMqttLog(log) {
            const rows = Array.isArray(log) ? log : [];
            el('mqtt-log-count').textContent = rows.length + (rows.length === 1 ? ' message' : ' messages');

            if (!rows.length) {
                mqttLogRowsEl.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Belum ada message MQTT.</td></tr>';
                return;
            }

            mqttLogRowsEl.innerHTML = rows.map((item) => {
                const evaluation = item.evaluation || {};
                const danger = evaluation.thresholdExceeded === true;
                const status = danger ? 'Awas' : (evaluation.thresholdExceeded === false ? 'Normal' : '-');
                const badge = danger ? 'bg-danger' : (status === 'Normal' ? 'bg-success' : 'bg-secondary');
                const receivedAt = item.receivedAt ? new Date(item.receivedAt).toLocaleTimeString() : '-';

                return '<tr>' +
                    '<td>' + escapeHtml(receivedAt) + '</td>' +
                    '<td class="modbus-accent">' + escapeHtml(item.topic) + '</td>' +
                    '<td><div class="mqtt-log-payload">' + escapeHtml(item.payload) + '</div></td>' +
                    '<td><span class="badge ' + badge + '">' + escapeHtml(status) + '</span></td>' +
                '</tr>';
            }).join('');
        }

        function renderRednodeRegisters(payload) {
            const activeCodes = selectedRednodeSensorCodes();
            const sensors = (Array.isArray(payload?.sensors) ? payload.sensors : [])
                .filter((sensor) => !activeCodes.size || activeCodes.has(String(sensor.sensor_code || '')));
            if (!sensors.length) {
                if (activeCodes.size) {
                    state.rows = [];
                    renderRows();
                }
                return;
            }

            const selectedCode = state.sensor?.code || state.sensor?.sensor_code || null;
            const reading = sensors.find((item) => item.sensor_code === selectedCode)
                || sensors.find((item) => Array.isArray(item.rows) && item.rows.length)
                || sensors[0];
            const rows = Array.isArray(reading?.rows) ? reading.rows : [];

            if (!reading || !rows.length) {
                state.rows = [];
                if (reading?.sensor_code) {
                    el('modbus-sensor-label').textContent = [reading.sensor_code, reading.sensor_label || reading.parameter || reading.sensor_type].filter(Boolean).join(' - ');
                    el('modbus-last-update').textContent = reading.received_at ? new Date(reading.received_at).toLocaleTimeString() : new Date().toLocaleTimeString();
                }
                renderRows();
                return;
            }

            const functionCode = reading.function_code || 'FC03';
            const address = Number(reading.address || rows[0]?.address || 0);
            const quantity = Number(reading.quantity || rows.length || 1);

            state.rows = rows.map((row) => ({
                address: Number(row.address || 0),
                raw: row.raw,
                uint16: row.uint16 ?? row.raw,
                int16: row.int16 ?? row.raw,
                hex: row.hex || '0x0000',
                binary: row.binary || '00000000 00000000',
                float32: row.float32 ?? null,
            }));

            el('modbus-function').value = functionCode;
            el('modbus-address').value = address;
            el('modbus-quantity').value = quantity;
            el('modbus-fc-label').textContent = functionCode;
            el('modbus-address-label').textContent = address + ' - ' + (address + quantity - 1);
            el('modbus-sensor-label').textContent = [reading.sensor_code || '-', reading.sensor_label || reading.parameter || reading.sensor_type].filter(Boolean).join(' - ');
            el('modbus-last-update').textContent = reading.received_at ? new Date(reading.received_at).toLocaleTimeString() : new Date().toLocaleTimeString();
            renderRows();
        }

        async function refreshRednodeStatus() {
            const response = await fetch(currentRednodeStatusUrl(), { headers: { Accept: 'application/json' } });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.ok === false) {
                throw new Error(data.message || 'RedNode status gagal dibaca.');
            }

            const statusEl = el('rednode-live-status');
            const online = Boolean(data.online);
            if (lastRednodeOnline !== online) {
                appendIntegrationLog('RedNode', online ? 'Kabar koneksi diterima dari RedNode.' : 'Kabar koneksi RedNode belum diterima atau sudah terlalu lama.', online ? 'Online' : 'Offline');
                lastRednodeOnline = online;
            }
            statusEl.textContent = online ? 'Online' : 'Offline';
            statusEl.classList.toggle('offline', !online);
            el('rednode-live-port').textContent = [data.serial_port || '-', data.pin_mapping || ''].filter(Boolean).join(' | ');
            el('rednode-live-seen').textContent = data.last_seen_at ? new Date(data.last_seen_at).toLocaleString() : '-';

            const errorBox = el('rednode-live-error');
            errorBox.textContent = data.last_error || '';
            errorBox.classList.toggle('d-none', !data.last_error);

            const activeCodes = selectedRednodeSensorCodes();
            const payloadSensors = (Array.isArray(data.last_payload?.sensors) ? data.last_payload.sensors : [])
                .filter((sensor) => !activeCodes.size || activeCodes.has(String(sensor.sensor_code || '')));
            const readings = Array.isArray(data.latest_readings) ? data.latest_readings : [];
            const liveRows = payloadSensors.map((sensor) => ({
                sensor_code: sensor.sensor_code,
                sensor_label: sensor.sensor_label,
                sensor_type: sensor.sensor_type,
                parameter: sensor.parameter,
                value: sensor.error ? sensor.error : (sensor.value ?? '-'),
                status: sensor.error ? 'Timeout' : (sensor.threshold_exceeded ? 'Awas' : 'Normal'),
                error: sensor.error || null,
                registers: sensor.registers || [],
                parameter_values: sensor.parameter_values || [],
                fresh: true,
                received_at: sensor.received_at || data.last_payload?.reported_at || data.last_seen_at,
            }));
            const liveCodes = new Set(liveRows.map((row) => row.sensor_code));
            readings.forEach((reading) => {
                const code = String(reading.sensor_code || '');
                if ((!activeCodes.size || activeCodes.has(code)) && !liveCodes.has(reading.sensor_code)) {
                    liveRows.push(reading);
                }
            });

            el('rednode-live-readings').innerHTML = liveRows.length
                ? liveRows.slice(0, 8).map((reading) => {
                    const statusText = reading.status || '-';
                    const statusLower = String(statusText).toLowerCase();
                    const stale = reading.fresh === false || statusLower.includes('data lama');
                    const danger = Boolean(reading.error) || statusLower.includes('timeout') || statusLower.includes('gagal') || statusLower.includes('awas');
                    const badge = stale ? 'bg-secondary' : (danger ? 'bg-danger' : 'bg-success');
                    const valueClass = stale ? 'text-muted fw-bold' : (danger ? 'text-danger fw-bold' : 'modbus-value');
                    const receivedAt = reading.received_at ? new Date(reading.received_at).toLocaleTimeString() : '-';
                    const label = reading.sensor_label || reading.parameter || reading.sensor_type || receivedAt;

                    return '<tr>' +
                        '<td><div class="fw-bold">' + escapeHtml(reading.sensor_code || '-') + '</div><small class="text-muted">' + escapeHtml(label) + '</small><br><small class="text-muted">' + escapeHtml(receivedAt) + '</small></td>' +
                        '<td class="text-end"><span class="' + valueClass + '">' + escapeHtml(reading.value ?? '-') + '</span><br><span class="badge ' + badge + '">' + escapeHtml(statusText) + '</span>' + renderLiveParameterValues(reading.parameter_values) + renderDecodedValues(reading.registers) + '</td>' +
                    '</tr>';
                }).join('')
                : '<tr><td class="text-muted">Belum ada nilai sensor.</td></tr>';

            renderRednodeRegisters(data.last_payload);
        }

        async function submitRednodeConfig(showSuccess = true) {
            const submitButton = rednodeSerialForm.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';

            try {
                const response = await fetch(rednodeSerialForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new FormData(rednodeSerialForm),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                    throw new Error(errors || data.message || 'Konfigurasi RedNode gagal disimpan.');
                }

                const rednode = data.rednode || {};
                const portLabel = [rednode.serial_port || rednodeSerialPort.value, rednode.pin_mapping || rednodePinMapping.value].filter(Boolean).join(' | ');
                const selectedCount = Array.isArray(rednode.monitored_sensor_ids)
                    ? rednode.monitored_sensor_ids.length
                    : selectedRednodeSensorIds().length;
                const names = selectedRednodeSensorNames();
                const target = names.length ? names.join(', ') : selectedCount + ' sensor';
                const message = 'Berhasil mengganti sensor RedNode ke ' + target + '. RedNode akan mengambil config terbaru otomatis.';
                el('rednode-live-port').textContent = portLabel || '-';
                if (showSuccess) {
                    appendIntegrationLog('RedNode', message, 'Sukses');
                    showMessage(message, 'success');
                }
                refreshRednodeStatus().catch(() => {});
                return data;
            } catch (error) {
                appendIntegrationLog('RedNode', error.message, 'Gagal');
                showMessage(error.message, 'warning');
                throw error;
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        }

        async function saveRednodeSerial(event) {
            event.preventDefault();
            const submitButton = rednodeSerialForm.querySelector('button[type="submit"]');

            await submitRednodeConfig(true);
            showMessage('Config tersimpan. Menjalankan gateway RedNode di logger...', 'info');
            await controlRednode('start', { apply: false, button: submitButton });
        }

        async function controlRednode(action, options = {}) {
            const button = options.button || el(action === 'start' ? 'rednode-start' : 'rednode-stop');
            const originalText = button?.textContent || '';

            if (button) {
                button.disabled = true;
                button.textContent = action === 'start' ? 'Starting...' : 'Stopping...';
            }

            try {
                if (options.apply !== false) {
                    await submitRednodeConfig(false);
                }
                const formData = new FormData(rednodeSerialForm);
                const response = await fetch(rednodeControlUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        action: action,
                        logger_code: formData.get('logger_code'),
                    }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                    throw new Error(errors || data.message || 'Perintah RedNode gagal.');
                }

                const remoteLog = Array.isArray(data.terminal_log) && data.terminal_log.length
                    ? data.terminal_log.join(' | ')
                    : (data.output || '');
                const output = remoteLog ? ' Remote: ' + remoteLog : '';
                const statusEl = el('rednode-live-status');
                const online = action === 'start';
                statusEl.textContent = online ? 'Online' : 'Offline';
                statusEl.classList.toggle('offline', !online);
                lastRednodeOnline = online;
                if (data.last_seen_at) {
                    el('rednode-live-seen').textContent = new Date(data.last_seen_at).toLocaleString();
                }
                appendIntegrationLog('RedNode', data.message + output, 'Sukses');
                showMessage(data.message + output, 'success');
                setTimeout(() => refreshRednodeStatus().catch(() => {}), 1500);
            } catch (error) {
                appendIntegrationLog('RedNode', error.message, 'Gagal');
                showMessage(error.message, 'warning');
            } finally {
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }
        }

        function displayValue(row) {
            if (state.mode === 'hex') {
                return row.hex;
            }
            if (state.mode === 'bin') {
                return row.binary;
            }
            if (state.mode === 'float') {
                return row.float32 === null || row.float32 === undefined
                    ? Number(row.uint16 || 0).toFixed(4)
                    : Number(row.float32).toFixed(4);
            }
            return row.raw === true || row.raw === false ? String(row.raw) : String(row.uint16 ?? row.raw);
        }

        function thresholdNumber(sensor) {
            const source = String(sensor?.threshold || sensor?.rule || '').replace(',', '.');
            const match = source.match(/-?\d+(\.\d+)?/);
            return match ? Number(match[0]) : null;
        }

        function sensorValue(row, sensor) {
            if (!row) {
                return null;
            }

            const dataType = String(sensor?.data_type || '').toLowerCase();
            let rawValue = row.uint16 ?? row.raw;

            if (dataType.includes('float') && row.float32 !== null && row.float32 !== undefined) {
                rawValue = row.float32;
            } else if (dataType.includes('int16')) {
                rawValue = row.int16;
            } else if (dataType.includes('bool')) {
                rawValue = row.raw ? 1 : 0;
            }

            const numeric = Number(rawValue);
            if (!Number.isFinite(numeric)) {
                return null;
            }

            const scale = Number(sensor?.scale_factor ?? 1);
            const offset = Number(sensor?.offset ?? 0);

            return (numeric * (Number.isFinite(scale) ? scale : 1)) + (Number.isFinite(offset) ? offset : 0);
        }

        function sensorUnit(sensor) {
            const unit = String(sensor?.unit || '').trim();
            return unit && unit !== '0' ? ' ' + unit : '';
        }

        function alertClass(alertLevel) {
            const level = String(alertLevel || '').toLowerCase();
            if (level.includes('awas') || level.includes('danger')) {
                return 'danger';
            }
            if (level.includes('siaga') || level.includes('waspada') || level.includes('warning')) {
                return 'warning';
            }
            return 'info';
        }

        function evaluateThreshold() {
            const sensor = state.sensor;
            const firstRow = state.rows[0];
            const threshold = thresholdNumber(sensor);
            const value = sensorValue(firstRow, sensor);

            if (!sensor || value === null) {
                showMessage('');
                return null;
            }

            const unit = sensorUnit(sensor);
            const valueText = value.toFixed(2) + unit;

            if (threshold === null) {
                showMessage('');
                return {
                    value: value,
                    valueText: valueText,
                    thresholdExceeded: null,
                };
            }

            if (value <= threshold) {
                showMessage('');
                return {
                    value: value,
                    valueText: valueText,
                    threshold: threshold,
                    thresholdExceeded: false,
                };
            }

            const level = 'Awas';
            const message = level + ': ' + sensor.code + ' melewati threshold. Nilai ' + value.toFixed(2) + unit + ' > ' + threshold + unit + '.';

            showMessage(message, alertClass(level));
            return {
                value: value,
                valueText: valueText,
                threshold: threshold,
                thresholdExceeded: true,
            };
        }

        function syncRealtimeSensorStatus(evaluation) {
            if (!state.sensor || !state.sensor.db_id || !evaluation) {
                return;
            }

            laravelRequest(realtimeStatusUrl, {
                sensor_id: state.sensor.db_id,
                value: evaluation.valueText,
                ...(evaluation.thresholdExceeded !== null && evaluation.thresholdExceeded !== undefined
                    ? { threshold_exceeded: evaluation.thresholdExceeded }
                    : {}),
            }).then((data) => {
                state.sensor.value = data.sensor.value;
                state.sensor.alert_level = data.sensor.alert_level;
                state.sensor.status = data.sensor.status;
            }).catch(() => {});
        }

        function renderRows() {
            const functionCode = el('modbus-function').value;
            if (!state.rows.length) {
                rowsEl.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">Belum ada data register.</td></tr>';
                el('modbus-summary').textContent = '0 register';
                return;
            }

            rowsEl.innerHTML = state.rows.map((row, index) => (
                '<tr>' +
                    '<td>' + (index + 1) + '</td>' +
                    '<td class="modbus-accent">' + row.address + '</td>' +
                    '<td>' + aliasFor(functionCode, row.address) + '</td>' +
                    '<td class="modbus-value">' + displayValue(row) + '</td>' +
                    '<td class="modbus-hex">' + row.hex + '</td>' +
                    '<td>' + row.binary + '</td>' +
                '</tr>'
            )).join('');

            const values = state.rows
                .map((row) => Number(row.uint16 ?? row.raw))
                .filter((value) => Number.isFinite(value));
            const sum = values.reduce((total, value) => total + value, 0);
            const min = values.length ? Math.min(...values) : 0;
            const max = values.length ? Math.max(...values) : 0;
            el('modbus-summary').textContent = state.rows.length + ' register    Min: ' + min + '    Max: ' + max + '    Sum: ' + sum;
        }

        function updateHeader(payload) {
            const endAddress = Number(payload.address) + Number(payload.quantity) - 1;
            el('modbus-fc-label').textContent = payload.functionCode;
            el('modbus-address-label').textContent = payload.address + ' - ' + endAddress;
            el('modbus-sensor-label').textContent = state.sensor ? state.sensor.code : '-';
        }

        function applySensor(sensor) {
            state.sensor = sensor || null;
            if (!sensor) {
                el('modbus-sensor-label').textContent = '-';
                el('modbus-unit').value = 1;
                el('modbus-function').value = 'FC03';
                el('modbus-address').value = 0;
                el('modbus-quantity').value = 1;
                el('modbus-interval').value = 1000;
                el('modbus-write-address').value = 0;
                updateHeader(readPayload());
                return;
            }

            el('modbus-unit').value = sensor.slave_id || 1;
            el('modbus-function').value = sensor.function_code || 'FC03';
            el('modbus-address').value = sensor.address || 0;
            el('modbus-quantity').value = sensor.quantity || 1;
            el('modbus-interval').value = sensor.poll_interval_ms || 1000;
            el('modbus-write-address').value = sensor.address || 0;
            updateHeader(readPayload());
        }

        async function connect() {
            showMessage('');
            const data = state.protocol === 'mqtt'
                ? await request('/api/mqtt/connect', mqttPayload())
                : await request('/api/modbus/connect', connectionPayload());
            setConnected(true);
            setStats(data.stats);
        }

        async function disconnect() {
            await stopPolling();
            showMessage('');
            const data = state.protocol === 'mqtt'
                ? await request('/api/mqtt/disconnect', {})
                : await request('/api/modbus/disconnect', {});
            setConnected(false);
            setStats(data.stats);
        }

        async function readOnce() {
            const payload = readPayload();
            updateHeader(payload);
            showMessage('');
            const data = await request('/api/modbus/read', payload);
            state.rows = data.rows || [];
            setConnected(true);
            setStats(data.stats);
            renderRows();
            syncRealtimeSensorStatus(evaluateThreshold());
            appendIntegrationLog('Sensor', 'Read once berhasil untuk ' + (state.sensor ? state.sensor.code : 'sensor aktif') + '.', 'Sukses');
        }

        function setPolling(active) {
            state.polling = active;
            el('modbus-poll').textContent = active
                ? (state.protocol === 'mqtt' ? 'Stop MQTT' : 'Stop Poll')
                : (state.protocol === 'mqtt' ? 'Subscribe' : 'Start Poll');
            el('modbus-poll').classList.toggle('modbus-btn-stop', active);
            el('modbus-poll').classList.toggle('modbus-btn-run', !active);
        }

        async function refreshBackendStatus() {
            if (state.protocol === 'mqtt') {
                const data = await request('/api/mqtt/status');
                setPolling(Boolean(data.mqtt?.active));
                setConnected(Boolean(data.mqtt?.connected || data.mqtt?.active));
                setStats(data.stats);
                renderMqttLog(data.mqtt?.log || (data.mqtt?.lastMessage ? [data.mqtt.lastMessage] : []));
                return;
            }

            const data = await request('/api/poll/status');
            setPolling(Boolean(data.active));
            setConnected(Boolean(data.active || data.lastResult?.connection));
            setStats(data.stats);

            if (data.lastResult?.rows) {
                state.rows = data.lastResult.rows;
                renderRows();
            }
        }

        async function stopPolling() {
            state.polling = false;
            clearInterval(state.timer);
            state.timer = null;
            await request(state.protocol === 'mqtt' ? '/api/mqtt/disconnect' : '/api/poll/stop', {});
            setPolling(false);
        }

        async function startPolling() {
            if (state.polling) {
                await stopPolling();
                return;
            }

            showMessage('');
            const data = state.protocol === 'mqtt'
                ? await request('/api/mqtt/connect', mqttPayload())
                : await request('/api/poll/start', readPayload());
            setStats(data.stats);
            setConnected(true);
            setPolling(true);

            clearInterval(state.timer);
            state.timer = setInterval(() => {
                refreshBackendStatus().catch(() => {});
            }, 2000);
        }

        async function writeValue() {
            const writeFunction = el('modbus-write-function').value;
            const rawValue = el('modbus-write-value').value.trim();
            const payload = {
                ...connectionPayload(),
                functionCode: writeFunction,
                address: numberValue('modbus-write-address', 0),
            };

            if (writeFunction === 'FC16') {
                payload.values = rawValue.split(',').map((value) => Number(value.trim()));
            } else if (writeFunction === 'FC05') {
                payload.value = rawValue === '1' || rawValue.toLowerCase() === 'true';
            } else {
                payload.value = Number(rawValue);
            }

            showMessage('');
            const data = await request('/api/modbus/write', payload);
            setStats(data.stats);
            showMessage('Write berhasil dikirim ke device.', 'success');
        }

        async function testMqttBroker() {
            showMessage('');

            if (state.protocol !== 'mqtt') {
                setProtocol('mqtt');
            }

            if (!state.connected) {
                await connect();
            }

            const data = await request('/api/mqtt/test-publish', mqttTestPayload());
            setStats(data.stats);
            showMessage('Test MQTT terkirim ke ' + data.topic + ' dengan payload ' + data.payload + '.', 'success');
            appendIntegrationLog('MQTT', 'Test publish terkirim ke ' + data.topic + '.', 'Sukses');

            setTimeout(() => {
                refreshBackendStatus().catch(() => {});
            }, 1000);
        }

        function handleError(error) {
            setConnected(false);
            showMessage(error.message, 'warning');
            appendIntegrationLog(state.protocol === 'mqtt' ? 'MQTT' : 'Sensor', error.message, 'Gagal');
            if (state.polling) {
                stopPolling().catch(() => {});
            }
        }

        el('modbus-sensor').addEventListener('change', (event) => {
            applySensor(sensorsByIndex[event.target.value] || null);
            saveMqttConfig();
        });
        document.querySelectorAll('[data-protocol]').forEach((button) => {
            button.addEventListener('click', () => setProtocol(button.dataset.protocol));
        });
        el('modbus-connect').addEventListener('click', () => connect().catch(handleError));
        el('modbus-disconnect').addEventListener('click', () => disconnect().catch(handleError));
        el('modbus-read').addEventListener('click', () => readOnce().catch(handleError));
        el('modbus-poll').addEventListener('click', () => startPolling().catch(handleError));
        el('modbus-write').addEventListener('click', () => writeValue().catch(handleError));
        el('mqtt-test').addEventListener('click', () => testMqttBroker().catch(handleError));
        if (rednodeSerialForm) {
            rednodeSerialForm.addEventListener('submit', saveRednodeSerial);
        }
        if (rednodeDataLogger) {
            rednodeDataLogger.addEventListener('change', () => {
                filterRednodeSensorsForLogger();
                refreshRednodeStatus().catch(() => {});
            });
            filterRednodeSensorsForLogger();
        }
        if (el('rednode-start')) {
            el('rednode-start').addEventListener('click', () => controlRednode('start'));
        }
        if (el('rednode-stop')) {
            el('rednode-stop').addEventListener('click', () => controlRednode('stop'));
        }
        rednodeSensorChecks.forEach((input) => {
            input.addEventListener('change', updateRednodeSensorCount);
        });
        if (el('rednode-select-all')) {
            el('rednode-select-all').addEventListener('click', () => {
                const allChecked = rednodeSensorChecks.every((input) => input.checked || input.disabled);
                rednodeSensorChecks.forEach((input) => {
                    if (!input.disabled) {
                        input.checked = !allChecked;
                    }
                });
                updateRednodeSensorCount();
            });
        }
        el('modbus-clear').addEventListener('click', () => {
            state.rows = [];
            renderRows();
            showMessage('');
        });
        ['mqtt-broker', 'mqtt-topic', 'mqtt-username', 'mqtt-password', 'mqtt-test-value', 'modbus-api-base'].forEach((id) => {
            el(id).addEventListener('change', saveMqttConfig);
            el(id).addEventListener('blur', saveMqttConfig);
        });
        el('mqtt-save-config').addEventListener('change', saveMqttConfig);
        if (rednodeSerialPort && rednodePinMapping) {
            const syncRednodePinMapping = () => {
                rednodePinMapping.value = rednodeSerialPort.options[rednodeSerialPort.selectedIndex]?.dataset.pinMapping || '';
            };
            rednodeSerialPort.addEventListener('change', syncRednodePinMapping);
            syncRednodePinMapping();
        }

        document.querySelectorAll('[data-modbus-mode]').forEach((button) => {
            button.addEventListener('click', () => {
                state.mode = button.dataset.modbusMode;
                document.querySelectorAll('[data-modbus-mode]').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                renderRows();
            });
        });

        loadStoredMqttConfig();
        updateRednodeSensorCount();

        if (sensorConfigs.length) {
            const storedSensorIndex = localStorage.getItem(mqttConfigKeys.sensorIndex);
            const sensorIndex = sensorsByIndex[storedSensorIndex] ? storedSensorIndex : '0';
            el('modbus-sensor').value = sensorIndex;
            applySensor(sensorsByIndex[sensorIndex]);
        }

        setProtocol('mqtt');
        if (apiBaseInput.value.trim()) {
            request('/health')
                .then((data) => {
                    setConnected(Boolean(data.connected || data.mqtt?.active));
                    setStats(data.stats);
                    renderMqttLog(data.mqtt?.log || (data.mqtt?.lastMessage ? [data.mqtt.lastMessage] : []));
                    if (data.mqtt?.active) {
                        setProtocol('mqtt');
                        setPolling(true);
                        state.timer = setInterval(() => {
                            refreshBackendStatus().catch(() => {});
                        }, 2000);
                    } else if (data.pollJob?.active) {
                        setProtocol('modbus');
                        setPolling(true);
                        state.timer = setInterval(() => {
                            refreshBackendStatus().catch(() => {});
                        }, 2000);
                    }
                })
                .catch(() => setConnected(false));
        } else {
            setConnected(false);
        }

        refreshRednodeStatus().catch(() => {});
        setInterval(() => {
            refreshRednodeStatus().catch(() => {});
        }, 1000);
    })();
</script>
@endsection
