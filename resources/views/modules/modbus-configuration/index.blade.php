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
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Device Setup @endslot
@slot('title') Modbus Configuration @endslot
@endcomponent

@php
    $sensors = collect($sensors ?? []);
    $sensorConfigs = $sensors->map(fn ($sensor) => [
        'db_id' => $sensor['db_id'] ?? $sensor['id'] ?? null,
        'code' => $sensor['id'] ?? '-',
        'label' => trim(($sensor['id'] ?? '-') . ' - ' . ($sensor['parameter'] ?? $sensor['type'] ?? 'Sensor')),
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
            <div class="col-xl-3 p-3 border-end">
                <div class="modbus-panel p-3 mb-3">
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

                <div class="modbus-panel p-3 mb-3">
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

            <div class="col-xl-9 p-3">
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
        const messageEl = el('modbus-message');
        const apiBaseInput = el('modbus-api-base');
        const csrfToken = @json(csrf_token());
        const gatewayStatusUrl = window.location.origin + '/api/realtime-sensor-status';
        const realtimeStatusUrl = gatewayStatusUrl;
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

            setTimeout(() => {
                refreshBackendStatus().catch(() => {});
            }, 1000);
        }

        function handleError(error) {
            setConnected(false);
            showMessage(error.message, 'warning');
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

        document.querySelectorAll('[data-modbus-mode]').forEach((button) => {
            button.addEventListener('click', () => {
                state.mode = button.dataset.modbusMode;
                document.querySelectorAll('[data-modbus-mode]').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');
                renderRows();
            });
        });

        loadStoredMqttConfig();

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
    })();
</script>
@endsection
