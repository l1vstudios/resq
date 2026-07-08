@extends('layouts.master')

@section('title') Sensors @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Monitoring @endslot
@slot('title') Sensors @endslot
@endcomponent

@php
    $monitoringStations = collect($monitoringStations ?? config('resq_dummy.monitoring_stations'));
    $sensors = collect($sensors ?? config('resq_dummy.sensors'));
    $dangerSensor = $sensors->firstWhere('status', 'Danger') ?? $sensors->first() ?? [
        'type' => '-',
    ];
@endphp

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Add Sensor</h4>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Monitoring Station</label>
                        <select class="form-select">
                            @foreach ($monitoringStations as $station)
                                <option>{{ $station['id'] }} - {{ $station['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type Of Sensor</label>
                        <input type="text" class="form-control" value="{{ $dangerSensor['type'] }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model Of Sensor</label>
                        <input type="text" class="form-control" placeholder="Model">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vendor</label>
                        <input type="text" class="form-control" placeholder="Vendor">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Manufacturing</label>
                            <input type="date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Installation</label>
                            <input type="date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Active</label>
                        <input type="date" class="form-control">
                    </div>
                    <button type="button" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Save Sensor
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Sensor Validation</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sensor</th>
                                <th>Monitoring ID</th>
                                <th>Warning ID</th>
                                <th>Parameter</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sensors as $sensor)
                                <tr>
                                    <td>
                                        <div>{{ $sensor['id'] }}</div>
                                        <small class="text-muted">{{ $sensor['type'] }}</small>
                                    </td>
                                    <td>{{ $sensor['monitoring_station_id'] }}</td>
                                    <td>{{ $sensor['warning_station_id'] }}</td>
                                    <td>{{ $sensor['parameter'] }}</td>
                                    <td>
                                        <span class="badge {{ $sensor['status'] === 'Danger' ? 'bg-danger' : 'bg-success' }}">
                                            {{ $sensor['status'] }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if ($sensor['status'] === 'Danger')
                                            <a href="{{ route('warning-stations.index') }}#command-test" class="btn btn-danger btn-sm">
                                                <i class="bx bx-broadcast me-1"></i> Send warning
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-light btn-sm">
                                                <i class="bx bx-check-circle me-1"></i> Normal
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">RS485 / Modbus Configuration</h4>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Slave ID</label>
                        <input type="number" class="form-control" placeholder="1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Baudrate</label>
                        <select class="form-select">
                            <option>9600</option>
                            <option>19200</option>
                            <option>38400</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Register Mapping Profile</label>
                        <input type="text" class="form-control" placeholder="profile_water_level_v1">
                    </div>
                </div>
                <button type="button" class="btn btn-primary">
                    <i class="bx bx-cog me-1"></i> Run Sensor Validation
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
