@extends('layouts.master')

@section('title') Credentials @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Device Setup @endslot
@slot('title') Credentials @endslot
@endcomponent

@php
    $credentials = collect($credentials ?? config('resq_dummy.credentials'));
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
@endphp

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Credential Setup</h4>
                <form method="POST" action="{{ route('credentials.store') }}">
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
                        <label class="form-label">Credential ID</label>
                        <input name="credential_code" class="form-control" placeholder="CRED-PDG-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Device Token / API Key</label>
                        <input name="device_token" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">MQTT Username</label><input name="mqtt_username" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Password Hash</label><input name="mqtt_password_hash" class="form-control"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Certificate Ref</label>
                        <input name="certificate_ref" class="form-control" placeholder="cert/MS-PDG-001.pem">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="credential_status" class="form-select" required>
                                <option>Active</option>
                                <option>Revoked</option>
                                <option>Expired</option>
                                <option>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Revoked At</label>
                            <input type="datetime-local" name="revoked_at" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" @disabled($dataLoggers->whereNotNull('db_id')->isEmpty())>Save / Update Credential</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
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
                                <th>Certificate Ref</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($credentials as $credential)
                                <tr>
                                    <td>{{ $credential['id'] ?? '-' }}</td>
                                    <td>{{ $credential['logger_id'] ?? '-' }}</td>
                                    <td><code>{{ $credential['device_token'] ?? '-' }}</code></td>
                                    <td>{{ $credential['mqtt_username'] ?? '-' }}</td>
                                    <td>{{ $credential['certificate_ref'] ?? '-' }}</td>
                                    <td><span class="badge {{ ($credential['credential_status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary' }}">{{ $credential['credential_status'] ?? '-' }}</span></td>
                                    <td>{{ $credential['created_at'] ?? '-' }}</td>
                                    <td class="text-end">
                                        @isset($credential['db_id'])
                                            <form method="POST" action="{{ route('device-setup.destroy', ['type' => 'credential', 'id' => $credential['db_id']]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        @endisset
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada credential.</td>
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
