@extends('layouts.master')

@section('title') Canonical Parameter Catalog @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Canonical Data @endslot
@slot('title') Parameter Catalog @endslot
@endcomponent

<div class="row align-items-stretch">
    @foreach ([
        ['key' => 'meteorology', 'label' => 'Meteorology', 'icon' => 'bx-cloud', 'color' => 'info'],
        ['key' => 'hydrology', 'label' => 'Hydrology', 'icon' => 'bx-water', 'color' => 'primary'],
        ['key' => 'geotechnical', 'label' => 'Geotechnical', 'icon' => 'bx-landscape', 'color' => 'warning'],
    ] as $domainCard)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-sm me-3"><span class="avatar-title rounded-circle bg-{{ $domainCard['color'] }} bg-soft text-{{ $domainCard['color'] }}"><i class="bx {{ $domainCard['icon'] }} font-size-24"></i></span></div>
                    <div><p class="text-muted mb-1">{{ $domainCard['label'] }}</p><h4 class="mb-0">{{ $summary[$domainCard['key']] ?? 0 }} parameter</h4></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mt-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
            <div>
                <h4 class="card-title mb-1">Canonical Parameter Catalog</h4>
                <p class="text-muted mb-0">Katalog ini menyatukan arti data perangkat sebelum digunakan oleh fitur Sentinel.</p>
            </div>
            <div>
                <span class="badge bg-success">{{ $summary['active'] ?? 0 }} active</span>
                <span class="badge bg-secondary">{{ $summary['deprecated'] ?? 0 }} deprecated</span>
            </div>
        </div>

        @if ($catalogUnavailable ?? false)
            <div class="alert alert-warning" role="alert">
                Katalog canonical belum tersedia. Jalankan migration dan <code>CanonicalCatalogSeeder</code> terlebih dahulu.
            </div>
        @endif

        <form method="GET" action="{{ route('canonical-catalog.index') }}" class="row g-3 mb-4">
            <div class="col-lg-5">
                <label for="catalog-search" class="form-label">Cari parameter</label>
                <input id="catalog-search" type="search" name="q" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Contoh: air_temperature">
            </div>
            <div class="col-md-4 col-lg-3">
                <label for="catalog-domain" class="form-label">Domain</label>
                <select id="catalog-domain" name="domain" class="form-select">
                    <option value="">Semua domain</option>
                    @foreach (['meteorology' => 'Meteorology', 'hydrology' => 'Hydrology', 'geotechnical' => 'Geotechnical'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['domain'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="catalog-lifecycle" class="form-label">Lifecycle</label>
                <select id="catalog-lifecycle" name="lifecycle" class="form-select">
                    <option value="">Semua status</option>
                    <option value="active" @selected(($filters['lifecycle'] ?? '') === 'active')>Active</option>
                    <option value="deprecated" @selected(($filters['lifecycle'] ?? '') === 'deprecated')>Deprecated</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary" type="submit">Terapkan</button>
                <a class="btn btn-outline-secondary" href="{{ route('canonical-catalog.index') }}">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Stable key</th>
                        <th scope="col">Definisi</th>
                        <th scope="col">Domain</th>
                        <th scope="col">Canonical unit</th>
                        <th scope="col">Tipe / karakteristik</th>
                        <th scope="col">Output</th>
                        <th scope="col">Lifecycle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parameters as $parameter)
                        @php
                            $definition = $parameter->definition;
                            $domainClass = ['meteorology' => 'bg-info', 'hydrology' => 'bg-primary', 'geotechnical' => 'bg-warning text-dark'][$parameter->domain] ?? 'bg-secondary';
                        @endphp
                        <tr>
                            <td><code>{{ $parameter->key }}</code><div><small class="text-muted">definition v{{ $definition?->version ?? '—' }}</small></div></td>
                            <td style="min-width: 320px"><strong>{{ $definition?->display_name ?? '—' }}</strong><div class="text-muted text-wrap">{{ $definition?->definition ?? '—' }}</div></td>
                            <td><span class="badge {{ $domainClass }}">{{ $parameter->domain }}</span></td>
                            <td><code>{{ $definition?->unit?->symbol ?? '—' }}</code><div><small class="text-muted">{{ $definition?->unit?->code ?? '—' }}</small></div></td>
                            <td>{{ $definition?->data_type ?? '—' }}<div><small class="text-muted">{{ $definition?->measurement_characteristic ?? '—' }}</small></div></td>
                            <td>{{ $definition ? $definition->output_precision.' digit' : '—' }}<div><small class="text-muted">{{ $definition?->rounding_mode ?? '—' }}</small></div></td>
                            <td><span class="badge {{ $parameter->lifecycle === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ $parameter->lifecycle }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">Tidak ada parameter canonical yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
