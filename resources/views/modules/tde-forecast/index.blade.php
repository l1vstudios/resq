@extends('layouts.master')

@section('title') TDE Forecast @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
<style>
    .tde-page-header {
        background: #fff;
        border: 1px solid var(--emp-line);
        border-left: 5px solid var(--emp-teal);
        border-radius: 8px;
        margin-bottom: 14px;
        padding: 18px 20px;
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
        border: 1px solid var(--emp-line);
        border-radius: 8px;
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
        border-radius: 999px;
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
        background: #f8fbfe;
        border: 1px solid var(--emp-line);
        border-radius: 8px;
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
        background: #f8fbfe;
        border: 1px solid var(--emp-line);
        border-radius: 8px;
        color: var(--emp-ink);
        font-weight: 800;
        line-height: 1.35;
        min-height: 44px;
        padding: 10px 12px;
    }

    .tde-empty-panel {
        background: #fff;
        border: 1px solid var(--emp-line);
        border-radius: 8px;
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
        background: #f8fbfe;
        border: 1px solid var(--emp-line);
        border-radius: 8px;
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
    $matrixStatus = $diagnosis['matrix_status'] ?? 'matrix_not_configured';
    $matrixVersion = $diagnosis['matrix_version'] ?? null;
    $executionState = $tde['execution_state'] ?? null;
    $isInsufficient = in_array($executionState, ['insufficient_data', 'partial'], true) || str_contains((string) ($pattern['key'] ?? ''), '?');
    $missingParameters = collect($diagnosis['missing_parameters'] ?? [])->filter()->values();
    $displayForecastSummary = $isInsufficient ? 'Belum siap' : $forecastSummary;
    $displayForecastBasis = $isInsufficient ? 'TDE membutuhkan minimal dua sampel untuk parameter AT, RH, DP, DPS, dan AP dalam window evaluasi.' : $forecastBasis;
    $displayEstimatedTime = $isInsufficient ? 'Belum tersedia' : $estimatedTime;
    $conditionState = $condition['state'] ?? 'UNKNOWN';
    $conditionClass = $conditionState === 'BASAH' ? 'tde-pill-wet' : ($conditionState === 'KERING' ? 'tde-pill-dry' : 'tde-pill-warning');
    $matrixClass = $matrixStatus === 'matched' ? 'tde-pill-dry' : ($matrixStatus === 'matrix_not_matched' ? 'tde-pill-error' : 'tde-pill-warning');
    $symbolClass = fn ($symbol) => $symbol === '<' ? 'tde-symbol-down' : ($symbol === '>' ? 'tde-symbol-up' : ($symbol === '=' ? 'tde-symbol-flat' : 'tde-symbol-missing'));
@endphp

<div class="emp-ui">
<div class="tde-page-header">
    <div class="tde-page-header-row">
        <div>
            <h1 class="tde-page-title">TDE Forecast</h1>
            <p class="tde-page-subtitle">Panel diagnosis tren atmosfer lokal dari time-series station dan Matrix TDE.</p>
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
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-refresh me-1"></i> Refresh
                </button>
            </form>
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
            <div class="note">{{ $isInsufficient ? 'Menunggu data time-series' : $forecastType }}</div>
        </div>
        <div class="tde-forecast-card">
            <div class="label">Perkiraan Waktu</div>
            <div class="value">{{ $displayEstimatedTime }}</div>
            <div class="note">Waktu evaluasi {{ isset($window['to']) ? \Illuminate\Support\Carbon::parse($window['to'])->format('Y-m-d H:i') : '-' }}</div>
        </div>
        <div class="tde-forecast-card">
            <div class="label">Matrix TDE</div>
            <div><span class="tde-pill {{ $matrixClass }}"><i class="bx bx-grid-alt"></i>{{ \Illuminate\Support\Str::headline($matrixStatus) }}</span></div>
            <div class="note">{{ $matrixVersion ? ($matrixVersion['matrix_code'].' / '.$matrixVersion['name']) : 'Matrix belum dipilih' }}</div>
        </div>
    </div>

    @if($isInsufficient)
        <div class="tde-empty-panel mb-3">
            <div class="tde-empty-title">Data TDE belum cukup untuk diagnosis</div>
            <div class="tde-empty-copy">{{ $displayForecastBasis }}</div>
            <div class="tde-empty-grid">
                <div class="tde-empty-item">
                    <strong>{{ $window['reading_count'] ?? 0 }} readings</strong>
                    <span>Data yang terbaca di window saat ini</span>
                </div>
                <div class="tde-empty-item">
                    <strong>{{ $window['minutes'] ?? '-' }} menit</strong>
                    <span>Window evaluasi aktif</span>
                </div>
                <div class="tde-empty-item">
                    <strong>{{ $missingParameters->isNotEmpty() ? $missingParameters->implode(', ') : 'AT, RH, DP, DPS, AP' }}</strong>
                    <span>Parameter yang belum memenuhi syarat</span>
                </div>
                <div class="tde-empty-item">
                    <strong>{{ isset($window['to']) ? \Illuminate\Support\Carbon::parse($window['to'])->format('Y-m-d H:i') : '-' }}</strong>
                    <span>Waktu evaluasi terakhir</span>
                </div>
            </div>
        </div>
    @else
    <div class="row">
        <div class="col-xl-5">
            <div class="card tde-section-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Combination Pattern</h4>
                            <div class="text-muted small">Pattern key: {{ $pattern['key'] ?? '-' }}</div>
                        </div>
                        <span class="tde-pill tde-pill-info">{{ $window['minutes'] ?? '-' }} min window</span>
                    </div>
                    <div class="tde-pattern">
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
        <div class="col-xl-7">
            <div class="card tde-section-card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Trend Parameter</h4>
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle tde-parameter-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Parameter</th>
                                    <th>Awal</th>
                                    <th>Akhir</th>
                                    <th>Perubahan</th>
                                    <th>Threshold</th>
                                    <th>Klasifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order as $parameter)
                                    @php($row = $parameters[$parameter] ?? [])
                                    @php($classification = $row['classification'] ?? '?')
                                    <tr>
                                        <td><strong>{{ $parameter }}</strong><div class="text-muted small">{{ $row['unit'] ?? '-' }}</div></td>
                                        <td>{{ $row['value_start'] ?? '-' }}</td>
                                        <td>{{ $row['value_end'] ?? '-' }}</td>
                                        <td>{{ $row['change'] ?? '-' }}</td>
                                        <td>{{ $row['threshold'] ?? '-' }}</td>
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
                            <div class="ops-readonly-field">{{ $window['reading_count'] ?? 0 }} readings</div>
                            <div class="text-muted small mt-1">Data in window</div>
                        </div>
                        <div class="col-md-6">
                            <div class="ops-readonly-field">{{ isset($window['from']) ? \Illuminate\Support\Carbon::parse($window['from'])->format('H:i') : '-' }}</div>
                            <div class="text-muted small mt-1">Window start</div>
                        </div>
                        <div class="col-md-6">
                            <div class="ops-readonly-field">{{ isset($window['to']) ? \Illuminate\Support\Carbon::parse($window['to'])->format('H:i') : '-' }}</div>
                            <div class="text-muted small mt-1">Window end</div>
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
    document.getElementById('tde-station-id')?.addEventListener('change', function () {
        this.form?.submit();
    });
</script>
@endsection
