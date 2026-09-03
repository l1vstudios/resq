@extends('layouts.master')

@section('title') Station Operations @endsection

@section('css')
<link href="https://dev.sentinelplatform.id/build/libs/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') {{ $operationsTitle ?? 'Platform Operations' }} @endslot
@slot('title') {{ $station->station_code }} @endslot
@endcomponent

@php
    $runtime = $runtime ?? [];
    $latestRows = collect($latest['readings'] ?? []);
    $seriesRows = collect($timeSeries['readings'] ?? []);
    $integrity = $runtime['integrity'] ?? [];
    $administrative = $runtime['administrative'] ?? [];
    $hazard = $runtime['hazard'] ?? [];
    $warning = $runtime['warning_state'] ?? [];
    $latestGroups = $latestRows->groupBy('sensor_code');
    $auditRows = $latestRows->filter(fn ($row) => ($row['register_address'] ?? null) !== null || ($row['raw'] ?? null) !== null)->values();
    $stationContextStations = collect($stationContextStations ?? [$station]);
    $stationContextCorridors = collect($stationContextCorridors ?? []);
    $stationRouteNameForHero = $stationRouteName ?? 'platform-operations.stations.show';
    $stateUrl = route($stationRouteNameForHero, [$station, 'tab' => 'state'], false);
    $integrityUrl = route($stationRouteNameForHero, [$station, 'tab' => 'integrity'], false);
    $administrativeUrl = route($stationRouteNameForHero, [$station, 'tab' => 'administrative'], false);
    $latestUrl = route('runtime.monitoring-stations.latest', $station, false);
    $runtimeUrl = route('runtime.monitoring-stations.show', $station, false);
    $seriesParameterRows = $seriesRows->flatMap(function ($row) {
        $items = collect($row['parameter_values'] ?? []);

        if ($items->isEmpty()) {
            return [[
                'parameter' => $row['parameter'] ?? '-',
                'timestamp' => $row['timestamp'] ?? null,
                'value' => $row['value'] ?? null,
                'unit' => $row['unit'] ?? null,
                'sensor_id' => $row['sensor_id'] ?? null,
                'sensor_code' => $row['sensor_code'] ?? 'Sensor',
                'data_logger_code' => $row['data_logger_code'] ?? null,
            ]];
        }

        return $items->map(fn ($item) => [
            'parameter' => $item['parameter'] ?? $item['label'] ?? ($row['parameter'] ?? '-'),
            'timestamp' => $row['timestamp'] ?? null,
            'value' => $item['value'] ?? null,
            'unit' => $item['unit'] ?? ($row['unit'] ?? null),
            'sensor_id' => $row['sensor_id'] ?? null,
            'sensor_code' => $row['sensor_code'] ?? 'Sensor',
            'data_logger_code' => $row['data_logger_code'] ?? null,
        ]);
    });
    $seriesParameterGroups = $seriesParameterRows->groupBy(fn ($point) => $point['sensor_code'] ?? 'Sensor');
@endphp

<div class="emp-ui" id="ops-station-runtime" data-latest-url="{{ $latestUrl }}" data-runtime-url="{{ $runtimeUrl }}" data-station-code="{{ $station->station_code }}" data-station-name="{{ $station->name }}" data-station-lat="{{ $station->latitude }}" data-station-lng="{{ $station->longitude }}">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => $operationsTitle ?? 'Shared Station UI',
    'title' => $station->station_code.' Station UI',
    'subtitle' => 'One shared station workspace for operational state, operational integrity, administrative monitoring, and authorized future functions.',
    'steps' => [
        ['label' => 'Station Context', 'icon' => 'bx bx-station', 'url' => $activeTab === 'state' ? '#station-context' : $stateUrl.'#station-context', 'active' => $activeTab === 'state'],
        ['label' => 'Current Data', 'icon' => 'bx bx-line-chart', 'url' => $activeTab === 'state' ? '#current-data' : $stateUrl.'#current-data'],
        ['label' => 'Integrity', 'icon' => 'bx bx-shield-quarter', 'url' => $integrityUrl, 'active' => $activeTab === 'integrity'],
        ['label' => 'Administration', 'icon' => 'bx bx-id-card', 'url' => $administrativeUrl, 'active' => $activeTab === 'administrative'],
    ],
])

@include('modules.platform-operations.partials.station-tabs', [
    'station' => $station,
    'activeTab' => $activeTab,
    'permissions' => $permissions,
    'stationRouteName' => $stationRouteName ?? 'platform-operations.stations.show',
    'showFutureTabs' => $showFutureTabs ?? false,
    'functionConfigUrl' => $functionConfigUrl ?? null,
    'functionConfigEnabled' => $functionConfigEnabled ?? false,
    'reportingUrl' => $reportingUrl ?? null,
    'reportingEnabled' => $reportingEnabled ?? false,
    'showReportingFutureTab' => $showReportingFutureTab ?? false,
])

<div class="card ops-context-switcher">
    <div class="card-body">
        <div class="ops-context-title">
            <div>
                <h4 class="card-title mb-1">Operational Context</h4>
                <div class="text-muted small">Pilih corridor dan station yang ingin dipantau dalam project aktif.</div>
            </div>
            <span class="ops-status ops-status-info">{{ $stationContextStations->count() }} station</span>
        </div>
        <div class="row g-3">
            <div class="col-lg-4">
                <label class="form-label">Project</label>
                <div class="ops-readonly-field">{{ $station->project?->project_code ?? '-' }} - {{ $station->project?->name ?? '-' }}</div>
            </div>
            <div class="col-lg-4">
                <label class="form-label" for="ops-context-corridor-filter">Corridor</label>
                <select class="form-select" id="ops-context-corridor-filter">
                    <option value="">All Corridors</option>
                    @foreach($stationContextCorridors as $corridorOption)
                        <option value="{{ $corridorOption->id }}" @selected((int) $corridorOption->id === (int) $station->corridor_id)>
                            {{ $corridorOption->corridor_code }} - {{ $corridorOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4">
                <label class="form-label" for="ops-context-station-select">Monitoring Station</label>
                <select class="form-select" id="ops-context-station-select">
                    @foreach($stationContextStations as $candidateStation)
                        <option
                            value="{{ route($stationRouteNameForHero, [$candidateStation, 'tab' => $activeTab], false) }}"
                            data-corridor-id="{{ $candidateStation->corridor_id }}"
                            @selected((int) $candidateStation->id === (int) $station->id)
                        >
                            {{ $candidateStation->station_code }} - {{ $candidateStation->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="row" id="station-context">
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Station</p><h4>{{ $station->station_code }}</h4><div class="ops-context-line">{{ $station->name }}</div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Project</p><h4>{{ $runtime['station']['project_code'] ?? '-' }}</h4><div class="ops-context-line">{{ $station->workspace?->workspace_code ?? '-' }}</div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <p class="text-muted">Hazard Level</p>
                        <h4 id="ops-hazard-state">@include('modules.platform-operations.partials.status-badge', ['status' => $hazard['state'] ?? 'NORMAL'])</h4>
                        <div class="ops-context-line">{{ $station->corridor?->corridor_code ?? 'No corridor' }}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger ops-kpi-action" id="ops-hazard-detail-button" data-bs-toggle="modal" data-bs-target="#ops-hazard-detail-modal">
                        <i class="bx bx-map-pin me-1"></i> Detail
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Integrity</p><h4 id="ops-integrity-state">@include('modules.platform-operations.partials.status-badge', ['status' => $integrity['overall'] ?? 'Warning'])</h4><div class="ops-context-line" id="ops-last-seen">Last seen {{ isset($integrity['last_seen_at']) && $integrity['last_seen_at'] ? \Illuminate\Support\Carbon::parse($integrity['last_seen_at'])->diffForHumans() : '-' }}</div></div></div>
    </div>
</div>

<div class="modal fade" id="ops-hazard-detail-modal" tabindex="-1" aria-labelledby="ops-hazard-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content ops-hazard-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="ops-hazard-detail-title">Hazard Sensor Detail</h5>
                    <div class="text-muted small">{{ $station->station_code }} / {{ $station->corridor?->corridor_code ?? 'No corridor' }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="ops-hazard-detail-list" id="ops-hazard-detail-list">
                            @forelse(collect($hazard['affected_sensors'] ?? []) as $source)
                                <div class="ops-audit-row">
                                    <div>
                                        <strong>{{ $source['sensor_code'] ?? '-' }}</strong>
                                        <div class="text-muted small">{{ collect($source['parameter_values'] ?? [])->pluck('value_text')->filter()->implode(', ') ?: ($source['basis'] ?? '-') }}</div>
                                        <div class="text-muted small">{{ $source['station_code'] ?? $station->station_code }} / {{ $source['data_logger_code'] ?? '-' }}</div>
                                    </div>
                                    <div class="text-end">
                                        @include('modules.platform-operations.partials.status-badge', ['status' => $source['state'] ?? 'AWAS'])
                                        <div class="text-muted small">Threshold {{ $source['threshold'] ?? '-' }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="ops-empty-state">Tidak ada sensor yang sedang Awas.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="ops-hazard-map-wrap">
                            <div id="ops-hazard-map" class="ops-hazard-map"></div>
                            <div class="ops-map-empty d-none" id="ops-hazard-map-empty">Titik koordinat station belum tersedia.</div>
                        </div>
                        <div class="text-muted small mt-2">Marker merah menandai sensor/station yang sedang berada pada level hazard.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($activeTab === 'state')
    <div class="row">
        <div class="col-12">
            <div class="card" id="current-data">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Current Data</h4>
                            <div class="text-muted small" id="ops-current-summary">{{ $latestRows->count() }} parameter from {{ $latestGroups->count() }} registered sensor group{{ $latestGroups->count() === 1 ? '' : 's' }}</div>
                        </div>
                        <span class="ops-status {{ $latestRows->where('fresh', true)->count() ? 'ops-status-success' : 'ops-status-muted' }}" id="ops-live-status">{{ $latestRows->where('fresh', true)->count() ? 'Live Data' : 'No Fresh Data' }}</span>
                    </div>
                    <div id="ops-current-groups">
                        @forelse($latestGroups as $sensorCode => $rows)
                            <div class="ops-sensor-block mb-3">
                                <div class="ops-sensor-block-head">
                                    <div>
                                    <strong>{{ $sensorCode }}</strong>
                                    <div class="text-muted small">{{ $rows->pluck('data_logger_code')->filter()->unique()->implode(', ') ?: 'Logger not assigned' }} / {{ $rows->count() }} parameter latest reading</div>
                                </div>
                                    <span class="text-muted small">{{ optional($rows->pluck('timestamp')->filter()->map(fn ($time) => \Illuminate\Support\Carbon::parse($time))->sortDesc()->first())->format('d M Y H:i') ?? '-' }}</span>
                                </div>
                                <div class="ops-parameter-grid">
                                    @foreach($rows as $row)
                                        <div class="ops-parameter-card">
                                            <div class="ops-parameter-label">{{ $row['parameter'] }}</div>
                                            <div class="ops-parameter-value">{{ is_numeric($row['value']) ? rtrim(rtrim(number_format((float) $row['value'], 2, '.', ''), '0'), '.') : ($row['value'] ?? '-') }}</div>
                                            <div class="ops-parameter-unit">{{ $row['unit'] ?: 'unit not set' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-3">No current data.</div>
                        @endforelse
                    </div>
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle ops-table mb-0">
                            <thead class="table-light"><tr><th>Parameter</th><th>Value</th><th>Unit</th><th>Freshness</th><th>Timestamp</th></tr></thead>
                            <tbody id="ops-current-table">
                                @forelse($latestRows as $row)
                                    <tr>
                                        <td>{{ $row['parameter'] }}</td>
                                        <td>{{ $row['value'] }}</td>
                                        <td>{{ $row['unit'] }}</td>
                                        <td>@include('modules.platform-operations.partials.status-badge', ['status' => $row['data_freshness'], 'label' => ucfirst($row['data_freshness'])])</td>
                                        <td>{{ $row['timestamp'] ? \Illuminate\Support\Carbon::parse($row['timestamp'])->format('d M Y H:i') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">No current data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card" id="time-series">
                <div class="card-body">
                    <div class="ops-series-header">
                        <div>
                            <h4 class="card-title mb-1">Time-Series</h4>
                            <div class="text-muted small" id="ops-series-summary">{{ $seriesParameterRows->count() }} bounded parameter points loaded from {{ $seriesParameterGroups->count() }} sensor group{{ $seriesParameterGroups->count() === 1 ? '' : 's' }}</div>
                        </div>
                        <div class="ops-series-toolbar">
                            <select class="form-select form-select-sm" id="ops-series-sensor-filter">
                                <option value="">All Sensors</option>
                                @foreach($seriesParameterGroups->keys()->filter()->values() as $sensorCode)
                                    <option value="{{ $sensorCode }}">{{ $sensorCode }}</option>
                                @endforeach
                            </select>
                            <select class="form-select form-select-sm" id="ops-series-parameter-filter">
                                <option value="">All Parameters</option>
                                @foreach($seriesParameterRows->pluck('parameter')->filter()->unique()->values() as $parameter)
                                    <option value="{{ $parameter }}">{{ $parameter }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="ops-series-board" id="ops-series-board">
                        @forelse($seriesParameterGroups as $sensorCode => $points)
                            <div class="ops-series-sensor-block" data-sensor-code="{{ $sensorCode }}">
                                <div class="ops-series-sensor-head">
                                    <div>
                                        <strong>{{ $sensorCode }}</strong>
                                        <div class="text-muted small">{{ $points->pluck('data_logger_code')->filter()->unique()->implode(', ') ?: 'Logger not assigned' }} / {{ $points->pluck('parameter')->filter()->unique()->count() }} parameter</div>
                                    </div>
                                    <span class="text-muted small">{{ $points->pluck('timestamp')->filter()->count() }} point</span>
                                </div>
                                @foreach($points->groupBy('parameter') as $parameter => $parameterPoints)
                                    @php
                                        $parameterValues = $parameterPoints->pluck('value')->filter(fn ($value) => is_numeric($value))->map(fn ($value) => abs((float) $value))->take(30);
                                        $parameterMax = max($parameterValues->max() ?: 1, 1);
                                        $latestPoint = $parameterPoints->sortByDesc('timestamp')->first();
                                    @endphp
                                    <div class="ops-series-parameter-row" data-parameter="{{ $parameter }}">
                                        <div class="ops-series-label">
                                            <strong>{{ $parameter }}</strong>
                                            <span>{{ $latestPoint['unit'] ?? '' }}</span>
                                        </div>
                                        <div class="ops-series-bars">
                                            @forelse($parameterValues as $value)
                                                <span class="ops-spark-bar" style="height: {{ max(8, (int) round(($value / $parameterMax) * 76)) }}px"></span>
                                            @empty
                                                <span class="text-muted small">No points.</span>
                                            @endforelse
                                        </div>
                                        <div class="ops-series-value">
                                            {{ is_numeric($latestPoint['value'] ?? null) ? rtrim(rtrim(number_format((float) $latestPoint['value'], 2, '.', ''), '0'), '.') : ($latestPoint['value'] ?? '-') }}
                                            {{ $latestPoint['unit'] ?? '' }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="ops-empty-state">No time-series points.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Hazard / Event / Warning</h4>
                    <div class="mb-2">Hazard: @include('modules.platform-operations.partials.status-badge', ['status' => $hazard['state'] ?? 'NORMAL'])</div>
                    <div class="mb-2">Activation: <span class="fw-semibold">{{ $warning['activation_status'] ?? 'not_configured' }}</span></div>
                    <div class="text-muted small">{{ $hazard['basis'] ?? 'Runtime state from latest read models.' }}</div>
                    <div class="ops-hazard-source-list mt-3" id="ops-hazard-sources">
                        @forelse(collect($hazard['affected_sensors'] ?? []) as $source)
                            <div class="ops-audit-row">
                                <div>
                                    <strong>{{ $source['sensor_code'] ?? '-' }}</strong>
                                    <div class="text-muted small">{{ collect($source['parameter_values'] ?? [])->pluck('value_text')->filter()->implode(', ') ?: ($source['basis'] ?? '-') }}</div>
                                </div>
                                <div class="text-end">
                                    @include('modules.platform-operations.partials.status-badge', ['status' => $source['state'] ?? 'AWAS'])
                                    <div class="text-muted small">Threshold {{ $source['threshold'] ?? '-' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">No sensor is currently reporting a hazard state.</div>
                        @endforelse
                    </div>
                    <div class="mt-3">
                        <div class="text-muted small fw-bold mb-2">Configured Hazard Acuan</div>
                        <div id="ops-hazard-classifications">
                            @forelse(collect($hazard['configured_classifications'] ?? []) as $classification)
                                <div class="ops-audit-row">
                                    <div>
                                        <strong>{{ $classification['sensor_code'] ?? '-' }} / {{ $classification['parameter'] ?? '-' }}</strong>
                                        <div class="text-muted small">{{ $classification['reading_method'] ?? '-' }} · {{ $classification['threshold_config']['comparison'] ?? 'configuration_only' }}</div>
                                    </div>
                                    <div class="text-end text-muted small">
                                        W {{ $classification['hazard_levels']['WASPADA']['threshold'] ?? '-' }} /
                                        S {{ $classification['hazard_levels']['SIAGA']['threshold'] ?? '-' }} /
                                        A {{ $classification['hazard_levels']['AWAS']['threshold'] ?? '-' }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small">No hazard classification configured.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card" id="mapping-audit">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Sensor Mapping Audit</h4>
                            <div class="text-muted small">Trace raw register data into mapped operational parameters.</div>
                        </div>
                        <select class="form-select form-select-sm ops-audit-select" id="ops-audit-filter">
                            <option value="">All Parameters</option>
                            @foreach($auditRows->pluck('parameter')->filter()->unique()->values() as $parameter)
                                <option value="{{ $parameter }}">{{ $parameter }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle ops-table mb-0">
                            <thead class="table-light"><tr><th>Sensor</th><th>Parameter</th><th>Raw/Register</th><th>Decode</th><th>Mapped Value</th><th>Status</th></tr></thead>
                            <tbody id="ops-audit-table">
                                @forelse($auditRows as $row)
                                    <tr>
                                        <td><strong>{{ $row['sensor_code'] ?? '-' }}</strong><div class="text-muted small">{{ $row['data_logger_code'] ?? '-' }}</div></td>
                                        <td>{{ $row['parameter'] ?? '-' }}<div class="text-muted small">{{ $row['source_parameter'] ?? '-' }}</div></td>
                                        <td>
                                            <div>Address {{ $row['register_address'] ?? '-' }} / index {{ $row['register_index'] ?? '-' }}</div>
                                            <div class="text-muted small">TX <code>{{ $row['modbus_frame']['tx'] ?? '-' }}</code></div>
                                            <div class="text-muted small">RX <code>{{ $row['modbus_frame']['rx'] ?? '-' }}</code></div>
                                            <div class="text-muted small">Words {{ is_array($row['registers'] ?? null) ? implode(', ', $row['registers']) : ($row['raw'] ?? '-') }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $row['value_type'] ?? '-' }} {{ $row['byte_order'] ?? '' }}</div>
                                            <div class="text-muted small">scale {{ $row['scale_factor'] ?? '-' }} + offset {{ $row['offset'] ?? '-' }}</div>
                                        </td>
                                        <td><strong>{{ $row['value_text'] ?? (($row['value'] ?? '-') . ' ' . ($row['unit'] ?? '')) }}</strong></td>
                                        <td>@include('modules.platform-operations.partials.status-badge', ['status' => $row['alert_level'] ?? $row['status'] ?? 'Normal'])</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No mapping audit rows.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Functional / Analytical Output</h4>
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle ops-table mb-0">
                            <thead class="table-light"><tr><th>Function</th><th>Execution State</th><th>Output</th><th>Unresolved Rules</th></tr></thead>
                            <tbody>
                                @foreach(collect($runtime['analytical_outputs'] ?? []) as $output)
                                    <tr>
                                        <td>{{ $output['function'] }}</td>
                                        <td>@include('modules.platform-operations.partials.status-badge', ['status' => $output['execution_state'], 'label' => $output['execution_state']])</td>
                                        <td>{{ $output['output'] ?? '-' }}</td>
                                        <td class="text-muted small">{{ collect($output['unresolved_rules'] ?? [])->take(2)->implode(' ') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@elseif($activeTab === 'integrity')
    <div class="row">
        @foreach(collect($integrity['components'] ?? []) as $name => $component)
            <div class="col-xl-4 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1">{{ \Illuminate\Support\Str::headline($name) }}</p>
                                <h5 class="mb-2">@include('modules.platform-operations.partials.status-badge', ['status' => $component['status'] ?? 'Warning'])</h5>
                            </div>
                            <i class="bx bx-check-shield font-size-24 text-muted"></i>
                        </div>
                        <div class="ops-context-line">{{ $component['basis'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-3">Administrative Monitoring</h4>
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle ops-table mb-0">
                            <tbody>
                                <tr><th>Registration Status</th><td>@include('modules.platform-operations.partials.status-badge', ['status' => $administrative['registration_status'] ?? 'unknown'])</td></tr>
                                <tr><th>Service Status</th><td>{{ $administrative['service_status'] ?? '-' }}</td></tr>
                                <tr><th>Service Period</th><td>{{ $administrative['service_period']['start'] ?? '-' }} - {{ $administrative['service_period']['end'] ?? '-' }}</td></tr>
                                <tr><th>Entitlement</th><td>{{ $administrative['entitlement'] ?? '-' }}</td></tr>
                                <tr><th>Package / Service Status</th><td>{{ $administrative['package_status'] ?? '-' }}</td></tr>
                                <tr><th>Administrative Attention</th><td>{{ $administrative['administrative_attention'] ?? '-' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
</div>
@endsection

@section('script')
<script src="https://dev.sentinelplatform.id/build/libs/leaflet/leaflet.js"></script>
<script>
    (function () {
        const root = document.getElementById('ops-station-runtime');

        if (!root) {
            return;
        }

        const latestUrl = root.dataset.latestUrl;
        const runtimeUrl = root.dataset.runtimeUrl;
        const contextCorridorEl = document.getElementById('ops-context-corridor-filter');
        const contextStationEl = document.getElementById('ops-context-station-select');
        const groupsEl = document.getElementById('ops-current-groups');
        const tableEl = document.getElementById('ops-current-table');
        const summaryEl = document.getElementById('ops-current-summary');
        const liveStatusEl = document.getElementById('ops-live-status');
        const hazardEl = document.getElementById('ops-hazard-state');
        const hazardDetailButtonEl = document.getElementById('ops-hazard-detail-button');
        const hazardDetailListEl = document.getElementById('ops-hazard-detail-list');
        const hazardMapEl = document.getElementById('ops-hazard-map');
        const hazardMapEmptyEl = document.getElementById('ops-hazard-map-empty');
        const hazardModalEl = document.getElementById('ops-hazard-detail-modal');
        const hazardSourcesEl = document.getElementById('ops-hazard-sources');
        const hazardClassificationsEl = document.getElementById('ops-hazard-classifications');
        const integrityEl = document.getElementById('ops-integrity-state');
        const lastSeenEl = document.getElementById('ops-last-seen');
        const seriesBoardEl = document.getElementById('ops-series-board');
        const seriesSensorFilterEl = document.getElementById('ops-series-sensor-filter');
        const seriesParameterFilterEl = document.getElementById('ops-series-parameter-filter');
        const seriesSummaryEl = document.getElementById('ops-series-summary');
        const auditTableEl = document.getElementById('ops-audit-table');
        const auditFilterEl = document.getElementById('ops-audit-filter');
        const previousValues = new Map();
        const seriesLastByKey = new Map();
        const initialSeriesPoints = @json($seriesParameterRows->values());
        const seriesHistory = initialSeriesPoints.map((point) => ({
            key: String(point.sensor_id || point.sensor_code || '') + ':' + String(point.parameter || ''),
            sensor_id: point.sensor_id || null,
            sensor_code: point.sensor_code || 'Sensor',
            data_logger_code: point.data_logger_code || '',
            parameter: point.parameter || '-',
            value: Number(point.value),
            unit: point.unit || '',
            timestamp: point.timestamp || Date.now(),
        })).filter((point) => Number.isFinite(point.value)).slice(-360);
        const stationLocation = {
            station_code: root.dataset.stationCode || '',
            station_name: root.dataset.stationName || '',
            latitude: Number(root.dataset.stationLat),
            longitude: Number(root.dataset.stationLng),
        };
        let hazardSources = @json($hazard['affected_sensors'] ?? []);
        let hazardMap = null;
        let hazardMarkerLayer = null;
        let runtimeTick = 0;

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatNumber(value) {
            const number = Number(value);

            if (!Number.isFinite(number)) {
                return value ?? '-';
            }

            return number.toLocaleString(undefined, {
                maximumFractionDigits: 2,
            });
        }

        function formatDate(value) {
            if (!value) {
                return '-';
            }

            return new Date(value).toLocaleString(undefined, {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit',
            });
        }

        function statusClass(status) {
            const value = String(status || '').toUpperCase();

            if (['HEALTHY', 'NORMAL', 'ACTIVE', 'ONLINE', 'CONNECTED', 'FRESH', 'REGISTERED', 'OK', 'AVAILABLE'].includes(value)) {
                return 'ops-status-success';
            }

            if (['WASPADA', 'WARNING', 'SIAGA', 'DEGRADED', 'PARTIAL', 'UNKNOWN', 'EXPIRING SOON', 'NOT_VALIDATED', 'DRAFT'].includes(value)) {
                return 'ops-status-warning';
            }

            if (['AWAS', 'CRITICAL', 'DANGER', 'ATTENTION', 'NOT REGISTERED'].includes(value)) {
                return 'ops-status-danger';
            }

            if (['OFFLINE', 'INACTIVE', 'EXPIRED', 'SUSPENDED', 'STALE'].includes(value)) {
                return 'ops-status-muted';
            }

            return 'ops-status-info';
        }

        function statusBadge(status, label) {
            return '<span class="ops-status ' + statusClass(status) + '">' + escapeHtml(label || status || 'Unknown') + '</span>';
        }

        function hasCoordinate(item) {
            return Number.isFinite(Number(item?.latitude)) && Number.isFinite(Number(item?.longitude));
        }

        function sourceCoordinate(source) {
            if (hasCoordinate(source)) {
                return {
                    latitude: Number(source.latitude),
                    longitude: Number(source.longitude),
                };
            }

            if (hasCoordinate(stationLocation)) {
                return {
                    latitude: Number(stationLocation.latitude),
                    longitude: Number(stationLocation.longitude),
                };
            }

            return null;
        }

        function sourceValues(source) {
            return Array.isArray(source?.parameter_values)
                ? source.parameter_values.map((item) => item.value_text || item.value).filter(Boolean).join(', ')
                : '';
        }

        function renderHazardDetail(sources) {
            hazardSources = Array.isArray(sources) ? sources : [];

            if (hazardDetailButtonEl) {
                hazardDetailButtonEl.title = hazardSources.length ? 'Lihat sensor hazard di map' : 'Tidak ada sensor hazard aktif';
            }

            if (hazardDetailListEl) {
                hazardDetailListEl.innerHTML = hazardSources.length
                    ? hazardSources.map((source) => {
                        const values = sourceValues(source);
                        const coordinate = sourceCoordinate(source);
                        const coordinateText = coordinate
                            ? formatNumber(coordinate.latitude) + ', ' + formatNumber(coordinate.longitude)
                            : 'Koordinat belum tersedia';

                        return '<div class="ops-audit-row">' +
                            '<div>' +
                                '<strong>' + escapeHtml(source.sensor_code || '-') + '</strong>' +
                                '<div class="text-muted small">' + escapeHtml(values || source.basis || '-') + '</div>' +
                                '<div class="text-muted small">' + escapeHtml(source.station_code || stationLocation.station_code || '-') + ' / ' + escapeHtml(source.data_logger_code || '-') + '</div>' +
                                '<div class="text-muted small">' + escapeHtml(coordinateText) + '</div>' +
                            '</div>' +
                            '<div class="text-end">' + statusBadge(source.state || source.alert_level || source.status || 'AWAS') + '<div class="text-muted small">Threshold ' + escapeHtml(source.threshold || '-') + '</div></div>' +
                        '</div>';
                    }).join('')
                    : '<div class="ops-empty-state">Tidak ada sensor yang sedang Awas.</div>';
            }

            renderHazardMap();
        }

        function ensureHazardMap() {
            if (!hazardMapEl || typeof L === 'undefined') {
                return null;
            }

            if (hazardMap) {
                return hazardMap;
            }

            const initial = hasCoordinate(stationLocation)
                ? [Number(stationLocation.latitude), Number(stationLocation.longitude)]
                : [-2.5, 118];
            hazardMap = L.map(hazardMapEl, {
                scrollWheelZoom: false,
            }).setView(initial, hasCoordinate(stationLocation) ? 14 : 5);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(hazardMap);
            hazardMarkerLayer = L.layerGroup().addTo(hazardMap);

            return hazardMap;
        }

        function renderHazardMap() {
            const map = ensureHazardMap();

            if (!map || !hazardMarkerLayer) {
                return;
            }

            hazardMarkerLayer.clearLayers();
            const points = hazardSources
                .map((source) => ({ source, coordinate: sourceCoordinate(source) }))
                .filter((item) => item.coordinate);

            if (hazardMapEmptyEl) {
                hazardMapEmptyEl.classList.toggle('d-none', points.length > 0);
            }

            if (!points.length) {
                map.setView([-2.5, 118], 5);
                return;
            }

            const icon = L.divIcon({
                className: '',
                html: '<div class="ops-hazard-marker"><i class="bx bx-error-circle"></i></div>',
                iconAnchor: [14, 14],
                popupAnchor: [0, -14],
            });
            const bounds = [];

            points.forEach(({ source, coordinate }) => {
                const latLng = [coordinate.latitude, coordinate.longitude];
                bounds.push(latLng);
                L.marker(latLng, { icon })
                    .bindPopup(
                        '<strong>' + escapeHtml(source.sensor_code || '-') + '</strong><br>' +
                        escapeHtml(sourceValues(source) || source.basis || '-') + '<br>' +
                        'Status: ' + escapeHtml(source.state || source.alert_level || source.status || 'AWAS')
                    )
                    .addTo(hazardMarkerLayer);
            });

            if (bounds.length === 1) {
                map.setView(bounds[0], 15);
            } else {
                map.fitBounds(bounds, { padding: [28, 28], maxZoom: 15 });
            }

            setTimeout(() => map.invalidateSize(), 100);
        }

        function groupBySensor(readings) {
            return readings.reduce((groups, row) => {
                const key = row.sensor_code || 'Sensor';
                groups[key] = groups[key] || [];
                groups[key].push(row);
                return groups;
            }, {});
        }

        function applyContextFilter() {
            if (!contextStationEl) {
                return;
            }

            const corridorId = contextCorridorEl ? contextCorridorEl.value : '';
            const options = Array.from(contextStationEl.options);
            let firstVisible = null;

            options.forEach((option) => {
                const visible = !corridorId || option.dataset.corridorId === corridorId;
                option.hidden = !visible;
                option.disabled = !visible;

                if (visible && !firstVisible) {
                    firstVisible = option;
                }
            });

            if (contextStationEl.selectedOptions[0]?.disabled && firstVisible) {
                contextStationEl.value = firstVisible.value;
            }
        }

        function appendSeriesPoints(readings) {
            const timestamp = Date.now();

            readings.forEach((row) => {
                const numeric = Number(row.value);

                if (!Number.isFinite(numeric)) {
                    return;
                }

                const key = String(row.sensor_id || row.sensor_code || '') + ':' + String(row.parameter || '');
                const signature = JSON.stringify([row.value, row.timestamp]);

                if (seriesLastByKey.get(key) === signature) {
                    return;
                }

                seriesLastByKey.set(key, signature);
                seriesHistory.push({
                    key,
                    sensor_id: row.sensor_id || null,
                    sensor_code: row.sensor_code || 'Sensor',
                    data_logger_code: row.data_logger_code || '',
                    parameter: row.parameter || '-',
                    value: numeric,
                    unit: row.unit || '',
                    timestamp: row.timestamp || timestamp,
                });
            });

            while (seriesHistory.length > 360) {
                seriesHistory.shift();
            }
        }

        function syncSeriesFilterOptions() {
            const sensors = Array.from(new Set(seriesHistory.map((point) => point.sensor_code || 'Sensor'))).sort();
            const parameters = Array.from(new Set(seriesHistory.map((point) => point.parameter || '-'))).sort();

            if (seriesSensorFilterEl) {
                const selected = seriesSensorFilterEl.value;
                seriesSensorFilterEl.innerHTML = '<option value="">All Sensors</option>' + sensors.map((sensor) => (
                    '<option value="' + escapeHtml(sensor) + '"' + (sensor === selected ? ' selected' : '') + '>' + escapeHtml(sensor) + '</option>'
                )).join('');

                if (selected && !sensors.includes(selected)) {
                    seriesSensorFilterEl.value = '';
                }
            }

            if (seriesParameterFilterEl) {
                const selected = seriesParameterFilterEl.value;
                seriesParameterFilterEl.innerHTML = '<option value="">All Parameters</option>' + parameters.map((parameter) => (
                    '<option value="' + escapeHtml(parameter) + '"' + (parameter === selected ? ' selected' : '') + '>' + escapeHtml(parameter) + '</option>'
                )).join('');

                if (selected && !parameters.includes(selected)) {
                    seriesParameterFilterEl.value = '';
                }
            }
        }

        function groupSeriesPoints(points) {
            return points.reduce((groups, point) => {
                const sensor = point.sensor_code || 'Sensor';
                const parameter = point.parameter || '-';

                groups[sensor] = groups[sensor] || {};
                groups[sensor][parameter] = groups[sensor][parameter] || [];
                groups[sensor][parameter].push(point);

                return groups;
            }, {});
        }

        function renderSeries(readings) {
            appendSeriesPoints(readings);
            syncSeriesFilterOptions();

            const sensorFilter = seriesSensorFilterEl ? seriesSensorFilterEl.value : '';
            const parameterFilter = seriesParameterFilterEl ? seriesParameterFilterEl.value : '';
            const points = seriesHistory.filter((point) => {
                const sensorMatches = !sensorFilter || point.sensor_code === sensorFilter;
                const parameterMatches = !parameterFilter || point.parameter === parameterFilter;

                return sensorMatches && parameterMatches;
            });
            const grouped = groupSeriesPoints(points);
            const sensorCount = Object.keys(grouped).length;
            const parameterCount = Array.from(new Set(points.map((point) => point.parameter))).length;

            if (seriesSummaryEl) {
                seriesSummaryEl.textContent = points.length + ' live parameter points buffered from ' + sensorCount + ' sensor group' + (sensorCount === 1 ? '' : 's') + ' / ' + parameterCount + ' parameter' + (parameterCount === 1 ? '' : 's');
            }

            if (seriesBoardEl) {
                seriesBoardEl.innerHTML = sensorCount
                    ? Object.entries(grouped).map(([sensorCode, parameters]) => {
                        const loggerCodes = Array.from(new Set(Object.values(parameters).flat().map((point) => point.data_logger_code).filter(Boolean))).join(', ') || 'Logger not assigned';
                        const parameterRows = Object.entries(parameters).map(([parameter, parameterPoints]) => {
                            const visiblePoints = parameterPoints.slice(-36);
                            const values = visiblePoints.map((point) => Math.abs(Number(point.value))).filter((value) => Number.isFinite(value));
                            const maxValue = Math.max(...values, 1);
                            const latestPoint = visiblePoints[visiblePoints.length - 1] || {};
                            const key = sensorCode + ':' + parameter + ':series-row';
                            const current = JSON.stringify([latestPoint.value, latestPoint.timestamp]);
                            const changed = previousValues.has(key) && previousValues.get(key) !== current;
                            previousValues.set(key, current);

                            return '<div class="ops-series-parameter-row ' + (changed ? 'ops-value-pulse' : '') + '">' +
                                '<div class="ops-series-label"><strong>' + escapeHtml(parameter) + '</strong><span>' + escapeHtml(latestPoint.unit || '') + '</span></div>' +
                                '<div class="ops-series-bars">' +
                                    visiblePoints.map((point) => {
                                        const height = Math.max(8, Math.round((Math.abs(Number(point.value)) / maxValue) * 76));

                                        return '<span class="ops-spark-bar" title="' + escapeHtml(parameter + ': ' + formatNumber(point.value) + ' ' + (point.unit || '') + ' / ' + formatDate(point.timestamp)) + '" style="height: ' + height + 'px"></span>';
                                    }).join('') +
                                '</div>' +
                                '<div class="ops-series-value">' + escapeHtml(formatNumber(latestPoint.value)) + ' ' + escapeHtml(latestPoint.unit || '') + '</div>' +
                            '</div>';
                        }).join('');

                        return '<div class="ops-series-sensor-block">' +
                            '<div class="ops-series-sensor-head"><div><strong>' + escapeHtml(sensorCode) + '</strong><div class="text-muted small">' + escapeHtml(loggerCodes) + ' / ' + Object.keys(parameters).length + ' parameter</div></div><span class="text-muted small">' + Object.values(parameters).flat().length + ' point</span></div>' +
                            parameterRows +
                        '</div>';
                    }).join('')
                    : '<div class="ops-empty-state">No time-series points for this filter.</div>';
            }
        }

        function renderAudit(readings) {
            if (!auditTableEl) {
                return;
            }

            const selected = auditFilterEl ? auditFilterEl.value : '';
            const auditable = readings.filter((row) => row.raw !== null || row.register_address !== null || row.registers !== null);
            const parameters = Array.from(new Set(auditable.map((row) => row.parameter).filter(Boolean)));

            if (auditFilterEl) {
                const current = auditFilterEl.value;
                auditFilterEl.innerHTML = '<option value="">All Parameters</option>' + parameters.map((parameter) => (
                    '<option value="' + escapeHtml(parameter) + '"' + (parameter === current ? ' selected' : '') + '>' + escapeHtml(parameter) + '</option>'
                )).join('');
            }

            const rows = selected ? auditable.filter((row) => row.parameter === selected) : auditable;
            auditTableEl.innerHTML = rows.length
                ? rows.map((row) => {
                    const registers = Array.isArray(row.registers) ? row.registers.join(', ') : (row.raw ?? '-');
                    const frame = row.modbus_frame || {};
                    const decode = [row.value_type || '-', row.byte_order || ''].join(' ').trim();
                    const scale = row.scale_factor ?? '-';
                    const offset = row.offset ?? '-';

                    return '<tr>' +
                        '<td><strong>' + escapeHtml(row.sensor_code || '-') + '</strong><div class="text-muted small">' + escapeHtml(row.data_logger_code || '-') + '</div></td>' +
                        '<td>' + escapeHtml(row.parameter || '-') + '<div class="text-muted small">' + escapeHtml(row.source_parameter || '-') + '</div></td>' +
                        '<td><div>Address ' + escapeHtml(row.register_address ?? '-') + ' / index ' + escapeHtml(row.register_index ?? '-') + '</div><div class="text-muted small">TX <code>' + escapeHtml(frame.tx || '-') + '</code></div><div class="text-muted small">RX <code>' + escapeHtml(frame.rx || '-') + '</code></div><div class="text-muted small">Words ' + escapeHtml(registers) + '</div></td>' +
                        '<td><div>' + escapeHtml(decode) + '</div><div class="text-muted small">scale ' + escapeHtml(scale) + ' + offset ' + escapeHtml(offset) + '</div></td>' +
                        '<td><strong>' + escapeHtml(row.value_text || (formatNumber(row.value) + ' ' + (row.unit || ''))) + '</strong></td>' +
                        '<td>' + statusBadge(row.alert_level || row.status || 'Normal') + '</td>' +
                    '</tr>';
                }).join('')
                : '<tr><td colspan="6" class="text-center text-muted">No mapping audit rows.</td></tr>';
        }

        function renderLatest(payload) {
            const readings = Array.isArray(payload.readings) ? payload.readings : [];
            const groups = groupBySensor(readings);
            const sensorCount = Object.keys(groups).length;
            const freshCount = readings.filter((row) => row.fresh).length;

            if (summaryEl) {
                summaryEl.textContent = readings.length + ' parameter from ' + sensorCount + ' registered sensor group' + (sensorCount === 1 ? '' : 's');
            }

            if (liveStatusEl) {
                liveStatusEl.className = 'ops-status ' + (freshCount ? 'ops-status-success' : 'ops-status-muted');
                liveStatusEl.textContent = freshCount ? 'Live Data' : 'No Fresh Data';
            }

            if (groupsEl) {
                groupsEl.innerHTML = sensorCount
                    ? Object.entries(groups).map(([sensorCode, rows]) => {
                        const latestTime = rows.map((row) => row.timestamp).filter(Boolean).sort().pop();
                        const loggerCodes = Array.from(new Set(rows.map((row) => row.data_logger_code).filter(Boolean))).join(', ') || 'Logger not assigned';

                        return '<div class="ops-sensor-block mb-3">' +
                            '<div class="ops-sensor-block-head">' +
                                '<div><strong>' + escapeHtml(sensorCode) + '</strong><div class="text-muted small">' + escapeHtml(loggerCodes) + ' / ' + rows.length + ' parameter latest reading</div></div>' +
                                '<span class="text-muted small">' + escapeHtml(formatDate(latestTime)) + '</span>' +
                            '</div>' +
                            '<div class="ops-parameter-grid">' +
                                rows.map((row) => {
                                    const key = row.sensor_id + ':' + row.parameter;
                                    const current = JSON.stringify([row.value, row.timestamp, row.data_freshness]);
                                    const changed = previousValues.has(key) && previousValues.get(key) !== current;
                                    previousValues.set(key, current);

                                    return '<div class="ops-parameter-card ' + (changed ? 'ops-value-pulse' : '') + '">' +
                                        '<div class="ops-parameter-label">' + escapeHtml(row.parameter || '-') + '</div>' +
                                        '<div class="ops-parameter-value">' + escapeHtml(formatNumber(row.value)) + '</div>' +
                                        '<div class="ops-parameter-unit">' + escapeHtml(row.unit || 'unit not set') + '</div>' +
                                    '</div>';
                                }).join('') +
                            '</div>' +
                        '</div>';
                    }).join('')
                    : '<div class="text-center text-muted py-3">No current data.</div>';
            }

            if (tableEl) {
                tableEl.innerHTML = readings.length
                    ? readings.map((row) => (
                        '<tr>' +
                            '<td>' + escapeHtml(row.parameter || '-') + '<div class="text-muted small">' + escapeHtml(row.sensor_code || '-') + '</div></td>' +
                            '<td class="fw-bold">' + escapeHtml(formatNumber(row.value)) + '</td>' +
                            '<td>' + escapeHtml(row.unit || '-') + '</td>' +
                            '<td>' + statusBadge(row.data_freshness, row.data_freshness ? row.data_freshness.charAt(0).toUpperCase() + row.data_freshness.slice(1) : '-') + '</td>' +
                            '<td>' + escapeHtml(formatDate(row.timestamp)) + '</td>' +
                        '</tr>'
                    )).join('')
                    : '<tr><td colspan="5" class="text-center text-muted">No current data.</td></tr>';
            }

            renderSeries(readings);
            renderAudit(readings);
        }

        function updateRuntime(payload) {
            const sources = Array.isArray(payload.hazard?.affected_sensors) ? payload.hazard.affected_sensors : [];

            if (hazardEl) {
                hazardEl.innerHTML = statusBadge(payload.hazard?.state || 'NORMAL');
            }

            renderHazardDetail(sources);

            if (hazardSourcesEl) {
                hazardSourcesEl.innerHTML = sources.length
                    ? sources.map((source) => {
                        const values = sourceValues(source);

                        return '<div class="ops-audit-row">' +
                            '<div><strong>' + escapeHtml(source.sensor_code || '-') + '</strong><div class="text-muted small">' + escapeHtml(values || source.basis || '-') + '</div></div>' +
                            '<div class="text-end">' + statusBadge(source.state || source.alert_level || source.status || 'AWAS') + '<div class="text-muted small">Threshold ' + escapeHtml(source.threshold || '-') + '</div></div>' +
                        '</div>';
                    }).join('')
                    : '<div class="text-muted small">No sensor is currently reporting a hazard state.</div>';
            }

            if (hazardClassificationsEl) {
                const classifications = Array.isArray(payload.hazard?.configured_classifications) ? payload.hazard.configured_classifications : [];
                hazardClassificationsEl.innerHTML = classifications.length
                    ? classifications.map((classification) => {
                        const levels = classification.hazard_levels || {};
                        const comparison = classification.threshold_config?.comparison || 'configuration_only';

                        return '<div class="ops-audit-row">' +
                            '<div><strong>' + escapeHtml((classification.sensor_code || '-') + ' / ' + (classification.parameter || '-')) + '</strong><div class="text-muted small">' + escapeHtml((classification.reading_method || '-') + ' · ' + comparison) + '</div></div>' +
                            '<div class="text-end text-muted small">W ' + escapeHtml(levels.WASPADA?.threshold ?? '-') + ' / S ' + escapeHtml(levels.SIAGA?.threshold ?? '-') + ' / A ' + escapeHtml(levels.AWAS?.threshold ?? '-') + '</div>' +
                        '</div>';
                    }).join('')
                    : '<div class="text-muted small">No hazard classification configured.</div>';
            }

            if (integrityEl) {
                integrityEl.innerHTML = statusBadge(payload.integrity?.overall || 'Warning');
            }

            if (lastSeenEl) {
                lastSeenEl.textContent = 'Last seen ' + (payload.integrity?.last_seen_at ? formatDate(payload.integrity.last_seen_at) : '-');
            }
        }

        async function fetchJson(url) {
            const target = new URL(url, window.location.origin);
            target.searchParams.set('fresh_seconds', '300');
            target.searchParams.set('_', Date.now());
            const response = await fetch(target.toString(), {
                headers: { 'Accept': 'application/json', 'Cache-Control': 'no-store' },
                cache: 'no-store',
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Runtime data gagal dibaca.');
            }

            return payload;
        }

        async function tick() {
            try {
                renderLatest(await fetchJson(latestUrl));
                runtimeTick += 1;

                if (runtimeTick === 1 || runtimeTick % 5 === 0) {
                    updateRuntime(await fetchJson(runtimeUrl));
                }
            } catch (error) {
                if (liveStatusEl) {
                    liveStatusEl.className = 'ops-status ops-status-warning';
                    liveStatusEl.textContent = 'Runtime Delay';
                }
            }
        }

        applyContextFilter();
        renderSeries([]);
        tick();
        renderHazardDetail(hazardSources);

        if (contextCorridorEl) {
            contextCorridorEl.addEventListener('change', applyContextFilter);
        }

        if (contextStationEl) {
            contextStationEl.addEventListener('change', () => {
                if (contextStationEl.value && contextStationEl.value !== window.location.pathname + window.location.search) {
                    window.location.href = contextStationEl.value;
                }
            });
        }

        if (hazardModalEl) {
            hazardModalEl.addEventListener('shown.bs.modal', () => {
                renderHazardMap();
            });
        }

        if (auditFilterEl) {
            auditFilterEl.addEventListener('change', tick);
        }
        if (seriesSensorFilterEl) {
            seriesSensorFilterEl.addEventListener('change', () => renderSeries([]));
        }
        if (seriesParameterFilterEl) {
            seriesParameterFilterEl.addEventListener('change', () => renderSeries([]));
        }
        window.setInterval(tick, 2000);
    })();
</script>
@endsection
