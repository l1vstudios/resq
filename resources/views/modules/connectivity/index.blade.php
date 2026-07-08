@extends('layouts.master')

@section('title') Connectivity @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Device Setup @endslot
@slot('title') Connectivity @endslot
@endcomponent

@php
    $connectivity = collect($connectivity ?? config('resq_dummy.connectivity'));
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
@endphp

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Connectivity Setup</h4>
                <form method="POST" action="{{ route('connectivity.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Data Logger</label>
                        <select name="data_logger_id" class="form-select" required @disabled($dataLoggers->whereNotNull('db_id')->isEmpty())>
                            @foreach ($dataLoggers->whereNotNull('db_id') as $logger)
                                <option value="{{ $logger['db_id'] }}">{{ $logger['id'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Connectivity ID</label>
                        <input name="connectivity_code" class="form-control" placeholder="CONN-PDG-001" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="communication_type" class="form-select">
                                <option>Cellular</option>
                                <option>Ethernet</option>
                                <option>WiFi</option>
                                <option>LoRa</option>
                                <option>Satellite</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Protocol</label>
                            <select name="protocol" class="form-select">
                                <option>MQTT</option>
                                <option>HTTP</option>
                                <option>Modbus TCP</option>
                                <option>TCP</option>
                                <option>UDP</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Host / Endpoint</label>
                        <input name="host_or_endpoint" class="form-control" placeholder="mqtt.resq.local">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="port" class="form-control" placeholder="1883">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Topic / API Path</label>
                            <input name="topic_or_api_path" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Gateway</label><input name="gateway_id" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">SIM</label><input name="sim_number" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">IMEI</label><input name="imei" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">APN</label><input name="apn" class="form-control"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="connectivity_status" class="form-select" required>
                            <option>Online</option>
                            <option>Offline</option>
                            <option>Degraded</option>
                            <option>Maintenance</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" @disabled($dataLoggers->whereNotNull('db_id')->isEmpty())>Save / Update Connectivity</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Connectivity List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Connectivity ID</th>
                                <th>Logger ID</th>
                                <th>Type</th>
                                <th>Protocol</th>
                                <th>Host / Endpoint</th>
                                <th>Port</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($connectivity as $item)
                                <tr>
                                    <td>{{ $item['id'] ?? '-' }}</td>
                                    <td>{{ $item['logger_id'] ?? '-' }}</td>
                                    <td>{{ $item['communication_type'] ?? '-' }}</td>
                                    <td>{{ $item['protocol'] ?? '-' }}</td>
                                    <td>{{ $item['host_or_endpoint'] ?? '-' }}</td>
                                    <td>{{ $item['port'] ?? '-' }}</td>
                                    <td><span class="badge {{ ($item['connectivity_status'] ?? '') === 'Online' ? 'bg-success' : 'bg-secondary' }}">{{ $item['connectivity_status'] ?? '-' }}</span></td>
                                    <td class="text-end">
                                        @isset($item['db_id'])
                                            <form method="POST" action="{{ route('device-setup.destroy', ['type' => 'connectivity', 'id' => $item['db_id']]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        @endisset
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada connectivity.</td>
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
