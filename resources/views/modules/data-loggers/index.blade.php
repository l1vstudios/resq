@extends('layouts.master')

@section('title') Data Loggers @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Device Setup @endslot
@slot('title') Data Loggers @endslot
@endcomponent

@php
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
    $monitoringStations = collect($monitoringStations ?? []);
@endphp

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Data Logger Setup</h4>
                <form method="POST" action="{{ route('data-loggers.store') }}">
                    @csrf
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
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Data Logger List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Logger ID</th>
                                <th>Monitoring Station</th>
                                <th>Serial Number</th>
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
                                    <td>{{ $logger['logger_model'] ?? '-' }}</td>
                                    <td>{{ $logger['vendor'] ?? '-' }}</td>
                                    <td>{{ $logger['firmware_version'] ?? '-' }}</td>
                                    <td><span class="badge {{ ($logger['logger_status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary' }}">{{ $logger['logger_status'] ?? '-' }}</span></td>
                                    <td class="text-end">
                                        @isset($logger['db_id'])
                                            <form method="POST" action="{{ route('device-setup.destroy', ['type' => 'data-logger', 'id' => $logger['db_id']]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        @endisset
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada data logger.</td>
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
