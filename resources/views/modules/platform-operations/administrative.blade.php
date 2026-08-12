@extends('layouts.master')

@section('title') Administrative Monitoring @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Platform Operations @endslot
@slot('title') Administrative Monitoring @endslot
@endcomponent

@php
    $stations = collect($stations ?? []);
    $filters = ['Active', 'Inactive', 'Expiring Soon', 'Attention'];
@endphp

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Platform Operations',
    'title' => 'Administrative Monitoring',
    'subtitle' => 'Project station list for registration status, service status, service period, entitlement, and administrative attention.',
    'steps' => [
        ['label' => 'Administrative Monitoring', 'icon' => 'bx bx-id-card', 'active' => true],
        ['label' => 'Project Station List', 'icon' => 'bx bx-table'],
        ['label' => 'Select Station', 'icon' => 'bx bx-target-lock'],
        ['label' => 'Station UI', 'icon' => 'bx bx-desktop'],
    ],
])

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="card-title mb-0">All Station Administrative Monitoring</h4>
            <div class="ops-filter-row">
                <a href="{{ route('platform-operations.administrative.index') }}" class="btn btn-sm {{ empty($activeFilter) ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
                @foreach($filters as $filter)
                    <a href="{{ route('platform-operations.administrative.index', ['status' => $filter]) }}" class="btn btn-sm {{ $activeFilter === $filter ? 'btn-primary' : 'btn-outline-primary' }}">{{ $filter }}</a>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Station</th>
                        <th>Project</th>
                        <th>Registration</th>
                        <th>Service</th>
                        <th>Period</th>
                        <th>Entitlement</th>
                        <th>Attention</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stations as $row)
                        @php($admin = $row['administrative'] ?? [])
                        <tr>
                            <td>
                                <a href="{{ route('platform-operations.stations.show', [$row['station']['id'], 'tab' => 'administrative']) }}" class="fw-semibold">
                                    {{ $row['station']['station_code'] }}
                                </a>
                                <div class="text-muted small">{{ $row['station']['name'] ?? '-' }}</div>
                            </td>
                            <td>{{ $row['station']['project_code'] ?? '-' }}</td>
                            <td>@include('modules.platform-operations.partials.status-badge', ['status' => $admin['registration_status'] ?? 'unknown'])</td>
                            <td>{{ $admin['service_status'] ?? '-' }}</td>
                            <td>{{ $admin['service_period']['start'] ?? '-' }} - {{ $admin['service_period']['end'] ?? '-' }}</td>
                            <td>{{ $admin['entitlement'] ?? '-' }}</td>
                            <td>{{ $admin['administrative_attention'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No administrative monitoring rows.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
