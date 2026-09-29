@extends('layouts.master')

@section('title') TDE Forecast @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
<style>
    .tde-forecast-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .tde-forecast-card {
        background: #fff;
        border: 1px solid var(--emp-line);
        border-radius: 8px;
        min-height: 136px;
        padding: 16px;
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
        font-size: 24px;
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
        min-height: 78px;
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
        gap: 10px;
        justify-content: space-between;
    }

    @media (max-width: 1199px) {
        .tde-forecast-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 575px) {
        .tde-forecast-grid { grid-template-columns: 1fr; }
        .tde-pattern { grid-template-columns: repeat(2, minmax(0, 1fr)); }
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
    $conditionState = $condition['state'] ?? 'UNKNOWN';
    $conditionClass = $conditionState === 'BASAH' ? 'tde-pill-wet' : ($conditionState === 'KERING' ? 'tde-pill-dry' : 'tde-pill-warning');
    $matrixClass = $matrixStatus === 'matched' ? 'tde-pill-dry' : ($matrixStatus === 'matrix_not_matched' ? 'tde-pill-error' : 'tde-pill-warning');
    $symbolClass = fn ($symbol) => $symbol === '<' ? 'tde-symbol-down' : ($symbol === '>' ? 'tde-symbol-up' : ($symbol === '=' ? 'tde-symbol-flat' : 'tde-symbol-missing'));
@endphp

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Trend Diagnosis Evaluator',
    'title' => 'TDE Forecast',
    'subtitle' => 'Panel diagnosis tren atmosfer lokal dari data time-series station dan Matrix TDE.',
    'steps' => [
        ['label' => 'Station', 'icon' => 'bx bx-station', 'active' => true],
        ['label' => 'Current Condition', 'icon' => 'bx bx-cloud'],
        ['label' => 'Trend Pattern', 'icon' => 'bx bx-line-chart'],
        ['label' => 'Matrix Diagnosis', 'icon' => 'bx bx-grid-alt'],
    ],
])

<div class="card ops-context-switcher">
    <div class="card-body">
        <div class="tde-station-strip">
            <div>
                <h4 class="card-title mb-1">Monitoring Station</h4>
                <div class="text-muted small">{{ $selectedStation?->project?->project_code ?? '-' }} / {{ $selectedStation?->corridor?->corridor_code ?? 'No corridor' }}</div>
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
            <div class="value">{{ $forecastSummary }}</div>
            <div class="note">{{ $forecastType }}</div>
        </div>
        <div class="tde-forecast-card">
            <div class="label">Perkiraan Waktu</div>
            <div class="value">{{ $estimatedTime }}</div>
            <div class="note">Waktu evaluasi {{ isset($window['to']) ? \Illuminate\Support\Carbon::parse($window['to'])->format('Y-m-d H:i') : '-' }}</div>
        </div>
        <div class="tde-forecast-card">
            <div class="label">Matrix TDE</div>
            <div><span class="tde-pill {{ $matrixClass }}"><i class="bx bx-grid-alt"></i>{{ \Illuminate\Support\Str::headline($matrixStatus) }}</span></div>
            <div class="note">{{ $matrixVersion ? ($matrixVersion['matrix_code'].' / '.$matrixVersion['name']) : 'No matrix selected' }}</div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-5">
            <div class="card">
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
                    <div class="text-muted small mt-3">{{ $forecastBasis }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card">
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
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Diagnosis Detail</h4>
                    <div class="ops-readonly-field mb-2">{{ $forecastSummary }}</div>
                    <div class="text-muted">{{ $forecastBasis }}</div>
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
            <div class="card">
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
</div>
@endsection

@section('script')
<script>
    document.getElementById('tde-station-id')?.addEventListener('change', function () {
        this.form?.submit();
    });
</script>
@endsection
