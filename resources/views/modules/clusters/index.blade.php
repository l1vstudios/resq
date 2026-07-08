@extends('layouts.master')

@section('title') Geospatial Workspace @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Registered @endslot
@slot('title') Geospatial Workspace @endslot
@endcomponent

@php
    $clusters = collect($clusters ?? config('resq_dummy.clusters'));
    $totalWorkspaces = $clusters->count();
    $totalProvinces = $clusters->pluck('province')->filter()->unique()->count();
    $totalBeneficiaries = $clusters->sum(fn ($cluster) => (int) ($cluster['beneficiaries'] ?? 0));
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Workspace</p>
                <h4 class="mb-0">{{ $totalWorkspaces }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Provinsi Terdaftar</p>
                <h4 class="mb-0">{{ $totalProvinces }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Penerima Manfaat</p>
                <h4 class="mb-0">{{ number_format($totalBeneficiaries) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Geospatial Workspace Registry</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Workspace ID</th>
                                <th>Project ID</th>
                                <th>Workspace Name</th>
                                <th>Hazard</th>
                                <th>Location</th>
                                <th>Beneficiaries</th>
                                <th>Monitoring</th>
                                <th>Warning</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clusters as $cluster)
                                <tr>
                                    <td>{{ $cluster['id'] ?? '-' }}</td>
                                    <td>{{ $cluster['project_id'] ?? '-' }}</td>
                                    <td>{{ $cluster['name'] ?? '-' }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $cluster['hazard'] ?? '-' }}</span></td>
                                    <td>{{ $cluster['province'] ?? '-' }} / {{ $cluster['city'] ?? '-' }}</td>
                                    <td>{{ number_format((int) ($cluster['beneficiaries'] ?? 0)) }}</td>
                                    <td>{{ $cluster['monitoring_station_id'] ?? '-' }}</td>
                                    <td>{{ $cluster['warning_station_id'] ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ in_array($cluster['status'] ?? '', ['Danger', 'Bahaya', 'Awas']) ? 'bg-danger' : (($cluster['status'] ?? '') === 'Waspada' ? 'bg-warning' : 'bg-success') }}">
                                            {{ $cluster['status'] ?? '-' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Belum ada geospatial workspace.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
