@extends('layouts.master')

@section('title') Monitoring Stations @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Registered @endslot
@slot('title') Monitoring Stations @endslot
@endcomponent

@php
    $monitoringStations = collect($monitoringStations ?? config('resq_dummy.monitoring_stations'));
    $sensorsByStation = collect($sensors ?? config('resq_dummy.sensors'))->groupBy('monitoring_station_id');
    $totalStations = $monitoringStations->count();
    $onlineStations = $monitoringStations->where('connectivity_status', 'Online')->count();
    $activeLoggers = $monitoringStations->where('logger_status', 'Active')->count();
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Monitoring Station</p>
                <h4 class="mb-0">{{ $totalStations }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Logger Active</p>
                <h4 class="mb-0">{{ $activeLoggers }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Connectivity Online</p>
                <h4 class="mb-0">{{ $onlineStations }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Monitoring Station Registry</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Station ID</th>
                                <th>Workspace</th>
                                <th>Station Name</th>
                                <th>Coordinate</th>
                                <th>Data Logger</th>
                                <th>Logger Status</th>
                                <th>Connectivity</th>
                                <th>Warning Station</th>
                                <th>Sensors</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($monitoringStations as $station)
                                <tr>
                                    <td>{{ $station['id'] ?? '-' }}</td>
                                    <td>{{ $station['cluster_id'] ?? '-' }}</td>
                                    <td>{{ $station['name'] ?? '-' }}</td>
                                    <td>{{ $station['coordinate'] ?? '-' }}</td>
                                    <td>{{ $station['logger_id'] ?? '-' }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $station['logger_status'] ?? '-' }}</span></td>
                                    <td><span class="badge {{ ($station['connectivity_status'] ?? '') === 'Online' ? 'bg-success' : 'bg-secondary' }}">{{ $station['connectivity_status'] ?? '-' }}</span></td>
                                    <td>{{ $station['warning_station_id'] ?? '-' }}</td>
                                    <td>{{ ($sensorsByStation[$station['id'] ?? ''] ?? collect())->count() }}</td>
                                    <td>
                                        <span class="badge {{ in_array($station['status'] ?? '', ['Danger', 'Bahaya', 'Awas']) ? 'bg-danger' : (($station['status'] ?? '') === 'Waspada' ? 'bg-warning' : 'bg-success') }}">
                                            {{ $station['status'] ?? '-' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">Belum ada monitoring station.</td>
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
