@extends('layouts.master')

@section('title') Manajemen Assets @endsection

@section('css')
<style>
    .asset-page .card {
        border: 1px solid #d9e4f2;
        border-radius: 8px;
        box-shadow: none;
    }

    .asset-header {
        align-items: flex-start;
        display: flex;
        gap: 16px;
        justify-content: space-between;
    }

    .asset-title {
        color: #102653;
        font-size: 22px;
        font-weight: 800;
        margin: 0;
    }

    .asset-subtitle {
        color: #74788d;
        font-size: 13px;
        margin: 6px 0 0;
        max-width: 760px;
    }

    .asset-metric-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .asset-metric {
        background: #f8fbfe;
        border: 1px solid #d9e4f2;
        border-radius: 8px;
        min-height: 72px;
        padding: 10px 12px;
    }

    .asset-metric span {
        color: #74788d;
        display: block;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .asset-metric strong {
        color: #102653;
        display: block;
        font-size: 17px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .asset-status {
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 700;
        padding: 5px 10px;
    }

    .asset-status-success { background: #e9f7ef; color: #157347; }
    .asset-status-failed { background: #fdecec; color: #b42318; }
    .asset-status-idle { background: #eef4fb; color: #1d4d7d; }

    @media (max-width: 991px) {
        .asset-header { display: block; }
        .asset-metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 575px) {
        .asset-metric-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Configuration @endslot
@slot('title') Manajemen Assets @endslot
@endcomponent

@php
    $assets = collect($assets ?? []);
    $projects = collect($projects ?? []);
    $stations = collect($stations ?? []);
    $availablePorts = collect($availablePorts ?? []);
    $scanPayload = $scanResult['payload'] ?? null;
    $scanValues = $scanPayload['values'] ?? [];
@endphp

<div class="asset-page">
    @if(session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Data belum bisa disimpan.</div>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if($scanResult)
        <div class="alert {{ $scanResult['ok'] ? 'alert-success' : 'alert-danger' }}">
            <div class="fw-semibold">{{ $scanResult['title'] ?? 'Scan result' }}</div>
            <div>{{ $scanResult['message'] ?? '-' }}</div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="asset-header">
                <div>
                    <h1 class="asset-title">Manajemen Assets</h1>
                    <p class="asset-subtitle">Daftarkan asset lapangan dan scan MPPT EPEVER XTRA1206N lewat Modbus RTU dari port serial lokal.</p>
                </div>
                <span class="asset-status asset-status-idle">{{ $assets->count() }} assets</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title mb-3">Tambah / Update Asset</h4>
                    <form method="POST" action="{{ route('asset-management.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select" required>
                                <option value="">Pilih project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>
                                        {{ $project->project_code }} - {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Monitoring Station</label>
                            <select name="monitoring_station_id" class="form-select">
                                <option value="">-</option>
                                @foreach($stations as $station)
                                    <option value="{{ $station->id }}" @selected((string) old('monitoring_station_id') === (string) $station->id)>
                                        {{ $station->station_code }} - {{ $station->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kode Asset</label>
                                <input name="asset_code" class="form-control" value="{{ old('asset_code', 'MPPT-XTRA1206-001') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Asset</label>
                                <input name="name" class="form-control" value="{{ old('name', 'MPPT EPEVER XTRA1206N') }}" required>
                            </div>
                        </div>
                        <input type="hidden" name="asset_type" value="mppt_charge_controller">
                        <input type="hidden" name="protocol" value="modbus_rtu">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vendor</label>
                                <input name="vendor" class="form-control" value="{{ old('vendor', 'EPEVER') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Model</label>
                                <input name="model" class="form-control" value="{{ old('model', 'XTRA1206N') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Serial Port</label>
                            <input name="serial_port" class="form-control" list="serial-port-options" value="{{ old('serial_port', '/dev/cu.usbserial-142120') }}" required>
                            <datalist id="serial-port-options">
                                @foreach($availablePorts as $port)
                                    <option value="{{ $port }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Slave</label>
                                <input type="number" name="slave_address" class="form-control" value="{{ old('slave_address', 1) }}" min="1" max="247" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Baud Rate</label>
                                <select name="baud_rate" class="form-select">
                                    @foreach([9600, 19200, 38400, 57600, 115200] as $baudRate)
                                        <option value="{{ $baudRate }}" @selected((int) old('baud_rate', 115200) === $baudRate)>{{ $baudRate }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Timeout</label>
                                <input type="number" name="timeout_ms" class="form-control" value="{{ old('timeout_ms', 1000) }}" min="300" max="10000" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Data Bits</label>
                                <select name="data_bits" class="form-select">
                                    <option value="8" @selected((int) old('data_bits', 8) === 8)>8</option>
                                    <option value="7" @selected((int) old('data_bits', 8) === 7)>7</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Parity</label>
                                <select name="parity" class="form-select">
                                    @foreach(['none', 'even', 'odd'] as $parity)
                                        <option value="{{ $parity }}" @selected(old('parity', 'none') === $parity)>{{ strtoupper($parity) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stop Bits</label>
                                <select name="stop_bits" class="form-select">
                                    <option value="1" @selected((int) old('stop_bits', 1) === 1)>1</option>
                                    <option value="2" @selected((int) old('stop_bits', 1) === 2)>2</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                @foreach(['Active', 'Inactive', 'Maintenance', 'Fault'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', 'Active') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Simpan Asset
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title mb-3">Quick Scan MPPT</h4>
                    <form method="POST" action="{{ route('asset-management.quick-scan') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Serial Port</label>
                            <input name="serial_port" class="form-control" list="serial-port-options" value="{{ old('serial_port', '/dev/cu.usbserial-142120') }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Slave</label>
                                <input type="number" name="slave_address" class="form-control" value="{{ old('slave_address', 1) }}" min="1" max="247">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Baud</label>
                                <select name="baud_rate" class="form-select">
                                    @foreach([9600, 19200, 38400, 57600, 115200] as $baudRate)
                                        <option value="{{ $baudRate }}" @selected((int) old('baud_rate', 115200) === $baudRate)>{{ $baudRate }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Timeout</label>
                                <input type="number" name="timeout_ms" class="form-control" value="{{ old('timeout_ms', 1000) }}" min="300" max="10000">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bx bx-search-alt-2 me-1"></i> Scan Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            @if($scanPayload)
                <div class="card mb-3">
                    <div class="card-body">
                        <h4 class="card-title mb-3">{{ $scanPayload['device'] ?? 'MPPT' }} Scan Result</h4>
                        <div class="asset-metric-grid">
                            <div class="asset-metric"><span>PV (Solar Panel) Voltage</span><strong>{{ data_get($scanValues, 'pv_voltage', '-') }} V</strong></div>
                            <div class="asset-metric"><span>PV (Solar Panel) Current</span><strong>{{ data_get($scanValues, 'pv_current', '-') }} A</strong></div>
                            <div class="asset-metric"><span>PV (Solar Panel) Power</span><strong>{{ data_get($scanValues, 'pv_power', '-') }} W</strong></div>
                            <div class="asset-metric"><span>Battery Voltage</span><strong>{{ data_get($scanValues, 'battery_voltage', '-') }} V</strong></div>
                            <div class="asset-metric"><span>Charge Voltage</span><strong>{{ data_get($scanValues, 'charge_voltage', '-') }} V</strong></div>
                            <div class="asset-metric"><span>Charge Current</span><strong>{{ data_get($scanValues, 'charge_current', '-') }} A</strong></div>
                            <div class="asset-metric"><span>Load Power</span><strong>{{ data_get($scanValues, 'load_power', '-') }} W</strong></div>
                            <div class="asset-metric"><span>Battery SOC</span><strong>{{ data_get($scanValues, 'battery_soc', '-') }} %</strong></div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Daftar Assets</h4>
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Asset</th>
                                    <th>Koneksi</th>
                                    <th>Scan Terakhir</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assets as $asset)
                                    @php
                                        $payloadValues = $asset->last_scan_payload['values'] ?? [];
                                        $statusClass = $asset->last_scan_status === 'success'
                                            ? 'asset-status-success'
                                            : ($asset->last_scan_status === 'failed' ? 'asset-status-failed' : 'asset-status-idle');
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $asset->asset_code }}</div>
                                            <div>{{ $asset->name }}</div>
                                            <small class="text-muted">{{ $asset->vendor ?: '-' }} {{ $asset->model ?: '' }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $asset->serial_port ?: '-' }}</div>
                                            <small class="text-muted">Slave {{ $asset->slave_address }} / {{ $asset->baud_rate }} bps</small>
                                        </td>
                                        <td>
                                            <span class="asset-status {{ $statusClass }}">{{ $asset->last_scan_status ?: 'belum discan' }}</span>
                                            <small class="text-muted d-block mt-1">{{ optional($asset->last_scanned_at)->format('Y-m-d H:i') ?: '-' }}</small>
                                            @if($asset->last_scan_message)
                                                <small class="text-muted d-block">{{ $asset->last_scan_message }}</small>
                                            @endif
                                            @if($asset->last_scan_status === 'success')
                                                <small class="text-muted d-block">
                                                    PV {{ data_get($payloadValues, 'pv_power', '-') }} W,
                                                    Battery {{ data_get($payloadValues, 'battery_voltage', '-') }} V
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('asset-management.show', $asset) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bx bx-line-chart me-1"></i> Detail
                                            </a>
                                            <form method="POST" action="{{ route('asset-management.destroy', $asset) }}" class="d-inline" onsubmit="return confirm('Hapus asset ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="bx bx-trash me-1"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada asset. Simpan MPPT dulu, lalu jalankan scan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
