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
                <form method="POST" action="{{ route('data-loggers.store') }}" id="data-logger-form">
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
                        <label class="form-label">IP / Host Remote</label>
                        <input name="remote_host" class="form-control" placeholder="192.168.3.1">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">SSH Port</label>
                            <input type="number" name="remote_ssh_port" class="form-control" value="22" min="1" max="65535">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">SSH User</label>
                            <input name="remote_ssh_user" class="form-control" placeholder="root">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SSH Password</label>
                        <input type="password" name="remote_ssh_password" class="form-control" placeholder="Biarkan kosong kalau sudah pernah disimpan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Node-RED / Gateway Path</label>
                        <input name="remote_gateway_path" class="form-control" placeholder="/root/rednode-gateway">
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

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Test Remote Logger</h4>
                <div class="mb-3">
                    <label class="form-label">Data Logger</label>
                    <select class="form-select" id="remote-test-logger" @disabled($dataLoggers->whereNotNull('db_id')->isEmpty())>
                        @foreach ($dataLoggers->whereNotNull('db_id') as $logger)
                            <option value="{{ $logger['db_id'] }}">{{ $logger['id'] }} - {{ $logger['remote_host'] ?? 'IP belum diisi' }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-outline-primary" id="remote-test-run" @disabled($dataLoggers->whereNotNull('db_id')->isEmpty())>
                    <i class="bx bx-wifi me-1"></i> Ping IP Logger
                </button>
                <div class="form-text">Ping hanya cek IP bisa dijangkau. Scan RedNode tetap butuh SSH, Node.js, folder gateway, dan file script di logger.</div>
                <div class="alert alert-info mt-3 mb-0 d-none" id="remote-test-message"></div>
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
                                <th>Remote IP</th>
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
                                    <td>
                                        <div>{{ $logger['remote_host'] ?? '-' }}</div>
                                        <small class="text-muted">
                                            {{ $logger['remote_ssh_user'] ?? '-' }}@{{ $logger['remote_ssh_port'] ?? 22 }}
                                        </small>
                                    </td>
                                    <td>{{ $logger['logger_model'] ?? '-' }}</td>
                                    <td>{{ $logger['vendor'] ?? '-' }}</td>
                                    <td>{{ $logger['firmware_version'] ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ ($logger['logger_status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary' }}">{{ $logger['logger_status'] ?? '-' }}</span>
                                        @if (! empty($logger['remote_last_status']))
                                            <div><small class="{{ $logger['remote_last_status'] === 'Success' ? 'text-success' : 'text-danger' }}">{{ $logger['remote_last_status'] }}</small></div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @isset($logger['db_id'])
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    data-edit-form="#data-logger-form"
                                                    data-edit-fields="{{ base64_encode(json_encode([
                                                        'monitoring_station_id' => $logger['monitoring_station_db_id'] ?? '',
                                                        'logger_code' => $logger['id'] ?? '',
                                                        'serial_number' => $logger['serial_number'] ?? '',
                                                        'logger_model' => $logger['logger_model'] ?? '',
                                                        'vendor' => $logger['vendor'] ?? '',
                                                        'firmware_version' => $logger['firmware_version'] ?? '',
                                                        'device_label' => $logger['device_label'] ?? '',
                                                        'remote_host' => $logger['remote_host'] ?? '',
                                                        'remote_ssh_port' => $logger['remote_ssh_port'] ?? 22,
                                                        'remote_ssh_user' => $logger['remote_ssh_user'] ?? '',
                                                        'remote_gateway_path' => $logger['remote_gateway_path'] ?? '',
                                                        'logger_status' => $logger['logger_status'] ?? 'Active',
                                                    ])) }}">Edit</button>
                                                <form method="POST" action="{{ route('device-setup.destroy', ['type' => 'data-logger', 'id' => $logger['db_id']]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
                                        @endisset
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Belum ada data logger.</td>
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

@section('script')
<script>
    (function () {
        const button = document.getElementById('remote-test-run');
        const select = document.getElementById('remote-test-logger');
        const message = document.getElementById('remote-test-message');

        if (!button || !select || !message) {
            return;
        }

        function setMessage(text, type) {
            message.className = 'alert mt-3 mb-0 alert-' + (type || 'info');
            message.textContent = text || '';
            message.classList.toggle('d-none', !text);
        }

        button.addEventListener('click', async function () {
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...';
            setMessage('Sedang ping IP logger dari server. Ini belum mengecek login SSH.', 'info');

            try {
                const response = await fetch(@json(route('data-loggers.test-remote')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify({
                        data_logger_id: select.value,
                    }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    throw new Error(data.message || 'Ping logger gagal.');
                }

                setMessage(data.message + ' Host: ' + data.host, 'success');
            } catch (error) {
                setMessage(error.message, 'warning');
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        });
    })();
</script>
@endsection
