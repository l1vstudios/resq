@extends('layouts.master')

@section('title') Telemetry Configuration @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Configuration @endslot
@slot('title') Telemetry @endslot
@endcomponent

@php
    $sensors = config('resq_dummy.sensors');
@endphp

<div class="row">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Telemetry Configuration</h4>
                    <button type="button" class="btn btn-primary btn-sm">
                        <i class="bx bx-refresh me-1"></i> Telemetry Test
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Station</th>
                                <th>Sensor</th>
                                <th>Value</th>
                                <th>Threshold</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sensors as $sensor)
                                <tr>
                                    <td>{{ $sensor['monitoring_station_id'] }}</td>
                                    <td>{{ $sensor['id'] }}</td>
                                    <td>{{ $sensor['value'] }}</td>
                                    <td>{{ $sensor['threshold'] }}</td>
                                    <td>
                                        <span class="badge {{ $sensor['status'] === 'Danger' ? 'bg-danger' : 'bg-success' }}">
                                            {{ $sensor['status'] }}
                                        </span>
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
                <h4 class="card-title mb-4">Hazard Level Classification</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Level</th>
                                <th>Parameter</th>
                                <th>Threshold</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sensors as $sensor)
                                <tr>
                                    <td>
                                        <span class="badge {{ $sensor['status'] === 'Danger' ? 'bg-danger' : 'bg-success' }}">
                                            {{ $sensor['status'] }}
                                        </span>
                                    </td>
                                    <td>{{ $sensor['parameter'] }}</td>
                                    <td>{{ $sensor['threshold'] }}</td>
                                    <td>
                                        @if ($sensor['status'] === 'Danger')
                                            Trigger {{ $sensor['warning_station_id'] }} and send warning to public
                                        @else
                                            Store telemetry only
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Power & System Monitoring</h4>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Controller / Logger</label>
                        <select class="form-select">
                            <option>DL-0001</option>
                            <option>WS-CTRL-001</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Device Type</label>
                        <select class="form-select">
                            <option>Smart Battery / BMS</option>
                            <option>Solar Charger</option>
                            <option>Power Controller</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Communication Type</label>
                            <select class="form-select">
                                <option>RS485 / Modbus</option>
                                <option>Analog</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slave ID</label>
                            <input type="number" class="form-control" placeholder="3">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Register Mapping Profile</label>
                        <input type="text" class="form-control" placeholder="bms_profile_v1">
                    </div>
                    <button type="button" class="btn btn-primary">
                        <i class="bx bx-battery me-1"></i> Save Monitoring Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
