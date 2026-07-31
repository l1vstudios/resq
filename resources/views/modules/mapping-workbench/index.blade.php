@extends('layouts.master')
@section('title') Mapping Workbench @endsection
@section('content')
@component('components.breadcrumb') @slot('li_1') Canonical Data @endslot @slot('title') Mapping Workbench @endslot @endcomponent

@if (! $available)
    <div class="alert alert-warning">Tabel mapping belum tersedia. Jalankan migration terlebih dahulu.</div>
@endif
@if ($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

<div class="row">
    <div class="col-xl-4">
        <div class="card"><div class="card-body">
            <h4 class="card-title">Buat Mapping Draft</h4>
            <p class="text-muted">Profile mewakili keluarga manufacturer/model. Source konkret dipilih saat activation.</p>
            <form method="POST" action="{{ route('mapping-workbench.profiles.store') }}">@csrf
                <div class="mb-3"><label class="form-label" for="profile-name">Nama profile</label><input id="profile-name" name="name" class="form-control" value="{{ old('name') }}" required></div>
                <div class="mb-3"><label class="form-label" for="manufacturer">Manufacturer</label><input id="manufacturer" name="manufacturer" class="form-control" value="{{ old('manufacturer') }}" required></div>
                <div class="mb-3"><label class="form-label" for="device-model">Device model</label><input id="device-model" name="device_model" class="form-control" value="{{ old('device_model') }}" required></div>
                <div class="mb-3"><label class="form-label" for="profile-description">Keterangan</label><textarea id="profile-description" name="description" class="form-control" rows="3">{{ old('description') }}</textarea></div>
                <button class="btn btn-primary" @disabled(! $available)>Buat Draft</button>
            </form>
        </div></div>
    </div>
    <div class="col-xl-8">
        <div class="card"><div class="card-body">
            <h4 class="card-title mb-4">Mapping Profiles</h4>
            <div class="table-responsive"><table class="table align-middle">
                <thead class="table-light"><tr><th>Profile</th><th>Manufacturer / Model</th><th>Versions</th><th>Status terbaru</th><th></th></tr></thead>
                <tbody>@forelse ($profiles as $profile)
                    @php $latest = $profile->versions->sortByDesc('version')->first(); @endphp
                    <tr><td><strong>{{ $profile->name }}</strong><div><code>{{ $profile->profile_key }}</code></div></td><td>{{ $profile->manufacturer }}<div class="text-muted">{{ $profile->device_model }}</div></td><td>{{ $profile->versions_count }}</td><td><span class="badge {{ $latest?->status === 'published' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $latest?->status ?? '—' }} v{{ $latest?->version ?? '—' }}</span><div><small>{{ $latest?->rules->count() ?? 0 }} rule</small></div></td><td><a class="btn btn-sm btn-outline-primary" href="{{ $latest ? route('mapping-workbench.show', $latest) : '#' }}">Buka</a></td></tr>
                @empty <tr><td colspan="5" class="text-center text-muted py-5">Belum ada mapping profile.</td></tr> @endforelse</tbody>
            </table></div>
        </div></div>
    </div>
</div>
@endsection
