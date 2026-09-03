@extends('layouts.master')

@section('title') Platform Operations @endsection

@section('css')
<link href="https://dev.sentinelplatform.id/build/libs/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Sentinel Console @endslot
@slot('title') Platform Operations @endslot
@endcomponent

@php
    $projectSummaries = collect($projectSummaries ?? []);
    $activeProjects = $projectSummaries->whereNotIn('state', ['OFFLINE'])->count();
    $totalStations = $projectSummaries->sum('station_count');
    $flowLinks = $flowLinks ?? [];
@endphp

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Sentinel Console',
    'title' => 'Platform Operations',
    'subtitle' => 'National landing page for project distribution, active project context, operational state, integrity, and administrative monitoring.',
    'steps' => [
        ['label' => 'National', 'icon' => 'bx bx-map-alt', 'url' => $flowLinks['national'] ?? route('platform-operations.index'), 'active' => true],
        ['label' => 'Select Project', 'icon' => 'bx bx-folder-open', 'url' => $flowLinks['select_project'] ?? '#ops-project-list'],
        ['label' => 'Project Context', 'icon' => 'bx bx-radar', 'url' => $flowLinks['project_context'] ?? '#ops-project-list'],
        ['label' => 'Corridor', 'icon' => 'bx bx-git-branch', 'url' => $flowLinks['corridor'] ?? '#ops-project-list'],
        ['label' => 'Station UI', 'icon' => 'bx bx-station', 'url' => $flowLinks['station_ui'] ?? '#ops-project-list'],
    ],
])

<div class="row">
    <div class="col-md-4">
        <div class="card ops-kpi">
            <div class="card-body">
                <p class="text-muted">Projects</p>
                <h4>{{ $projectSummaries->count() }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card ops-kpi">
            <div class="card-body">
                <p class="text-muted">Monitoring Stations</p>
                <h4>{{ $totalStations }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card ops-kpi">
            <div class="card-body">
                <p class="text-muted">Active Project Contexts</p>
                <h4>{{ $activeProjects }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card" id="ops-project-list">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="card-title mb-0">Project Distribution</h4>
                    <span class="badge bg-info-subtle text-info">3D Terrain / Contour GIS</span>
                </div>
                <div id="ops-national-map" class="ops-map ops-map-lg"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Project List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle ops-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Project</th>
                                <th>Area</th>
                                <th>Stations</th>
                                <th>State</th>
                                <th>Last Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projectSummaries as $project)
                                <tr>
                                    <td>
                                        <a href="{{ route('platform-operations.projects.show', $project['id']) }}" class="fw-semibold">
                                            {{ $project['project_code'] }}
                                        </a>
                                        <div class="text-muted small">{{ $project['name'] }}</div>
                                    </td>
                                    <td>{{ $project['area'] }}</td>
                                    <td>{{ $project['station_count'] }}</td>
                                    <td>@include('modules.platform-operations.partials.status-badge', ['status' => $project['state']])</td>
                                    <td>{{ $project['last_update'] ? \Illuminate\Support\Carbon::parse($project['last_update'])->diffForHumans() : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No accessible projects.</td>
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

@section('script')
<script src="https://dev.sentinelplatform.id/build/libs/leaflet/leaflet.js"></script>
@include('modules.platform-operations.partials.map-script', ['mapId' => 'ops-national-map', 'points' => $mapProjects ?? [], 'lines' => $mapLines ?? [], 'mapStyle' => 'terrain3d'])
@endsection
