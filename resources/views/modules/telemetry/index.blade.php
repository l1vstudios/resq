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
                <form method="POST" action="{{ route('telemetry.store') }}">
                    @csrf
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
                        <tbody>
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
                                <th>Logger</th>
                                <th>Station</th>
                                <th>Sensor</th>
                                <th>Value</th>
                                <th>Alert</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($telemetryReadings as $reading)
                                <tr>
                                    <td>{{ $reading['received_at'] ?? '-' }}</td>
                                    <td>{{ $reading['data_logger_id'] ?? '-' }}</td>
                                    <td>{{ $reading['monitoring_station_id'] ?? '-' }}</td>
                                    <td>{{ $reading['sensor_id'] ?? '-' }}</td>
                                    <td>{{ $reading['value'] ?? '-' }}</td>
                                    <td><span class="badge {{ $alertClass($reading['alert_level'] ?? 'Normal') }}">{{ $reading['alert_level'] ?? 'Normal' }}</span></td>
                                    <td><span class="badge {{ $alertClass($reading['status'] ?? 'Normal') }}">{{ $reading['status'] ?? 'Normal' }}</span></td>
                                    <td class="text-end">
                                        @isset($reading['db_id'])
                                            <form method="POST" action="{{ route('device-setup.destroy', ['type' => 'telemetry', 'id' => $reading['db_id']]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        @endisset
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada telemetry history.</td>
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
