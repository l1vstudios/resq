@extends('layouts.master')

@section('title') Function Configuration @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Client Operations @endslot
@slot('title') Function Configuration @endslot
@endcomponent

@php($stations = collect($stations ?? []))

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Client UI',
    'title' => 'Function Configuration',
    'subtitle' => 'Select a registered Monitoring Station. Available functions depend on station instrumentation capability.',
    'steps' => [
        ['label' => 'Main Menu', 'icon' => 'bx bx-menu'],
        ['label' => 'Select Monitoring Station', 'icon' => 'bx bx-station', 'active' => true],
        ['label' => 'Configure Function', 'icon' => 'bx bx-cog'],
    ],
])

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="card-title mb-0">Select Monitoring Station</h4>
            <span class="text-muted small">Only registered stations with supported instrumentation show configurable functions.</span>
        </div>
        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Station</th>
                        <th>Project</th>
                        <th>Corridor</th>
                        <th>Available Functions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stations as $row)
                        @php($available = collect($row['available_functions'] ?? []))
                        <tr>
                            <td>
                                <strong>{{ $row['station']['station_code'] }}</strong>
                                <div class="text-muted small">{{ $row['station']['name'] }}</div>
                            </td>
                            <td>{{ $row['station']['project_code'] ?? '-' }}</td>
                            <td>{{ $row['station']['corridor_code'] ?? '-' }}</td>
                            <td>
                                @forelse($available as $function)
                                    <span class="badge bg-primary-subtle text-primary me-1">{{ $function }}</span>
                                @empty
                                    <span class="text-muted small">No supported function</span>
                                @endforelse
                            </td>
                            <td class="text-end">
                                @if($available->isNotEmpty())
                                    <a href="{{ route('client-operations.function-configuration.stations.show', $row['station']['id']) }}" class="btn btn-sm btn-primary">Configure</a>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Configure</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No authorized monitoring stations.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
