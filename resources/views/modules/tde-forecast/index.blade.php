@extends('layouts.master')

@section('title') TDE Forecast @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
<style>
    .tde-page-header {
        background: #fff;
        border: 0;
        border-radius: 2px;
        box-shadow: 0 6px 18px rgba(18, 38, 63, 0.07);
        margin-bottom: 14px;
        padding: 18px 20px;
    }

    .emp-ui .card,
    .emp-ui .ops-context-switcher,
    .tde-forecast-card,
    .tde-pattern-item,
    .tde-diagnosis-box,
    .tde-empty-panel,
    .tde-empty-item {
        background: #fff;
        border: 0;
        border-radius: 2px;
        box-shadow: 0 6px 18px rgba(18, 38, 63, 0.07);
    }

    .emp-ui .card {
        background-image: none;
        border: 0 !important;
        box-shadow: 0 6px 18px rgba(18, 38, 63, 0.07) !important;
    }

    .tde-realtime-bar {
        border-top: 0 !important;
    }

    .tde-refresh-button {
        background: #556ee6;
        border: 0;
        border-radius: 2px;
        color: #fff;
    }

    .tde-refresh-button:hover,
    .tde-refresh-button:focus {
        background: #485ec4;
        color: #fff;
    }

    .tde-page-header-row {
        align-items: flex-start;
        display: flex;
        gap: 14px;
        justify-content: space-between;
    }

    .tde-page-title {
        color: var(--emp-navy);
        font-size: 24px;
        font-weight: 900;
        letter-spacing: 0;
        line-height: 1.2;
        margin: 0;
    }

    .tde-page-subtitle {
        color: var(--emp-muted);
        font-size: 13px;
        margin: 6px 0 0;
        max-width: 720px;
    }

    .tde-header-meta {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .tde-forecast-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .tde-forecast-card {
        background: #fff;
        border: 0;
        min-height: 118px;
        padding: 14px;
    }

    .tde-forecast-card .label {
        color: var(--emp-muted);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .05em;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .tde-forecast-card .value {
        color: var(--emp-navy);
        font-size: 20px;
        font-weight: 900;
        line-height: 1.15;
        overflow-wrap: anywhere;
    }

    .tde-forecast-card .note {
        color: var(--emp-muted);
        font-size: 13px;
        margin-top: 8px;
    }

    .tde-pill {
        align-items: center;
        border-radius: 2px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        gap: 6px;
        min-height: 28px;
        padding: 6px 10px;
    }

    .tde-pill-dry { background: #e9f7ef; color: #0b6b34; }
    .tde-pill-wet { background: #e7f0ff; color: #0d4d96; }
    .tde-pill-warning { background: #fff5d9; color: #8a5b00; }
    .tde-pill-info { background: #eef4fb; color: #1d4d7d; }
    .tde-pill-error { background: #fdecec; color: #a12d25; }

    .tde-pattern {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(5, minmax(62px, 1fr));
    }

    .tde-pattern-item {
        background: #fff;
        border: 0;
        min-height: 74px;
        padding: 10px;
        text-align: center;
    }

    .tde-pattern-item .param {
        color: var(--emp-muted);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .tde-pattern-item .symbol {
        font-size: 28px;
        font-weight: 900;
        line-height: 1.2;
    }

    .tde-symbol-down { color: var(--emp-blue); }
    .tde-symbol-flat { color: var(--emp-gold); }
    .tde-symbol-up { color: var(--emp-green); }
    .tde-symbol-missing { color: var(--emp-muted); }

    .tde-parameter-table td,
    .tde-parameter-table th {
        vertical-align: middle;
    }

    .tde-station-strip {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: space-between;
    }

    .tde-section-card .card-body {
        padding: 18px;
    }

    .tde-diagnosis-box {
        background: #fff;
        border: 0;
        color: var(--emp-ink);
        font-weight: 800;
        line-height: 1.35;
        min-height: 44px;
        padding: 10px 12px;
    }

    .tde-empty-panel {
        background: #fff;
        border: 0;
        padding: 22px;
    }

    .tde-empty-title {
        color: var(--emp-navy);
        font-size: 18px;
        font-weight: 900;
        margin-bottom: 6px;
    }

    .tde-empty-copy {
        color: var(--emp-muted);
        font-size: 14px;
        margin-bottom: 16px;
        max-width: 820px;
    }

    .tde-empty-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .tde-empty-item {
        background: #fff;
        border: 0;
        min-height: 74px;
        padding: 12px;
    }

    .tde-empty-item strong {
        color: var(--emp-navy);
        display: block;
        font-size: 15px;
        margin-bottom: 4px;
    }

    .tde-empty-item span {
        color: var(--emp-muted);
        font-size: 13px;
    }

    @media (max-width: 1199px) {
        .tde-forecast-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tde-empty-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 575px) {
        .tde-forecast-grid { grid-template-columns: 1fr; }
        .tde-empty-grid { grid-template-columns: 1fr; }
        .tde-pattern { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tde-page-header-row { display: block; }
        .tde-header-meta { justify-content: flex-start; margin-top: 12px; }
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Forecast @endslot
@slot('title') TDE Forecast @endslot
@endcomponent

@php
    $stations = collect($stations ?? []);
    $output = $tde['output'] ?? null;
    $condition = $output['condition_current'] ?? [];
    $diagnosis = $output['diagnosis'] ?? [];
    $matrixMatch = $diagnosis['matrix_match'] ?? null;
    $fallback = $diagnosis['fallback_interpretation'] ?? null;
    $forecastSummary = $matrixMatch['summary'] ?? $fallback['summary'] ?? 'Belum ada diagnosis';
    $forecastBasis = $matrixMatch['basis'] ?? $fallback['basis'] ?? 'Data TDE belum cukup untuk evaluasi penuh.';
    $forecastType = $matrixMatch['type'] ?? $fallback['type'] ?? '-';
    $estimatedTime = $matrixMatch['estimated_time'] ?? $matrixMatch['perkiraan_waktu'] ?? $matrixMatch['time_estimate'] ?? '-';
    $pattern = $output['combination_pattern'] ?? [];
    $order = $pattern['order'] ?? ['AT', 'RH', 'DP', 'DPS', 'AP'];
    $symbols = $pattern['symbols'] ?? [];
    $parameters = collect($output['parameters'] ?? []);
    $window = $output['window'] ?? [];
    // Durasi rentang waktu analisis dalam istilah yang mudah dipahami (jam/menit/hari)
    $windowMinutes = (int) ($window['minutes'] ?? 0);
    $windowFriendly = $windowMinutes <= 0
        ? '-'
        : ($windowMinutes % 1440 === 0
            ? ($windowMinutes / 1440).' hari terakhir'
            : ($windowMinutes % 60 === 0
                ? ($windowMinutes / 60).' jam terakhir'
                : $windowMinutes.' menit terakhir'));
    $matrixStatus = $diagnosis['matrix_status'] ?? 'matrix_not_configured';
    $matrixVersion = $diagnosis['matrix_version'] ?? null;
    $executionState = $tde['execution_state'] ?? null;
    $isInsufficient = in_array($executionState, ['insufficient_data', 'partial'], true) || str_contains((string) ($pattern['key'] ?? ''), '?');
    $missingParameters = collect($diagnosis['missing_parameters'] ?? [])->filter()->values();
    $displayForecastSummary = $isInsufficient ? 'Belum siap' : $forecastSummary;
    $displayForecastBasis = $isInsufficient ? 'TDE membutuhkan minimal dua pembacaan untuk parameter AT, RH, DP, DPS, dan AP dalam rentang waktu analisis.' : $forecastBasis;
    $displayEstimatedTime = $isInsufficient ? 'Belum tersedia' : $estimatedTime;
    $conditionState = $condition['state'] ?? 'UNKNOWN';
    $conditionClass = $conditionState === 'BASAH' ? 'tde-pill-wet' : ($conditionState === 'KERING' ? 'tde-pill-dry' : 'tde-pill-warning');
    $matrixClass = $matrixStatus === 'matched' ? 'tde-pill-dry' : ($matrixStatus === 'matrix_not_matched' ? 'tde-pill-error' : 'tde-pill-warning');
    $symbolClass = fn ($symbol) => $symbol === '<' ? 'tde-symbol-down' : ($symbol === '>' ? 'tde-symbol-up' : ($symbol === '=' ? 'tde-symbol-flat' : 'tde-symbol-missing'));
    $derivedData = $output['derived_data'] ?? [];
    $dewPointMeta = $derivedData['dew_point_metadata'] ?? \App\Services\TrendDiagnosisEvaluator::dewPointMetadata()['dew_point'];
    $dewPointSpreadMeta = $derivedData['dew_point_spread_metadata'] ?? \App\Services\TrendDiagnosisEvaluator::dewPointMetadata()['dew_point_spread'];
    $derivedParameters = ['DP', 'DPS'];
@endphp

<div class="emp-ui">
<div class="tde-page-header">
    <div class="tde-page-header-row">
        <div>
            <h1 class="tde-page-title">TDE Forecast</h1>
            <p class="tde-page-subtitle">Panel diagnosis tren cuaca lokal dari data sensor station dan Matrix TDE.</p>
        </div>
        <div class="tde-header-meta">
            <span class="tde-pill tde-pill-info"><i class="bx bx-station"></i>{{ $selectedStation?->station_code ?? 'Belum ada station' }}</span>
            <span class="tde-pill {{ $matrixClass }}"><i class="bx bx-grid-alt"></i>{{ \Illuminate\Support\Str::headline($matrixStatus) }}</span>
        </div>
    </div>
</div>

<div class="card ops-context-switcher">
    <div class="card-body">
        <div class="tde-station-strip">
            <div>
                <h4 class="card-title mb-1">Monitoring Station</h4>
                <div class="text-muted small">{{ $selectedStation?->project?->project_code ?? '-' }} / {{ $selectedStation?->corridor?->corridor_code ?? 'Corridor belum dipilih' }}</div>
            </div>
            <form method="GET" action="{{ route('tde-forecast.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label for="tde-station-id" class="form-label mb-1">Station</label>
                    <select id="tde-station-id" name="station_id" class="form-select" style="min-width: 280px;">
                        @foreach($stations as $station)
                            <option value="{{ $station->id }}" @selected((int) $selectedStation?->id === (int) $station->id)>
                                {{ $station->station_code }} - {{ $station->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary tde-refresh-button">
                    <i class="bx bx-refresh me-1"></i> Refresh
                </button>
            </form>
        </div>
        <div class="tde-realtime-bar mt-3 pt-3" style="border-top:1px solid var(--emp-line);">
            <div class="d-flex flex-wrap gap-3 align-items-end">
                <div class="form-check form-switch mb-0" style="padding-top:6px;">
                    <input class="form-check-input" type="checkbox" id="tde-realtime-toggle">
                    <label class="form-check-label fw-semibold" for="tde-realtime-toggle">Perbarui otomatis</label>
                </div>
                <div>
                    <label for="tde-interval" class="form-label mb-1 small">Perbarui setiap (detik)</label>
                    <input type="number" id="tde-interval" class="form-control form-control-sm" style="width:120px;" min="2" max="3600" value="10">
                </div>
                <div class="text-muted small" style="padding-bottom:6px;">
                    Status: <span id="tde-realtime-status" class="fw-semibold">Nonaktif</span>
                    <span id="tde-last-update" class="ms-2"></span>
                </div>
            </div>
        </div>
    </div>
</div>

@if(! $selectedStation)
    <div class="card"><div class="card-body text-center text-muted">Belum ada monitoring station yang bisa diakses.</div></div>
@elseif(! $output)
    <div class="card"><div class="card-body text-center text-muted">Output TDE belum tersedia untuk station ini.</div></div>
@else
    <div class="tde-forecast-grid mb-3">
        <div class="tde-forecast-card">
            <div class="label">Kondisi Saat Ini</div>
            <div><span class="tde-pill {{ $conditionClass }}"><i class="bx bx-cloud"></i>{{ $conditionState }}</span></div>
            <div class="note">Rainfall {{ $condition['rainfall_value'] ?? '-' }} / threshold {{ $condition['threshold'] ?? '0.4' }}</div>
        </div>
        <div class="tde-forecast-card">
            <div class="label">Proyeksi Kondisi</div>
            <div class="value">{{ $displayForecastSummary }}</div>
            <div class="note">{{ $isInsufficient ? 'Menunggu data sensor' : $forecastType }}</div>
        </div>
        <div class="tde-forecast-card">
            <div class="label">Perkiraan Waktu</div>
            <div class="value">{{ $displayEstimatedTime }}</div>
            <div class="note">Dihitung pada {{ isset($window['to']) ? \Illuminate\Support\Carbon::parse($window['to'])->format('d M Y H:i') : '-' }}</div>
        </div>
        <div class="tde-forecast-card">
            <div class="label">Matrix TDE</div>
            <div><span class="tde-pill {{ $matrixClass }}"><i class="bx bx-grid-alt"></i>{{ \Illuminate\Support\Str::headline($matrixStatus) }}</span></div>
            <div class="note">{{ $matrixVersion ? ($matrixVersion['matrix_code'].' / '.$matrixVersion['name']) : 'Matrix belum dipilih' }}</div>
        </div>
    </div>

    @if(session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if($matrixStatus !== 'matched')
        <div class="card tde-section-card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3 align-items-end justify-content-between">
                    <div>
                        <h5 class="mb-1">Sambungkan Matrix TDE</h5>
                        <div class="text-muted small">
                            @if($matrixStatus === 'matrix_not_configured')
                                Station ini belum terhubung ke Matrix TDE. Pilih matriks lalu sambungkan agar diagnosa akhir aktif.
                            @else
                                Pattern belum cocok dengan matriks aktif. Anda bisa mengganti matriks di sini.
                            @endif
                        </div>
                    </div>
                    @if(! empty($availableMatrices))
                        <form method="POST" action="{{ route('tde-forecast.attach-matrix') }}" class="d-flex flex-wrap gap-2 align-items-end">
                            @csrf
                            <input type="hidden" name="station_id" value="{{ $selectedStation->id }}">
                            <div>
                                <label class="form-label mb-1 small">Pilih Matrix TDE</label>
                                <select name="tde_matrix_version_id" class="form-select" style="min-width:280px;" required>
                                    @foreach($availableMatrices as $matrix)
                                        <option value="{{ $matrix['id'] }}" @selected((int) ($currentMatrixId ?? 0) === (int) $matrix['id'])>
                                            {{ $matrix['matrix_code'] }} - {{ $matrix['name'] }}{{ $matrix['version_label'] ? ' ('.$matrix['version_label'].')' : '' }} [{{ $matrix['scope'] }}]
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-warning"><i class="bx bx-link me-1"></i> Sambungkan</button>
                        </form>
                    @else
                        <div class="text-muted">
                            Belum ada Matrix TDE. Import dulu di <a href="{{ route('master-data-tde.index') }}">Master Data TDE</a>.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($isInsufficient)
        <div class="tde-empty-panel mb-3">
            <div class="tde-empty-title">Data TDE belum cukup untuk diagnosis</div>
            <div class="tde-empty-copy">{{ $displayForecastBasis }}</div>
            <div class="tde-empty-grid">
                <div class="tde-empty-item">
                    <strong>{{ $window['reading_count'] ?? 0 }} pembacaan</strong>
                    <span>Jumlah data sensor dalam rentang waktu ini</span>
                </div>
                <div class="tde-empty-item">
                    <strong>{{ $windowFriendly }}</strong>
                    <span>Rentang waktu analisis</span>
                </div>
                <div class="tde-empty-item">
                    <strong>{{ $missingParameters->isNotEmpty() ? $missingParameters->implode(', ') : 'AT, RH, DP, DPS, AP' }}</strong>
                    <span>Parameter yang belum memenuhi syarat</span>
                </div>
                <div class="tde-empty-item">
                    <strong>{{ isset($window['to']) ? \Illuminate\Support\Carbon::parse($window['to'])->format('d M Y H:i') : '-' }}</strong>
                    <span>Waktu perhitungan terakhir</span>
                </div>
            </div>
        </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="card tde-section-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Combination Pattern</h4>
                            <div class="text-muted small">Pattern key: <span id="tde-pattern-key">{{ $pattern['key'] ?? '-' }}</span></div>
                        </div>
                        <span class="tde-pill tde-pill-info">{{ $windowFriendly }}</span>
                    </div>
                    <div class="tde-pattern" id="tde-pattern-grid">
                        @foreach($order as $index => $parameter)
                            @php($symbol = $symbols[$index] ?? '?')
                            <div class="tde-pattern-item">
                                <div class="param">{{ $parameter }}</div>
                                <div class="symbol {{ $symbolClass($symbol) }}">{{ $symbol }}</div>
                                <div class="text-muted small">{{ $parameters[$parameter]['label'] ?? '-' }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-muted small mt-3">{{ $displayForecastBasis }}</div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card tde-section-card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Trend Parameter &amp; Sumber Sensor</h4>
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle tde-parameter-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Parameter</th>
                                    <th>Sumber Sensor</th>
                                    <th>Awal</th>
                                    <th>Akhir</th>
                                    <th>Perubahan</th>
                                    <th>Threshold Turun / Naik</th>
                                    <th>Klasifikasi</th>
                                </tr>
                            </thead>
                            <tbody id="tde-parameter-rows">
                                @foreach($order as $parameter)
                                    @php($row = $parameters[$parameter] ?? [])
                                    @php($classification = $row['classification'] ?? '?')
                                    @php($isDerived = ($row['is_derived'] ?? in_array($parameter, $derivedParameters, true)))
                                    @php($sensorCode = $row['sensor_code'] ?? null)
                                    @php($derivedFrom = $row['derived_from'] ?? null)
                                    <tr>
                                        <td>
                                            <strong>{{ $parameter }}</strong>
                                            <div class="text-muted small">{{ $row['unit'] ?? '-' }}</div>
                                        </td>
                                        <td>
                                            @if($isDerived)
                                                <span class="tde-pill tde-pill-warning" style="min-height:22px;padding:3px 8px;font-size:11px;">Turunan</span>
                                                <div class="text-muted small mt-1">
                                                    dari {{ is_array($derivedFrom) && $derivedFrom ? implode(' + ', $derivedFrom) : ($parameter === 'DP' ? 'AT + RH' : 'AT + DP') }}
                                                </div>
                                            @elseif($sensorCode)
                                                <span class="tde-pill tde-pill-info" style="min-height:22px;padding:3px 8px;font-size:11px;"><i class="bx bx-chip"></i>{{ $sensorCode }}</span>
                                                @if($row['parameter_label'] ?? null)
                                                    <div class="text-muted small mt-1">{{ $row['parameter_label'] }}</div>
                                                @endif
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $row['value_start'] ?? '-' }}</td>
                                        <td>{{ $row['value_end'] ?? '-' }}</td>
                                        <td>{{ $row['change'] ?? '-' }}</td>
                                        <td>
                                            <span class="text-primary" title="{{ $row['threshold_down_label'] ?? 'Turun' }}">&le; {{ $row['threshold_down'] ?? ('-'.($row['threshold'] ?? '')) }}</span>
                                            <span class="text-muted">/</span>
                                            <span class="text-success" title="{{ $row['threshold_up_label'] ?? 'Naik' }}">&ge; +{{ $row['threshold_up'] ?? ($row['threshold'] ?? '') }}</span>
                                            <div class="text-muted small">{{ $row['threshold_down_label'] ?? 'Turun' }} / {{ $row['threshold_up_label'] ?? 'Naik' }}</div>
                                        </td>
                                        <td>
                                            <span class="tde-pill tde-pill-info">
                                                <span class="{{ $symbolClass($classification) }}">{{ $classification }}</span>
                                                {{ $row['label'] ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card tde-section-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                        <h4 class="card-title mb-0">List Data</h4>
                        <ul class="nav nav-pills" role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tde-tab-sensors" role="tab">Sumber Sensor</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tde-tab-readings" role="tab">Pembacaan Terakhir</a></li>
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tde-tab-sensors" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Tipe</th>
                                            <th>Sensor / Sumber</th>
                                            <th>Nilai Terakhir</th>
                                            <th>Jumlah Sampel</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tde-sensor-rows">
                                        @forelse(($output['station_sensors'] ?? []) as $sensorRow)
                                            <tr>
                                                <td><strong>{{ $sensorRow['parameter'] }}</strong></td>
                                                <td>
                                                    @if($sensorRow['is_derived'] ?? false)
                                                        <span class="tde-pill tde-pill-warning" style="min-height:20px;padding:2px 7px;font-size:10px;">Turunan</span>
                                                    @else
                                                        <span class="tde-pill tde-pill-info" style="min-height:20px;padding:2px 7px;font-size:10px;">Sensor</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($sensorRow['is_derived'] ?? false)
                                                        {{ is_array($sensorRow['derived_from'] ?? null) ? implode(' + ', $sensorRow['derived_from']) : '-' }}
                                                    @else
                                                        {{ $sensorRow['sensor_code'] ?? '-' }}
                                                    @endif
                                                </td>
                                                <td>{{ $sensorRow['latest_value'] ?? '-' }} {{ $sensorRow['unit'] ?? '' }}</td>
                                                <td>{{ $sensorRow['sample_count'] ?? 0 }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted">Belum ada data sensor.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane" id="tde-tab-readings" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Parameter</th>
                                            <th>Nilai</th>
                                            <th>Sumber</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tde-reading-rows">
                                        @forelse(($output['recent_readings'] ?? []) as $readingRow)
                                            <tr>
                                                <td>{{ isset($readingRow['timestamp']) ? \Illuminate\Support\Carbon::parse($readingRow['timestamp'])->format('Y-m-d H:i:s') : '-' }}</td>
                                                <td><strong>{{ $readingRow['parameter'] }}</strong></td>
                                                <td>{{ $readingRow['value'] ?? '-' }} {{ $readingRow['unit'] ?? '' }}</td>
                                                <td>
                                                    @if($readingRow['is_derived'] ?? false)
                                                        <span class="text-warning">Turunan</span>
                                                    @else
                                                        {{ $readingRow['sensor_code'] ?? 'Sensor' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted">Belum ada pembacaan.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card tde-section-card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Diagnosis Detail</h4>
                    <div class="tde-diagnosis-box mb-2">{{ $displayForecastSummary }}</div>
                    <div class="text-muted">{{ $displayForecastBasis }}</div>
                    @if($matrixMatch)
                        <div class="mt-3">
                            <span class="tde-pill tde-pill-dry">{{ $matrixMatch['output_code'] ?? 'MATRIX_MATCH' }}</span>
                            @if($matrixMatch['confidence'] ?? null)
                                <span class="tde-pill tde-pill-info">{{ $matrixMatch['confidence'] }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card tde-section-card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Evaluasi</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="ops-readonly-field">{{ \Illuminate\Support\Str::headline($tde['execution_state'] ?? '-') }}</div>
                            <div class="text-muted small mt-1">Execution state</div>
                        </div>
                        <div class="col-md-6">
                            <div class="ops-readonly-field">{{ $window['reading_count'] ?? 0 }} pembacaan</div>
                            <div class="text-muted small mt-1">Jumlah data dalam rentang</div>
                        </div>
                        <div class="col-md-6">
                            <div class="ops-readonly-field">{{ isset($window['from']) ? \Illuminate\Support\Carbon::parse($window['from'])->format('d M H:i') : '-' }}</div>
                            <div class="text-muted small mt-1">Mulai dari</div>
                        </div>
                        <div class="col-md-6">
                            <div class="ops-readonly-field">{{ isset($window['to']) ? \Illuminate\Support\Carbon::parse($window['to'])->format('d M H:i') : '-' }}</div>
                            <div class="text-muted small mt-1">Sampai dengan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card tde-section-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Sumber Nilai Dew Point</h4>
                            <div class="text-muted small">Transparansi untuk peneliti: dari mana nilai DP &amp; DPS berasal.</div>
                        </div>
                        <span class="tde-pill tde-pill-info"><i class="bx bx-flask"></i>{{ $dewPointMeta['method'] ?? 'Magnus-Tetens' }}</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="tde-diagnosis-box mb-2">
                                <div class="mb-1"><strong>{{ $dewPointMeta['name'] ?? 'Dew Point' }}</strong></div>
                                <div class="text-muted small mb-2">Nilai turunan dari {{ implode(' &amp; ', $dewPointMeta['derived_from'] ?? ['AT', 'RH']) }} (bukan hasil ukur sensor langsung).</div>
                                <code style="display:block;background:#fff;border:0;border-radius:2px;padding:8px 10px;color:var(--emp-navy);box-shadow:0 6px 18px rgba(18,38,63,.07);">
                                    {{ $dewPointMeta['formula'] ?? 'Td = (c * γ) / (b - γ)' }}
                                </code>
                                <div class="text-muted small mt-2">
                                    Koefisien: b = {{ $dewPointMeta['coefficients']['b'] ?? '17.62' }},
                                    c = {{ $dewPointMeta['coefficients']['c'] ?? '243.12' }} °C.
                                    @if(isset($dewPointMeta['valid_range']))
                                        Valid {{ $dewPointMeta['valid_range']['min_temperature_c'] }}°C s.d. {{ $dewPointMeta['valid_range']['max_temperature_c'] }}°C
                                        (galat &lt; {{ $dewPointMeta['valid_range']['accuracy_c'] }}°C).
                                    @endif
                                </div>
                            </div>
                            <div class="tde-diagnosis-box">
                                <div class="mb-1"><strong>{{ $dewPointSpreadMeta['name'] ?? 'Dew-Point Spread' }}</strong></div>
                                <code style="display:block;background:#fff;border:0;border-radius:2px;padding:8px 10px;color:var(--emp-navy);box-shadow:0 6px 18px rgba(18,38,63,.07);">
                                    {{ $dewPointSpreadMeta['formula'] ?? 'DPS = AT - DP' }}
                                </code>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="label text-muted" style="font-size:12px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;margin-bottom:8px;">Referensi Ilmiah</div>
                            <ul class="text-muted small mb-0" style="padding-left:18px;">
                                @foreach(($dewPointMeta['references'] ?? []) as $reference)
                                    <li class="mb-2">{{ $reference }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endif
</div>
@endsection

@section('script')
<script>
(function () {
    var stationSelect = document.getElementById('tde-station-id');
    stationSelect?.addEventListener('change', function () { this.form?.submit(); });

    var dataUrl = @json(route('tde-forecast.data'));
    var toggle = document.getElementById('tde-realtime-toggle');
    var intervalInput = document.getElementById('tde-interval');
    var statusEl = document.getElementById('tde-realtime-status');
    var lastUpdateEl = document.getElementById('tde-last-update');
    var timer = null;

    var symbolClass = function (s) {
        if (s === '<') return 'tde-symbol-down';
        if (s === '>') return 'tde-symbol-up';
        if (s === '=') return 'tde-symbol-flat';
        return 'tde-symbol-missing';
    };
    var esc = function (v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };
    var fmtTime = function (iso) {
        if (!iso) return '-';
        var d = new Date(iso);
        if (isNaN(d)) return '-';
        var p = function (n) { return String(n).padStart(2, '0'); };
        return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
    };

    function render(tde) {
        if (!tde || !tde.output) return;
        var out = tde.output;
        var params = out.parameters || {};
        var order = (out.combination_pattern && out.combination_pattern.order) || ['AT', 'RH', 'DP', 'DPS', 'AP'];
        var symbols = (out.combination_pattern && out.combination_pattern.symbols) || [];

        // pattern key
        var keyEl = document.getElementById('tde-pattern-key');
        if (keyEl) keyEl.textContent = (out.combination_pattern && out.combination_pattern.key) || '-';

        // pattern grid
        var grid = document.getElementById('tde-pattern-grid');
        if (grid) {
            grid.innerHTML = order.map(function (p, i) {
                var sym = symbols[i] || '?';
                var label = (params[p] && params[p].label) || '-';
                return '<div class="tde-pattern-item"><div class="param">' + esc(p) + '</div>'
                    + '<div class="symbol ' + symbolClass(sym) + '">' + esc(sym) + '</div>'
                    + '<div class="text-muted small">' + esc(label) + '</div></div>';
            }).join('');
        }

        // parameter rows
        var pbody = document.getElementById('tde-parameter-rows');
        if (pbody) {
            pbody.innerHTML = order.map(function (p) {
                var r = params[p] || {};
                var cls = r.classification || '?';
                var derived = r.is_derived || (p === 'DP' || p === 'DPS');
                var sourceCell;
                if (derived) {
                    var df = Array.isArray(r.derived_from) && r.derived_from.length ? r.derived_from.join(' + ') : (p === 'DP' ? 'AT + RH' : 'AT + DP');
                    sourceCell = '<span class="tde-pill tde-pill-warning" style="min-height:22px;padding:3px 8px;font-size:11px;">Turunan</span>'
                        + '<div class="text-muted small mt-1">dari ' + esc(df) + '</div>';
                } else if (r.sensor_code) {
                    sourceCell = '<span class="tde-pill tde-pill-info" style="min-height:22px;padding:3px 8px;font-size:11px;"><i class="bx bx-chip"></i>' + esc(r.sensor_code) + '</span>';
                    if (r.parameter_label) sourceCell += '<div class="text-muted small mt-1">' + esc(r.parameter_label) + '</div>';
                } else {
                    sourceCell = '<span class="text-muted small">-</span>';
                }
                return '<tr><td><strong>' + esc(p) + '</strong><div class="text-muted small">' + esc(r.unit || '-') + '</div></td>'
                    + '<td>' + sourceCell + '</td>'
                    + '<td>' + esc(r.value_start != null ? r.value_start : '-') + '</td>'
                    + '<td>' + esc(r.value_end != null ? r.value_end : '-') + '</td>'
                    + '<td>' + esc(r.change != null ? r.change : '-') + '</td>'
                    + '<td><span class="text-primary">&le; ' + esc(r.threshold_down != null ? r.threshold_down : '-') + '</span> '
                    + '<span class="text-muted">/</span> '
                    + '<span class="text-success">&ge; +' + esc(r.threshold_up != null ? r.threshold_up : '-') + '</span>'
                    + '<div class="text-muted small">' + esc(r.threshold_down_label || 'Turun') + ' / ' + esc(r.threshold_up_label || 'Naik') + '</div></td>'
                    + '<td><span class="tde-pill tde-pill-info"><span class="' + symbolClass(cls) + '">' + esc(cls) + '</span> ' + esc(r.label || '-') + '</span></td></tr>';
            }).join('');
        }

        // station sensors
        var sbody = document.getElementById('tde-sensor-rows');
        if (sbody) {
            var sensors = out.station_sensors || [];
            sbody.innerHTML = sensors.length ? sensors.map(function (s) {
                var typeCell = s.is_derived
                    ? '<span class="tde-pill tde-pill-warning" style="min-height:20px;padding:2px 7px;font-size:10px;">Turunan</span>'
                    : '<span class="tde-pill tde-pill-info" style="min-height:20px;padding:2px 7px;font-size:10px;">Sensor</span>';
                var src = s.is_derived ? (Array.isArray(s.derived_from) ? s.derived_from.join(' + ') : '-') : (s.sensor_code || '-');
                return '<tr><td><strong>' + esc(s.parameter) + '</strong></td><td>' + typeCell + '</td>'
                    + '<td>' + esc(src) + '</td>'
                    + '<td>' + esc(s.latest_value != null ? s.latest_value : '-') + ' ' + esc(s.unit || '') + '</td>'
                    + '<td>' + esc(s.sample_count || 0) + '</td></tr>';
            }).join('') : '<tr><td colspan="5" class="text-center text-muted">Belum ada data sensor.</td></tr>';
        }

        // recent readings
        var rbody = document.getElementById('tde-reading-rows');
        if (rbody) {
            var readings = out.recent_readings || [];
            rbody.innerHTML = readings.length ? readings.map(function (r) {
                var srcCell = r.is_derived ? '<span class="text-warning">Turunan</span>' : esc(r.sensor_code || 'Sensor');
                return '<tr><td>' + esc(fmtTime(r.timestamp)) + '</td><td><strong>' + esc(r.parameter) + '</strong></td>'
                    + '<td>' + esc(r.value != null ? r.value : '-') + ' ' + esc(r.unit || '') + '</td>'
                    + '<td>' + srcCell + '</td></tr>';
            }).join('') : '<tr><td colspan="4" class="text-center text-muted">Belum ada pembacaan.</td></tr>';
        }
    }

    function poll() {
        var stationId = stationSelect ? stationSelect.value : '';
        if (!stationId) return;
        fetch(dataUrl + '?station_id=' + encodeURIComponent(stationId), { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
            .then(function (json) {
                if (json && json.ok) {
                    render(json.tde);
                    lastUpdateEl.textContent = 'Update: ' + fmtTime(json.server_time);
                }
            })
            .catch(function () {
                if (statusEl) statusEl.textContent = 'Gagal memperbarui';
            });
    }

    function start() {
        stop();
        var sec = Math.max(2, parseInt(intervalInput.value, 10) || 10);
        timer = setInterval(poll, sec * 1000);
        statusEl.textContent = 'Aktif (' + sec + 's)';
        poll();
    }
    function stop() {
        if (timer) { clearInterval(timer); timer = null; }
        if (statusEl) statusEl.textContent = 'Nonaktif';
    }

    toggle?.addEventListener('change', function () { this.checked ? start() : stop(); });
    intervalInput?.addEventListener('change', function () { if (toggle && toggle.checked) start(); });
})();
</script>
@endsection
