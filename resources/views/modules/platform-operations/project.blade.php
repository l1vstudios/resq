@extends('layouts.master')

@section('title') Project Operations @endsection

@section('css')
<link href="https://dev.sentinelplatform.id/build/libs/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') {{ $operationsTitle ?? 'Platform Operations' }} @endslot
@slot('title') {{ $project->project_code }} @endslot
@endcomponent

@php
    $runtime = $runtime ?? [];
    $stations = collect($runtime['stations'] ?? []);
    $corridors = collect($corridors ?? []);
    $criticalStations = $stations->filter(fn ($row) => in_array($row['integrity']['overall'] ?? '', ['Critical', 'Offline'], true))->count();
    $showNationalLink = $showNationalLink ?? true;
    $firstCorridor = $corridors->first();
    $firstStation = $stations->first();
@endphp

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => $operationsTitle ?? 'Platform Operations',
    'title' => $project->project_code.' Operational Page',
    'subtitle' => $project->name.' active project context with spatial operational map and corridor monitoring list.',
    'steps' => [
        ['label' => 'Active Project Context', 'icon' => 'bx bx-folder-open', 'url' => '#station-context', 'active' => true],
        ['label' => 'Spatial Map', 'icon' => 'bx bx-map', 'url' => '#ops-project-map'],
        ['label' => 'Corridor List', 'icon' => 'bx bx-list-ul', 'url' => '#ops-corridor-list'],
        ['label' => 'Corridor', 'icon' => 'bx bx-git-branch', 'url' => $firstCorridor['url'] ?? '#ops-corridor-list'],
        ['label' => 'Station UI', 'icon' => 'bx bx-station', 'url' => isset($firstStation['station']['id']) ? route(($showNationalLink ? 'platform-operations' : 'client-operations').'.stations.show', $firstStation['station']['id']) : '#ops-project-map'],
    ],
    'actions' => $showNationalLink ? [
        ['label' => 'National', 'url' => route('platform-operations.index'), 'icon' => 'bx bx-map-alt'],
    ] : [],
])

	<div class="row" id="station-context">
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Project</p><h4>{{ $project->project_code }}</h4><div class="ops-context-line">{{ $project->name }}</div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Stations</p><h4>{{ $stations->count() }}</h4><div class="ops-context-line">{{ $criticalStations }} need attention</div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Corridors</p><h4>{{ $corridors->count() }}</h4><div class="ops-context-line">Operational relationships</div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Last Refresh</p><h4 class="font-size-18">{{ isset($runtime['generated_at']) ? \Illuminate\Support\Carbon::parse($runtime['generated_at'])->format('H:i') : '-' }}</h4><div class="ops-context-line">Runtime read model</div></div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
	        <div class="card" id="ops-corridor-list">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Spatial Operational Map</h4>
                    @if($showNationalLink)
                        <a href="{{ route('platform-operations.index') }}" class="btn btn-sm btn-outline-secondary">National</a>
                    @endif
                </div>
                <div id="ops-project-map" class="ops-map"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Corridor List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle ops-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Corridor</th>
                                <th>Stations</th>
                                <th>Hazard</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($corridors as $corridor)
                                <tr>
                                    <td>
                                        <a class="fw-semibold" href="{{ $corridor['url'] }}">{{ $corridor['corridor_code'] }}</a>
                                        <div class="text-muted small">{{ $corridor['name'] }}</div>
                                    </td>
                                    <td>{{ $corridor['station_count'] }}</td>
                                    <td>@include('modules.platform-operations.partials.status-badge', ['status' => $corridor['hazard_state']])</td>
                                    <td>@include('modules.platform-operations.partials.status-badge', ['status' => $corridor['status']])</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No corridors registered.</td></tr>
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

@section('script')
<script src="https://dev.sentinelplatform.id/build/libs/leaflet/leaflet.js"></script>
@include('modules.platform-operations.partials.map-script', ['mapId' => 'ops-project-map', 'points' => $mapStations ?? [], 'lines' => $mapLines ?? [], 'mapStyle' => 'terrain3d'])
@endsection
