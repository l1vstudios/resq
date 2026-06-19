@extends('layouts.master')

@section('title') Data Loggers @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Devices @endslot
@slot('title') Data Loggers @endslot
@endcomponent

@php
    $dataLoggers = config('resq_dummy.data_loggers');
@endphp

<div class="row">
    <div class="col-12">
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
                                <th>Device Label / QR</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dataLoggers as $logger)
                                <tr>
                                    <td>{{ $logger['id'] }}</td>
                                    <td>{{ $logger['monitoring_station_id'] }}</td>
                                    <td>{{ $logger['serial_number'] }}</td>
                                    <td>{{ $logger['logger_model'] }}</td>
                                    <td>{{ $logger['vendor'] }}</td>
                                    <td>{{ $logger['firmware_version'] }}</td>
                                    <td>{{ $logger['device_label'] }}</td>
                                    <td><span class="badge bg-success">{{ $logger['logger_status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
