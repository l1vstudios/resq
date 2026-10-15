@extends('layouts.master')

@section('title') Dashboard @endsection

@section('css')
<style>
    .dashboard-grid-surface { background: #fff; border: 1px solid #e5eaf0; border-radius: 12px; overflow: hidden; }
    .dashboard-filters { padding: 20px; border-bottom: 1px solid #e5eaf0; }
    .dashboard-filter-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; align-items: end; }
    .dashboard-search { grid-column: span 2; }
    .dashboard-filter-grid .form-label { font-size: 12px; font-weight: 600; margin-bottom: 6px; }
    .dashboard-filter-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 8px; }
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
    @media (max-width: 991px) { .dashboard-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
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
        <p class="text-muted mb-0">Cari project atau filter wilayah untuk membuka peta monitoring.</p>
    </div>
    <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ $projects->total() }} dari {{ $totalProjects }} Project</span>
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

    <div class="table-responsive" tabindex="0" aria-label="Daftar project">
        <table id="dashboard-project-grid" class="table table-hover align-middle dashboard-project-grid">
            <caption class="visually-hidden">Daftar project beserta wilayah, klien, jumlah station, dan tautan peta.</caption>
            <thead>
                <tr>
                    <th scope="col">No.</th><th scope="col">Project</th><th scope="col">Wilayah</th><th scope="col">Klien / Pemilik</th>
                    <th scope="col" class="text-center">Workspace</th><th scope="col" class="text-center">Monitoring</th><th scope="col" class="text-center">Peringatan</th><th scope="col">Status</th><th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $project)
                    @php
                        $locations = $project->workspaces->map(fn ($workspace) => collect([$workspace->city, $workspace->province])->filter()->unique()->implode(', '))->filter()->unique();
                        $active = in_array(strtolower($project->status ?? ''), ['active', 'aktif'], true);
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $projects->firstItem() + $loop->index }}</td>
                        <td><a href="{{ route('dashboard.projects.map', $project) }}" class="project-name">{{ $project->name }}</a><span class="project-code">{{ $project->project_code }}</span></td>
                        <td class="project-region">{{ $locations->implode(' · ') ?: 'Lokasi belum ditentukan' }}</td>
                        <td>{{ $project->client?->name ?: ($project->owner ?: '—') }}</td>
                        <td class="text-center">{{ $project->workspaces_count }}</td>
                        <td class="text-center">{{ $project->monitoring_stations_count }}</td>
                        <td class="text-center">{{ $project->warning_stations_count }}</td>
                        <td><span class="badge {{ $active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $project->status ?: 'Belum aktif' }}</span></td>
                        <td><a href="{{ route('dashboard.projects.map', $project) }}" class="btn btn-sm btn-outline-primary text-nowrap" aria-label="Buka peta {{ $project->name }}"><i class="bx bx-map-alt me-1" aria-hidden="true"></i>Buka Peta</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-5">
                        <i class="bx bx-search-alt display-4 text-muted" aria-hidden="true"></i>
                        <h5 class="mt-3">{{ $totalProjects === 0 ? 'Belum ada project' : ($projects->total() === 0 ? 'Tidak ada project yang sesuai filter' : 'Tidak ada project pada halaman ini') }}</h5>
                        <p class="text-muted mb-0">{{ $totalProjects === 0 ? 'Project yang tersedia untuk akun Anda akan tampil di sini.' : 'Sesuaikan filter atau kembali ke halaman pertama.' }}</p>
                        @if ($totalProjects > 0)
                            <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}" class="btn btn-sm btn-outline-primary mt-3">Halaman Pertama</a>
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="dashboard-grid-footer">
        <div class="d-flex align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="project-per-page" class="text-muted small mb-0 text-nowrap">Baris per halaman</label>
                <select name="per_page" id="project-per-page" form="dashboard-project-filters" class="form-select form-select-sm w-auto">
                    @foreach ([10, 25, 50] as $size)
                        <option value="{{ $size }}" @selected($projects->perPage() === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <small class="text-muted">Menampilkan {{ $projects->firstItem() ?? 0 }}–{{ $projects->lastItem() ?? 0 }} dari {{ $projects->total() }} project</small>
        </div>
        {{ $projects->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('project-per-page').addEventListener('change', function () {
            document.getElementById('dashboard-project-filters').requestSubmit();
        });
        document.getElementById('project-province').addEventListener('change', function () {
            document.getElementById('project-city').value = '';
            document.getElementById('dashboard-project-filters').requestSubmit();
        });
    });
</script>
@endsection
