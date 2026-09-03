@extends('layouts.master')

@section('title') Corridor Monitoring @endsection

@section('css')
<link href="{{ asset('build/libs/leaflet/leaflet.css') }}" rel="stylesheet" type="text/css" />
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') {{ $operationsTitle ?? 'Platform Operations' }} @endslot
@slot('title') {{ $corridor->corridor_code }} @endslot
@endcomponent

@php
    $stations = collect($stations ?? []);
    $stationRows = collect($stationRows ?? []);
    $currentPreview = collect($currentPreview ?? []);
    $seriesPreview = collect($seriesPreview ?? []);
    $projectRoute = $projectRoute ?? route('platform-operations.projects.show', $project);
    $reportUrl = $reportUrl ?? null;
    $firstStationRow = $stationRows->first();
    $stationUiUrl = $firstStationRow['station_url'] ?? (isset($firstStationRow['station']['id']) ? route('platform-operations.stations.show', $firstStationRow['station']['id']) : '#ops-station-list');
@endphp

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => $operationsTitle ?? 'Platform Operations',
    'title' => $corridor->corridor_code.' Corridor Monitoring Page',
    'subtitle' => 'Multi-station corridor workspace with spatial view, station list, current-data preview, short time-series, and contextual functions.',
    'steps' => [
        ['label' => 'Project', 'icon' => 'bx bx-folder', 'url' => $projectRoute],
        ['label' => 'Select Corridor', 'icon' => 'bx bx-map-pin', 'url' => $projectRoute.'#ops-corridor-list'],
        ['label' => 'Corridor Monitoring', 'icon' => 'bx bx-git-branch', 'url' => '#ops-corridor-map', 'active' => true],
        ['label' => 'Station UI', 'icon' => 'bx bx-desktop', 'url' => $stationUiUrl],
    ],
    'actions' => array_values(array_filter([
        $reportUrl ? ['label' => 'Reporting & Export', 'url' => $reportUrl, 'icon' => 'bx bx-file'] : null,
        ['label' => 'Project', 'url' => $projectRoute, 'icon' => 'bx bx-folder-open'],
    ])),
])

<div class="row">
    <div class="col-md-4">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Project</p><h4>{{ $project->project_code }}</h4><div class="ops-context-line">{{ $project->name }}</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Corridor</p><h4>{{ $corridor->corridor_code }}</h4><div class="ops-context-line">{{ $corridor->name }}</div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Stations</p><h4>{{ $stations->count() }}</h4><div class="ops-context-line">Selectable via map or list</div></div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
	        <div class="card" id="ops-station-list">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="card-title mb-0">Corridor Spatial Map</h4>
                    <div class="d-flex gap-2">
                        @if($reportUrl)
                            <a href="{{ $reportUrl }}" class="btn btn-sm btn-outline-primary">Report</a>
                        @endif
                        <a href="{{ $projectRoute }}" class="btn btn-sm btn-outline-secondary">Project</a>
                    </div>
                </div>
                <div id="ops-corridor-map" class="ops-map"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Station List</h4>
                <div class="ops-station-list">
                    <div class="list-group">
                        @forelse($stationRows as $row)
                            <a href="{{ $row['station_url'] ?? route('platform-operations.stations.show', $row['station']['id']) }}" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $row['station']['station_code'] }}</strong>
                                    @include('modules.platform-operations.partials.status-badge', ['status' => $row['hazard']['state'] ?? 'NORMAL'])
                                </div>
                                <div class="text-muted small">{{ $row['station']['name'] ?? '-' }}</div>
                                <div class="small mt-1">Integrity: {{ $row['integrity']['overall'] ?? '-' }}</div>
                            </a>
                        @empty
                            <div class="text-center text-muted py-3">No stations in this corridor.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Current Data Preview</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle ops-table mb-0">
                        <thead class="table-light"><tr><th>Station</th><th>Parameter</th><th>Value</th><th>Freshness</th><th>Time</th></tr></thead>
                        <tbody>
                            @forelse($currentPreview as $reading)
                                <tr>
                                    <td>{{ $reading['station_code'] }}</td>
                                    <td>{{ $reading['parameter'] }}</td>
                                    <td>{{ $reading['value'] }} {{ $reading['unit'] }}</td>
                                    <td>@include('modules.platform-operations.partials.status-badge', ['status' => $reading['data_freshness'], 'label' => ucfirst($reading['data_freshness'])])</td>
                                    <td>{{ $reading['timestamp'] ? \Illuminate\Support\Carbon::parse($reading['timestamp'])->format('d M H:i') : '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No current readings.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Short Time-Series Preview</h4>
                @forelse($seriesPreview as $series)
                    @php
                        $values = collect($series['points'])->pluck('value')->filter(fn ($value) => is_numeric($value))->map(fn ($value) => (float) $value);
                        $max = max($values->max() ?: 1, 1);
                    @endphp
                    <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                        <div>
                            <strong>{{ $series['station_code'] }}</strong>
                            <div class="text-muted small">{{ $series['sensor_code'] }} / {{ $series['parameter'] ?? 'Parameter' }}</div>
                        </div>
                        <div class="ops-spark">
                            @forelse($values as $value)
                                <span class="ops-spark-bar" style="height: {{ max(8, (int) round(($value / $max) * 44)) }}px"></span>
                            @empty
                                <span class="text-muted small">No points</span>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-3">No time-series preview available.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('script')
<script src="{{ asset('build/libs/leaflet/leaflet.js') }}"></script>
@include('modules.platform-operations.partials.map-script', ['mapId' => 'ops-corridor-map', 'points' => $mapStations ?? [], 'lines' => $mapLines ?? [], 'mapStyle' => 'terrain3d'])
@endsection
