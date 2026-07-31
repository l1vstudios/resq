@extends('layouts.master')

@section('title') Telemetry Configuration @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Telemetry @endslot
@slot('title') Telemetry Configuration @endslot
@endcomponent

@php
    $sensors = collect($sensors ?? config('resq_dummy.sensors'));
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
    $telemetryReadings = collect($telemetryReadings ?? []);
    $alertClass = fn ($level) => in_array($level, ['Awas', 'Danger']) ? 'bg-danger' : ($level === 'Siaga' ? 'bg-warning' : ($level === 'Waspada' ? 'bg-info' : 'bg-success'));
@endphp

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Telemetry Reading</h4>
                <form method="POST" action="{{ route('telemetry.store') }}" id="telemetry-form">
                    @csrf
                    <input type="hidden" name="telemetry_id">
                    <div class="mb-3">
                        <label class="form-label">Sensor</label>
                        <select name="sensor_id" class="form-select" required @disabled($sensors->whereNotNull('db_id')->isEmpty())>
                            @foreach ($sensors->whereNotNull('db_id') as $sensor)
                                <option value="{{ $sensor['db_id'] }}">{{ $sensor['id'] }} - {{ $sensor['parameter'] ?? $sensor['type'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data Logger</label>
                        <select name="data_logger_id" class="form-select">
                            <option value="">-</option>
                            @foreach ($dataLoggers->whereNotNull('db_id') as $logger)
                                <option value="{{ $logger['db_id'] }}">{{ $logger['id'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <input name="value" class="form-control" placeholder="2.8 m">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Alert Level</label>
                            <select name="alert_level" class="form-select" required>
                                <option>Normal</option>
                                <option>Waspada</option>
                                <option>Siaga</option>
                                <option>Awas</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option>Normal</option>
                                <option>Waspada</option>
                                <option>Siaga</option>
                                <option>Awas</option>
                                <option>Danger</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Received At</label>
                        <input type="datetime-local" name="received_at" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary" @disabled($sensors->whereNotNull('db_id')->isEmpty())>
                        Save Telemetry
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Latest Sensor State</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Station</th>
                                <th>Sensor</th>
                                <th>Value</th>
                                <th>Threshold</th>
                                <th>Alert</th>
                                <th>Status</th>
                                <th>Last Seen</th>
                            </tr>
                        </thead>
                        <tbody id="latest-sensor-state-rows">
                            @forelse ($sensors as $sensor)
                                <tr>
                                    <td>{{ $sensor['monitoring_station_id'] ?? '-' }}</td>
                                    <td>{{ $sensor['id'] ?? '-' }}</td>
                                    <td>{{ $sensor['value'] ?? '-' }}</td>
                                    <td>{{ $sensor['threshold'] ?? '-' }}</td>
                                    <td><span class="badge {{ $alertClass($sensor['alert_level'] ?? 'Normal') }}">{{ $sensor['alert_level'] ?? 'Normal' }}</span></td>
                                    <td><span class="badge {{ $alertClass($sensor['status'] ?? 'Normal') }}">{{ $sensor['status'] ?? 'Normal' }}</span></td>
                                    <td>{{ $sensor['last_seen'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada sensor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Telemetry History</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Received</th>
                                <th>Source</th>
                                <th>Logger</th>
                                <th>Station</th>
                                <th>Sensor</th>
                                <th>Parameter</th>
                                <th>Value</th>
                                <th>Quality / Origin</th>
                                <th>Alert</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="telemetry-history-rows">
                            @forelse ($telemetryReadings as $reading)
                                @php
                                    $displayValue = match ($reading['data_type'] ?? null) {
                                        'boolean' => ($reading['value_boolean'] ?? false) ? 'true' : 'false',
                                        'text' => $reading['value_text'] ?? '-',
                                        'decimal' => $reading['value_decimal'] ?? '-',
                                        default => $reading['value'] ?? '-',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $reading['received_at'] ?? '-' }}</td>
                                    <td><span class="badge {{ ($reading['reading_source'] ?? 'legacy') === 'canonical' ? 'bg-primary' : 'bg-secondary' }}">{{ $reading['reading_source'] ?? 'legacy' }}</span></td>
                                    <td>{{ $reading['data_logger_id'] ?? '-' }}</td>
                                    <td>{{ $reading['monitoring_station_id'] ?? '-' }}</td>
                                    <td>{{ $reading['sensor_id'] ?? '-' }}</td>
                                    <td>{{ $reading['parameter_name'] ?? $reading['parameter_key'] ?? '-' }}@isset($reading['canonical_value_id'])<div class="text-muted"><small>value #{{ $reading['canonical_value_id'] }} · raw #{{ $reading['raw_ingestion_event_id'] }}</small></div>@endisset</td>
                                    <td>{{ $displayValue }} @if (! empty($reading['unit_symbol']))<span class="text-muted">{{ $reading['unit_symbol'] }}</span>@endif</td>
                                    <td>{{ $reading['quality'] ?? '-' }}<div class="text-muted">{{ $reading['origin'] ?? '-' }}</div></td>
                                    <td><span class="badge {{ $alertClass($reading['alert_level'] ?? 'Normal') }}">{{ $reading['alert_level'] ?? 'Normal' }}</span></td>
                                    <td><span class="badge {{ $alertClass($reading['status'] ?? 'Normal') }}">{{ $reading['status'] ?? 'Normal' }}</span></td>
                                    <td class="text-end">
                                        @if (($reading['reading_source'] ?? 'legacy') === 'legacy' && isset($reading['db_id']))
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    data-edit-form="#telemetry-form"
                                                    data-edit-fields="{{ base64_encode(json_encode([
                                                        'telemetry_id' => $reading['db_id'] ?? '',
                                                        'sensor_id' => $reading['sensor_db_id'] ?? '',
                                                        'data_logger_id' => $reading['data_logger_db_id'] ?? '',
                                                        'value' => $reading['value'] ?? '',
                                                        'alert_level' => $reading['alert_level'] ?? 'Normal',
                                                        'status' => $reading['status'] ?? 'Normal',
                                                        'received_at' => $reading['received_at_input'] ?? '',
                                                    ])) }}">Edit</button>
                                                <form method="POST" action="{{ route('device-setup.destroy', ['type' => 'telemetry', 'id' => $reading['db_id']]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted">Belum ada telemetry history.</td>
                                </tr>
                            @endforelse
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
    document.addEventListener('DOMContentLoaded', function () {
        var liveDataUrl = @json(route('telemetry.live-data'));
        var telemetryDestroyBaseUrl = @json(url('/device-setup/telemetry'));
        var csrfToken = @json(csrf_token());
        var latestRows = document.getElementById('latest-sensor-state-rows');
        var historyRows = document.getElementById('telemetry-history-rows');
        var refreshInFlight = false;

        function escapeHtml(value) {
            return String(value ?? '-')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function alertClass(level) {
            if (['Awas', 'Danger'].includes(level)) {
                return 'bg-danger';
            }
            if (level === 'Siaga') {
                return 'bg-warning';
            }
            if (level === 'Waspada') {
                return 'bg-info';
            }
            return 'bg-success';
        }

        function editPayload(fields) {
            return btoa(JSON.stringify(fields));
        }

        function readingValue(reading) {
            if (reading.data_type === 'boolean') {
                return reading.value_boolean === true ? 'true' : 'false';
            }
            if (reading.data_type === 'text') {
                return reading.value_text ?? '-';
            }
            if (reading.data_type === 'decimal') {
                return reading.value_decimal ?? '-';
            }
            return reading.value ?? '-';
        }

        function renderLatestSensors(sensors) {
            if (!Array.isArray(sensors) || sensors.length === 0) {
                latestRows.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Belum ada sensor.</td></tr>';
                return;
            }

            latestRows.innerHTML = sensors.map(function (sensor) {
                var alertLevel = sensor.alert_level || 'Normal';
                var status = sensor.status || 'Normal';

                return '<tr>' +
                    '<td>' + escapeHtml(sensor.monitoring_station_id) + '</td>' +
                    '<td>' + escapeHtml(sensor.id) + '</td>' +
                    '<td>' + escapeHtml(sensor.value) + '</td>' +
                    '<td>' + escapeHtml(sensor.threshold) + '</td>' +
                    '<td><span class="badge ' + alertClass(alertLevel) + '">' + escapeHtml(alertLevel) + '</span></td>' +
                    '<td><span class="badge ' + alertClass(status) + '">' + escapeHtml(status) + '</span></td>' +
                    '<td>' + escapeHtml(sensor.last_seen) + '</td>' +
                '</tr>';
            }).join('');
        }

        function renderTelemetryHistory(readings) {
            if (!Array.isArray(readings) || readings.length === 0) {
                historyRows.innerHTML = '<tr><td colspan="11" class="text-center text-muted">Belum ada telemetry history.</td></tr>';
                return;
            }

            historyRows.innerHTML = readings.map(function (reading) {
                var alertLevel = reading.alert_level || 'Normal';
                var status = reading.status || 'Normal';
                var actions = '';

                if (reading.reading_source === 'legacy' && reading.db_id) {
                    actions = '<div class="d-inline-flex gap-1">' +
                        '<button type="button" class="btn btn-outline-primary btn-sm" data-edit-form="#telemetry-form" data-edit-fields="' + editPayload({
                            telemetry_id: reading.db_id || '',
                            sensor_id: reading.sensor_db_id || '',
                            data_logger_id: reading.data_logger_db_id || '',
                            value: reading.value ?? '',
                            alert_level: reading.alert_level || 'Normal',
                            status: reading.status || 'Normal',
                            received_at: reading.received_at_input || '',
                        }) + '">Edit</button>' +
                        '<form method="POST" action="' + telemetryDestroyBaseUrl + '/' + encodeURIComponent(reading.db_id) + '">' +
                            '<input type="hidden" name="_token" value="' + escapeHtml(csrfToken) + '">' +
                            '<input type="hidden" name="_method" value="DELETE">' +
                            '<button class="btn btn-outline-danger btn-sm">Delete</button>' +
                        '</form>' +
                    '</div>';
                }

                return '<tr>' +
                    '<td>' + escapeHtml(reading.received_at) + '</td>' +
                    '<td><span class="badge ' + (reading.reading_source === 'canonical' ? 'bg-primary' : 'bg-secondary') + '">' + escapeHtml(reading.reading_source || 'legacy') + '</span></td>' +
                    '<td>' + escapeHtml(reading.data_logger_id) + '</td>' +
                    '<td>' + escapeHtml(reading.monitoring_station_id) + '</td>' +
                    '<td>' + escapeHtml(reading.sensor_id) + '</td>' +
                    '<td>' + escapeHtml(reading.parameter_name || reading.parameter_key || '-') + (reading.canonical_value_id ? '<div class="text-muted"><small>value #' + escapeHtml(reading.canonical_value_id) + ' · raw #' + escapeHtml(reading.raw_ingestion_event_id) + '</small></div>' : '') + '</td>' +
                    '<td>' + escapeHtml(readingValue(reading)) + (reading.unit_symbol ? ' <span class="text-muted">' + escapeHtml(reading.unit_symbol) + '</span>' : '') + '</td>' +
                    '<td>' + escapeHtml(reading.quality) + '<div class="text-muted">' + escapeHtml(reading.origin) + '</div></td>' +
                    '<td><span class="badge ' + alertClass(alertLevel) + '">' + escapeHtml(alertLevel) + '</span></td>' +
                    '<td><span class="badge ' + alertClass(status) + '">' + escapeHtml(status) + '</span></td>' +
                    '<td class="text-end">' + actions + '</td>' +
                '</tr>';
            }).join('');
        }

        function refreshTelemetry() {
            if (refreshInFlight) {
                return;
            }

            refreshInFlight = true;
            fetch(liveDataUrl, {
                headers: {
                    'Accept': 'application/json',
                },
            })
                .then(function (response) {
                    return response.ok ? response.json() : null;
                })
                .then(function (data) {
                    if (!data) {
                        return;
                    }

                    renderLatestSensors(data.sensors || []);
                    renderTelemetryHistory(data.telemetryReadings || []);
                })
                .catch(function () {})
                .finally(function () {
                    refreshInFlight = false;
                });
        }

        refreshTelemetry();
        setInterval(refreshTelemetry, 1000);
    });
</script>
@endsection
