@extends('layouts.master')

@section('title') Dashboard @endsection

@section('css')
<style>
    .dashboard-grid-surface { background: #fff; border: 1px solid #e5eaf0; border-radius: 5px; overflow: hidden; }
    .dashboard-filters { padding: 20px; border-bottom: 1px solid #e5eaf0; }
    .dashboard-filter-grid { display: grid; grid-template-columns: minmax(0, 2fr) repeat(2, minmax(0, 1fr)) auto; gap: 16px; align-items: end; }
    .dashboard-search { min-width: 0; }
    .dashboard-filter-grid .form-label { font-size: 12px; font-weight: 600; margin-bottom: 6px; }
    .dashboard-filter-actions { display: flex; flex-wrap: nowrap; justify-content: flex-end; gap: 8px; }
    .dashboard-filter-actions .btn { white-space: nowrap; }
    .dashboard-project-grid { min-width: 960px; margin: 0; }
    .dashboard-project-grid thead th { background: #f6f8fb; color: #68768a; font-size: 11px; font-weight: 600; letter-spacing: .03em; white-space: nowrap; padding: 14px 16px; border-bottom: 1px solid #e5eaf0; }
    .dashboard-project-grid tbody td { padding: 16px; border-color: #edf0f4; }
    .dashboard-project-grid .project-name { color: #24334a; font-weight: 600; }
    .dashboard-project-grid .project-name:hover { color: #556ee6; }
    .dashboard-project-grid .project-code { display: block; font-size: 11px; color: #74788d; margin-top: 4px; }
    .dashboard-project-grid .project-region { max-width: 250px; font-size: 12px; }
    .dashboard-grid-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding: 16px 20px; border-top: 1px solid #e5eaf0; }
    .dashboard-grid-footer nav { margin-left: auto; }
    .dashboard-grid-footer .pagination { margin: 0; }
    @media (max-width: 991px) {
        .dashboard-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dashboard-search, .dashboard-filter-actions { grid-column: 1 / -1; }
    }
    @media (max-width: 575px) {
        .dashboard-filter-grid { grid-template-columns: 1fr; gap: 12px; }
        .dashboard-search { grid-column: auto; }
        .dashboard-filters, .dashboard-grid-footer { padding: 16px; }
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') RESQ @endslot
@slot('title') Dashboard @endslot
@endcomponent

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="mb-1">Daftar Project</h4>
        <p class="text-muted mb-0">Project diurutkan berdasarkan kondisi sensor paling kritis: Awas, Siaga, Waspada, lalu Normal.</p>
    </div>
    <span id="dashboard-project-count" class="badge bg-primary-subtle text-primary px-3 py-2">{{ $projects->total() }} dari {{ $totalProjects }} Project</span>
</div>

@if ($errors->any())
    <div class="alert alert-warning" role="alert">{{ $errors->first() }}</div>
@endif

<div class="dashboard-grid-surface">
    <form method="GET" action="{{ route('dashboard') }}" class="dashboard-filters" id="dashboard-project-filters" role="search" aria-label="Filter project">
        <div class="dashboard-filter-grid">
            <div class="dashboard-search">
                <label for="project-search" class="form-label">Cari Project</label>
                <input type="search" name="q" id="project-search" class="form-control" value="{{ $filters['q'] ?? '' }}" maxlength="150" placeholder="Nama, kode project, klien, atau pemilik…">
            </div>
            <div>
                <label for="project-province" class="form-label">Provinsi</label>
                <select name="province" id="project-province" class="form-select">
                    <option value="">Semua provinsi</option>
                    @foreach ($provinces as $province)
                        <option value="{{ $province }}" @selected(($filters['province'] ?? '') === $province)>{{ $province }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="project-city" class="form-label">Kabupaten / Kota</label>
                <select name="city" id="project-city" class="form-select">
                    <option value="">Semua kabupaten / kota</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="dashboard-filter-actions">
                <button type="submit" class="btn btn-primary"><i class="bx bx-filter-alt me-1" aria-hidden="true"></i>Terapkan</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>
    <p id="dashboard-project-refresh" class="text-muted small px-3 pt-3 mb-0" role="status">Urutan diperbarui otomatis setiap 5 detik.</p>

    <div id="dashboard-project-results">
        @include('modules.dashboard.partials.project-list')
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('build/js/pages/dashboard-project-list.js') }}?v={{ filemtime(public_path('build/js/pages/dashboard-project-list.js')) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('dashboard-project-results').addEventListener('change', function (event) {
            if (event.target.id === 'project-per-page') document.getElementById('dashboard-project-filters').requestSubmit();
        });
        var listing = new DashboardProjectList({
            container: document.getElementById('dashboard-project-results'),
            count: document.getElementById('dashboard-project-count'),
            status: document.getElementById('dashboard-project-refresh'),
            url: window.location.href
        });
        listing.start();
        window.addEventListener('pagehide', function () { listing.destroy(); });
        window.addEventListener('pageshow', function (event) { if (event.persisted) listing.start(); });
        document.getElementById('project-province').addEventListener('change', function () {
            document.getElementById('project-city').value = '';
            document.getElementById('dashboard-project-filters').requestSubmit();
        });
    });
</script>
@endsection
