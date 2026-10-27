@extends('layouts.master')

@section('title') Admin Management @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Administration @endslot
@slot('title') Admin Management @endslot
@endcomponent

@if (session('message'))
    <div class="alert alert-success" role="status">{{ session('message') }}</div>
@endif

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="mb-1">Admin Management</h4>
        <p class="text-muted mb-0">Daftar akun, role, dan akses project.</p>
    </div>
    <a href="{{ route('admins.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1" aria-hidden="true"></i>Tambah Admin</a>
</div>

<div class="bg-white border rounded-3 overflow-hidden">
    <form method="GET" action="{{ route('admins.index') }}" class="d-flex flex-wrap gap-2 p-3 border-bottom" role="search" aria-label="Cari akun">
        <label for="admin-search" class="visually-hidden">Cari nama atau email</label>
        <input type="search" id="admin-search" name="q" class="form-control w-auto flex-grow-1" value="{{ $search }}" placeholder="Cari nama atau email…" maxlength="150">
        <button type="submit" class="btn btn-outline-primary">Cari</button>
        @if ($search !== '')<a href="{{ route('admins.index') }}" class="btn btn-outline-secondary">Reset</a>@endif
    </form>
    <div class="table-responsive" tabindex="0" aria-label="Data akun">
        <table class="table table-hover align-middle mb-0" id="admin-user-grid">
            <thead class="table-light"><tr><th scope="col" class="ps-3">No.</th><th scope="col">Nama / Email</th><th scope="col">Jenis Akun</th><th scope="col">Role</th><th scope="col">Klien</th><th scope="col">Project</th><th scope="col">Status</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="ps-3 text-muted">{{ $users->firstItem() + $loop->index }}</td>
                        <td><strong>{{ $user->name }}</strong><small class="d-block text-muted">{{ $user->email }}</small></td>
                        <td>{{ $user->isSentinel() ? 'Internal' : ($user->isClientUser() ? 'Client' : '—') }}</td>
                        <td>{{ $user->roles->merge($user->projectRoles)->unique('id')->map(fn ($role) => $role->display_name ?: $role->name)->implode(', ') ?: 'Belum ada role' }}</td>
                        <td>{{ $user->client?->name ?: '—' }}</td>
                        <td>{{ $user->isSentinel() ? 'Semua project' : ($user->projects->pluck('project_code')->implode(', ') ?: ($user->roles->contains('name', 'ClientAdmin') ? 'Semua project klien' : 'Belum ditugaskan')) }}</td>
                        <td><span class="badge {{ $user->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($user->status ?? 'unknown') }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">{{ $search !== '' ? 'Tidak ada akun yang sesuai pencarian.' : 'Belum ada akun.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top">
        <small class="text-muted">Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} akun</small>
        <div>{{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    </div>
</div>

@endsection
