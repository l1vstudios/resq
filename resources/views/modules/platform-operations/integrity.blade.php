@extends('layouts.master')

@section('title') Station Integrity @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Platform Operations @endslot
@slot('title') Operational Integrity @endslot
@endcomponent

@php
    $stations = collect($stations ?? []);
    $filters = ['Healthy', 'Warning', 'Critical', 'Offline'];
@endphp

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Platform Operations',
    'title' => 'Operational Integrity',
    'subtitle' => 'Station health landing page focused on connectivity, telemetry/data health, device/instrument health, maintenance, and last seen state.',
    'steps' => [
        ['label' => 'All Station Integrity', 'icon' => 'bx bx-list-ul', 'active' => true],
        ['label' => 'Select Station', 'icon' => 'bx bx-search'],
        ['label' => 'Shared Station UI', 'icon' => 'bx bx-desktop'],
    ],
])

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="card-title mb-0">All Station Integrity</h4>
            <div class="ops-filter-row">
                <a href="{{ route('platform-operations.integrity.index') }}" class="btn btn-sm {{ empty($activeFilter) ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
                @foreach($filters as $filter)
                    <a href="{{ route('platform-operations.integrity.index', ['status' => $filter]) }}" class="btn btn-sm {{ $activeFilter === $filter ? 'btn-primary' : 'btn-outline-primary' }}">{{ $filter }}</a>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Station</th>
                        <th>Project</th>
                        <th>Overall</th>
                        <th>Connectivity</th>
                        <th>Telemetry</th>
                        <th>Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stations as $row)
                        <tr>
                            <td>
                                <a href="{{ route('platform-operations.stations.show', [$row['station']['id'], 'tab' => 'integrity']) }}" class="fw-semibold">
                                    {{ $row['station']['station_code'] }}
                                </a>
                                <div class="text-muted small">{{ $row['station']['name'] ?? '-' }}</div>
                            </td>
                            <td>{{ $row['station']['project_code'] ?? '-' }}</td>
                            <td>@include('modules.platform-operations.partials.status-badge', ['status' => $row['integrity']['overall'] ?? 'Warning'])</td>
                            <td>{{ $row['integrity']['components']['connectivity']['status'] ?? '-' }}</td>
                            <td>{{ $row['integrity']['components']['telemetry_data_health']['status'] ?? '-' }}</td>
                            <td>{{ ! empty($row['integrity']['last_seen_at']) ? \Illuminate\Support\Carbon::parse($row['integrity']['last_seen_at'])->diffForHumans() : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No station integrity rows.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
