@extends('layouts.master')

@section('title') Warning Stations @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Registered @endslot
@slot('title') Warning Stations @endslot
@endcomponent

@php
    $warningStations = collect($warningStations ?? config('resq_dummy.warning_stations'));
    $wsControllers = collect($wsControllers ?? config('resq_dummy.ws_controllers'))->keyBy('warning_station_id');
    $totalWarnings = $warningStations->count();
    $publicWarningEnabled = $warningStations->where('public_warning_enabled', true)->count();
    $standbyControllers = $warningStations->filter(fn ($station) => ($wsControllers[$station['id'] ?? '']['controller_status'] ?? $station['controller_status'] ?? '') === 'Standby')->count();
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Warning Station</p>
                <h4 class="mb-0">{{ $totalWarnings }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Public Warning Enabled</p>
                <h4 class="mb-0">{{ $publicWarningEnabled }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Controller Standby</p>
                <h4 class="mb-0">{{ $standbyControllers }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Warning Station Registry</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Warning ID</th>
                                <th>Workspace</th>
                                <th>Source Monitoring</th>
                                <th>Station Name</th>
                                <th>Zone</th>
                                <th>Coordinate</th>
                                <th>Controller</th>
                                <th>Output Devices</th>
                                <th>Public Warning</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($warningStations as $station)
                                @php
                                    $controller = $wsControllers[$station['id'] ?? ''] ?? [
                                        'id' => $station['controller_id'] ?? '-',
                                        'controller_model' => $station['controller_model'] ?? '-',
                                        'vendor' => $station['controller_vendor'] ?? '-',
                                        'controller_status' => $station['controller_status'] ?? '-',
                                    ];
                                    $outputDevices = collect($station['output_devices'] ?? [])->filter()->implode(', ');
                                @endphp
                                <tr>
                                    <td>{{ $station['id'] ?? '-' }}</td>
                                    <td>{{ $station['cluster_id'] ?? '-' }}</td>
                                    <td>{{ $station['source_monitoring_station_id'] ?? '-' }}</td>
                                    <td>{{ $station['name'] ?? '-' }}</td>
                                    <td>{{ $station['zone_id'] ?? '-' }}</td>
                                    <td>{{ $station['coordinate'] ?? '-' }}</td>
                                    <td>
                                        <div>{{ $controller['id'] ?? '-' }}</div>
                                        <small class="text-muted">{{ $controller['controller_model'] ?? '-' }} / {{ $controller['controller_status'] ?? '-' }}</small>
                                    </td>
                                    <td>{{ $outputDevices ?: '-' }}</td>
                                    <td>
                                        <span class="badge {{ ($station['public_warning_enabled'] ?? false) ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ($station['public_warning_enabled'] ?? false) ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ in_array($station['status'] ?? '', ['Danger', 'Bahaya', 'Awas']) ? 'bg-danger' : (($station['status'] ?? '') === 'Waspada' ? 'bg-warning' : 'bg-success') }}">
                                            {{ $station['status'] ?? '-' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">Belum ada warning station.</td>
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
