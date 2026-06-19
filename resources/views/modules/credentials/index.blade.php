@extends('layouts.master')

@section('title') Credential / Authentication @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Devices @endslot
@slot('title') Credential / Authentication @endslot
@endcomponent

@php
    $credentials = config('resq_dummy.credentials');
@endphp

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Credential / Authentication List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Credential ID</th>
                                <th>Logger ID</th>
                                <th>Device Token / API Key</th>
                                <th>MQTT Username</th>
                                <th>Password Hash</th>
                                <th>Certificate Ref</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Revoked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($credentials as $credential)
                                <tr>
                                    <td>{{ $credential['id'] }}</td>
                                    <td>{{ $credential['logger_id'] }}</td>
                                    <td><code>{{ $credential['device_token'] }}</code></td>
                                    <td>{{ $credential['mqtt_username'] }}</td>
                                    <td><code>{{ $credential['mqtt_password_hash'] }}</code></td>
                                    <td>{{ $credential['certificate_ref'] }}</td>
                                    <td><span class="badge bg-success">{{ $credential['credential_status'] }}</span></td>
                                    <td>{{ $credential['created_at'] }}</td>
                                    <td>{{ $credential['revoked_at'] ?? '-' }}</td>
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
