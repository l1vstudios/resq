@extends('layouts.master')

@section('title') Admin Management @endsection

@section('content')
<style>
    #admin-user-grid { table-layout: fixed; min-width: 1120px; }
    #admin-user-grid th { font-size: .82rem; letter-spacing: .01em; text-transform: uppercase; white-space: nowrap; }
    #admin-user-grid td { vertical-align: middle; }
    #admin-user-grid .admin-name { font-weight: 700; line-height: 1.25; }
    #admin-user-grid .admin-email { font-size: .82rem; line-height: 1.25; }
    #admin-user-grid .admin-cell { overflow-wrap: anywhere; }
    .admin-grid-toolbar { max-width: 760px; }
    .admin-actions { min-width: 155px; }
</style>
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
    <form method="GET" action="{{ route('admins.index') }}" class="p-3 border-bottom" role="search" aria-label="Cari akun">
        <div class="admin-grid-toolbar d-flex gap-2">
            <label for="admin-search" class="visually-hidden">Cari nama atau email</label>
            <input type="search" id="admin-search" name="q" class="form-control" value="{{ $search }}" placeholder="Cari nama atau email…" maxlength="150">
            <button type="submit" class="btn btn-primary px-3">Cari</button>
            @if ($search !== '')<a href="{{ route('admins.index') }}" class="btn btn-light border">Reset</a>@endif
        </div>
    </form>
    <div class="table-responsive" tabindex="0" aria-label="Data akun">
        <table class="table table-hover align-middle mb-0" id="admin-user-grid">
            <colgroup><col style="width: 4%"><col style="width: 17%"><col style="width: 9%"><col style="width: 16%"><col style="width: 18%"><col style="width: 13%"><col style="width: 8%"><col style="width: 15%"></colgroup>
            <thead class="table-light"><tr><th scope="col" class="ps-3">No.</th><th scope="col">Nama / Email</th><th scope="col">Jenis Akun</th><th scope="col">Role</th><th scope="col">Klien</th><th scope="col">Project</th><th scope="col">Status</th><th scope="col" class="text-end pe-3">Aksi</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="ps-3 text-muted">{{ $users->firstItem() + $loop->index }}</td>
                        <td class="admin-cell"><div class="admin-name">{{ $user->name }}</div><small class="d-block text-muted admin-email">{{ $user->email }}</small></td>
                        <td>{{ $user->isSentinel() ? 'Internal' : ($user->isClientUser() ? 'Client' : '—') }}</td>
                        <td class="admin-cell">{{ $user->roles->merge($user->projectRoles)->unique('id')->map(fn ($role) => $role->display_name ?: $role->name)->implode(', ') ?: 'Belum ada role' }}</td>
                        <td class="admin-cell">{{ $user->client?->name ?: '—' }}</td>
                        <td class="admin-cell">{{ $user->isSentinel() ? 'Semua project' : ($user->projects->pluck('project_code')->implode(', ') ?: ($user->roles->contains('name', 'ClientAdmin') ? 'Semua project klien' : 'Belum ditugaskan')) }}</td>
                        <td><span class="badge {{ $user->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ ucfirst($user->status ?? 'unknown') }}</span></td>
                        <td class="text-end pe-3">
                            <div class="admin-actions d-flex justify-content-end gap-2" aria-label="Aksi {{ $user->name }}">
                                <a href="{{ route('admins.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-pencil me-1"></i>Edit</a>
                                @if(!auth()->user()->is($user))
                                    <form method="POST" action="{{ route('admins.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Hapus akun {{ addslashes($user->name) }}? Akses project dan role akun ini akan dicabut.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-trash me-1"></i>Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">{{ $search !== '' ? 'Tidak ada akun yang sesuai pencarian.' : 'Belum ada akun.' }}</td></tr>
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
