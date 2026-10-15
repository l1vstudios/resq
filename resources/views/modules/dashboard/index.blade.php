@extends('layouts.master')

@section('title') Dashboard @endsection

@section('css')
<style>
    .dashboard-project-card { height: 100%; transition: transform .18s ease, box-shadow .18s ease; }
    .dashboard-project-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(18, 38, 63, .12) !important; }
    .dashboard-project-card:focus-within { outline: 2px solid #556ee6; outline-offset: 3px; }
    .dashboard-project-card .project-location { min-height: 42px; }
    .dashboard-project-card .project-metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .dashboard-project-card .project-metrics strong { display: block; color: #343a40; font-size: 18px; }
    .dashboard-project-card .project-metrics span { color: #74788d; font-size: 12px; }
    @media (prefers-reduced-motion: reduce) { .dashboard-project-card { transition: none; } }
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
        <p class="text-muted mb-0">Pilih project untuk membuka peta monitoring.</p>
    </div>
    <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ $projects->count() }} Project</span>
</div>

<div class="row g-4">
    @forelse ($projects as $project)
        @php
            $locations = $project->workspaces->map(fn ($workspace) => collect([$workspace->city, $workspace->province])->filter()->unique()->implode(', '))->filter()->unique();
            $active = in_array(strtolower($project->status ?? ''), ['active', 'aktif'], true);
        @endphp
        <div class="col-sm-6 col-xl-4">
            <article class="card dashboard-project-card mb-0">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="avatar-md">
                            <span class="avatar-title rounded bg-primary-subtle text-primary fs-2"><i class="bx bx-map-alt" aria-hidden="true"></i></span>
                        </div>
                        <span class="badge {{ $active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $project->status ?: 'Belum aktif' }}</span>
                    </div>
                    <p class="text-muted small mb-1">{{ $project->project_code }}</p>
                    <h5 class="mb-2"><a href="{{ route('dashboard.projects.map', $project) }}" class="text-dark stretched-link">{{ $project->name }}</a></h5>
                    <p class="text-muted mb-2">{{ $project->client?->name ?: ($project->owner ?: '—') }}</p>
                    <p class="project-location text-muted small mb-3"><i class="bx bx-map-pin me-1" aria-hidden="true"></i>{{ $locations->implode(' · ') ?: 'Lokasi belum ditentukan' }}</p>
                    <div class="project-metrics border-top border-bottom py-3 mt-auto">
                        <div><strong>{{ $project->workspaces_count }}</strong><span>Workspace</span></div>
                        <div><strong>{{ $project->monitoring_stations_count }}</strong><span>Monitoring</span></div>
                        <div><strong>{{ $project->warning_stations_count }}</strong><span>Peringatan</span></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center text-primary fw-medium mt-3">
                        <span>Buka Peta</span><i class="bx bx-right-arrow-alt fs-4" aria-hidden="true"></i>
                    </div>
                </div>
            </article>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bx bx-folder-open display-4 text-muted" aria-hidden="true"></i>
                    <h5 class="mt-3">Belum ada project</h5>
                    <p class="text-muted mb-0">Project yang tersedia untuk akun Anda akan tampil di sini.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
