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
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-admin-modal"><i class="bx bx-plus me-1" aria-hidden="true"></i>Tambah Admin</button>
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
                        <td>{{ $user->roles->map(fn ($role) => $role->display_name ?: $role->name)->implode(', ') ?: 'Belum ada role' }}</td>
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

<div class="modal fade" id="add-admin-modal" tabindex="-1" aria-labelledby="add-admin-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="add-admin-title">Tambah Admin</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <form method="POST" action="{{ route('admins.store') }}">
                @csrf
                <input type="hidden" name="_admin_form" value="create">
                <div class="modal-body">
                    <p class="text-muted small">Buat akun internal dan tentukan role aksesnya.</p>
                    @if ($errors->any() && old('_admin_form') === 'create')
                        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <div class="mb-3"><label for="admin-name" class="form-label">Nama</label><input id="admin-name" name="name" class="form-control" value="{{ old('name') }}" maxlength="255" autocomplete="name" required></div>
                    <div class="mb-3"><label for="admin-email" class="form-label">Email</label><input type="email" id="admin-email" name="email" class="form-control" value="{{ old('email') }}" maxlength="255" autocomplete="email" required></div>
                    <div class="row">
                        <div class="col-sm-6 mb-3"><label for="admin-password" class="form-label">Password</label><input type="password" id="admin-password" name="password" class="form-control" minlength="8" maxlength="255" autocomplete="new-password" required><small class="text-muted">Minimal 8 karakter.</small></div>
                        <div class="col-sm-6 mb-3"><label for="admin-password-confirm" class="form-label">Konfirmasi Password</label><input type="password" id="admin-password-confirm" name="password_confirmation" class="form-control" minlength="8" maxlength="255" autocomplete="new-password" required></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-8 mb-3"><label for="admin-role" class="form-label">Role</label><select name="role_id" id="admin-role" class="form-select" required><option value="">Pilih role</option>@foreach ($roles as $role)<option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->display_name ?: $role->name }}</option>@endforeach</select></div>
                        <div class="col-sm-4 mb-3"><label for="admin-status" class="form-label">Status</label><select name="status" id="admin-status" class="form-select"><option value="active" @selected(old('status', 'active') === 'active')>Active</option><option value="inactive" @selected(old('status') === 'inactive')>Inactive</option></select></div>
                    </div>
                    @if ($roles->isEmpty())<p class="text-warning mb-0">Role internal belum tersedia.</p>@endif
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" @disabled($roles->isEmpty())>Simpan Admin</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
@if ($errors->any() && old('_admin_form') === 'create')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('add-admin-modal')).show();
    });
</script>
@endif
@endsection
