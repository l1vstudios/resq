@extends('layouts.master')

@section('title') Client Station Operations @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') {{ $operationsTitle ?? 'Client Operations' }} @endslot
@slot('title') {{ $project->project_code }} Stations @endslot
@endcomponent

@php
    $stations = collect($stations ?? []);
    $perspective = $perspective ?? 'integrity';
    $isAdministrative = $perspective === 'administrative';
    $filters = $isAdministrative
        ? ['Active', 'Inactive', 'Expiring Soon', 'Attention']
        : ['Healthy', 'Warning', 'Critical', 'Offline'];
    $heading = $isAdministrative ? 'Project Station Administrative Monitoring' : 'Project Station Integrity';
@endphp

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Client UI',
    'title' => $heading,
    'subtitle' => 'Active project station list for '.$project->project_code.' with Client-authorized operational context only.',
    'steps' => [
        ['label' => 'Active Project', 'icon' => 'bx bx-folder-open'],
        ['label' => 'Project Station List', 'icon' => 'bx bx-table', 'active' => true],
        ['label' => 'Select Station', 'icon' => 'bx bx-search'],
        ['label' => 'Shared Station UI', 'icon' => 'bx bx-desktop'],
    ],
])

<div class="row">
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Active Project</p><h4>{{ $project->project_code }}</h4><div class="ops-context-line">{{ $project->name }}</div></div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Perspective</p><h4>{{ $isAdministrative ? 'Administrative' : 'Integrity' }}</h4><div class="ops-context-line">Project-scoped stations only</div></div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Rows</p><h4>{{ $stations->count() }}</h4><div class="ops-context-line">Filtered operational read model</div></div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="card-title mb-0">{{ $heading }}</h4>
            <div class="ops-filter-row">
                <a href="{{ route('client-operations.projects.stations', [$project, 'tab' => $perspective]) }}" class="btn btn-sm {{ empty($activeFilter) ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
                @foreach($filters as $filter)
                    <a href="{{ route('client-operations.projects.stations', [$project, 'tab' => $perspective, 'status' => $filter]) }}" class="btn btn-sm {{ $activeFilter === $filter ? 'btn-primary' : 'btn-outline-primary' }}">{{ $filter }}</a>
                @endforeach
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light">
                    @if($isAdministrative)
                        <tr>
                            <th>Station</th>
                            <th>Registration</th>
                            <th>Service</th>
                            <th>Period</th>
                            <th>Entitlement</th>
                            <th>Attention</th>
                        </tr>
                    @else
                        <tr>
                            <th>Station</th>
                            <th>Overall</th>
                            <th>Connectivity</th>
                            <th>Telemetry</th>
                            <th>Last Seen</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @forelse($stations as $row)
                        @php($admin = $row['administrative'] ?? [])
                        @php($integrity = $row['integrity'] ?? [])
                        <tr>
                            <td>
                                <a href="{{ $row['station_url'] }}" class="fw-semibold">{{ $row['station']['station_code'] }}</a>
                                <div class="text-muted small">{{ $row['station']['name'] ?? '-' }}</div>
                            </td>
                            @if($isAdministrative)
                                <td>@include('modules.platform-operations.partials.status-badge', ['status' => $admin['registration_status'] ?? 'unknown'])</td>
                                <td>{{ $admin['service_status'] ?? '-' }}</td>
                                <td>{{ $admin['service_period']['start'] ?? '-' }} - {{ $admin['service_period']['end'] ?? '-' }}</td>
                                <td>{{ $admin['entitlement'] ?? '-' }}</td>
                                <td>{{ $admin['administrative_attention'] ?? '-' }}</td>
                            @else
                                <td>@include('modules.platform-operations.partials.status-badge', ['status' => $integrity['overall'] ?? 'Warning'])</td>
                                <td>{{ $integrity['components']['connectivity']['status'] ?? '-' }}</td>
                                <td>{{ $integrity['components']['telemetry_data_health']['status'] ?? '-' }}</td>
                                <td>{{ ! empty($integrity['last_seen_at']) ? \Illuminate\Support\Carbon::parse($integrity['last_seen_at'])->diffForHumans() : '-' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isAdministrative ? 6 : 5 }}" class="text-center text-muted">No stations match this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
