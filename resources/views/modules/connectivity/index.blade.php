@extends('layouts.master')

@section('title') Connectivity @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Devices @endslot
@slot('title') Connectivity @endslot
@endcomponent

@php
    $connectivity = config('resq_dummy.connectivity');
@endphp

<div class="row">
    <div class="col-12">
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
                                <th>Topic / API Path</th>
                                <th>Gateway</th>
                                <th>SIM</th>
                                <th>IMEI</th>
                                <th>APN</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($connectivity as $item)
                                <tr>
                                    <td>{{ $item['id'] }}</td>
                                    <td>{{ $item['logger_id'] }}</td>
                                    <td>{{ $item['communication_type'] }}</td>
                                    <td>{{ $item['protocol'] }}</td>
                                    <td>{{ $item['host_or_endpoint'] }}</td>
                                    <td>{{ $item['port'] }}</td>
                                    <td>{{ $item['topic_or_api_path'] }}</td>
                                    <td>{{ $item['gateway_id'] }}</td>
                                    <td>{{ $item['sim_number'] }}</td>
                                    <td>{{ $item['imei'] }}</td>
                                    <td>{{ $item['apn'] }}</td>
                                    <td><span class="badge bg-success">{{ $item['connectivity_status'] }}</span></td>
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
