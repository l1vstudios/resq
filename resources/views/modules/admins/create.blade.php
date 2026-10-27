@extends('layouts.master')

@php
    $isEditing = isset($editingUser);
    $pageTitle = $isEditing ? 'Edit Admin & Hak Akses' : 'Tambah Admin & Hak Akses';
    $accountType = old('account_type', $editingUser->type ?? 'sentinel');
    $clientId = old('client_id', $editingUser->client_id ?? null);
    $projectIds = old('project_ids', $selectedProjectIds);
    $menuIds = old('menu_ids', $selectedMenuIds);
    $permissionValues = old('permissions', $selectedPermissions);
@endphp

@section('title') {{ $pageTitle }} @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Administration @endslot
@slot('li_2') <a href="{{ route('admins.index') }}">Admin Management</a> @endslot
@slot('title') {{ $pageTitle }} @endslot
@endcomponent

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h4 class="mb-1">{{ $pageTitle }}</h4>
        <p class="text-muted mb-0">{{ $isEditing ? 'Perbarui akun, scope client/project, serta matriks akses role.' : 'Buat akun, tentukan scope client/project, lalu atur menu dan aksi CRUD dalam satu role.' }}</p>
    </div>
    <a href="{{ route('admins.index') }}" class="btn btn-light"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Data belum dapat disimpan.</strong>
        <ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $isEditing ? route('admins.update', $editingUser) : route('admins.store') }}" id="rbac-admin-form" class="rbac-editor mx-auto">
    @csrf
    @if($isEditing) @method('PUT') @endif
    <input type="hidden" name="_admin_form" value="create">

    <style>
        .rbac-editor { max-width: 1500px; }
        .rbac-editor .card { border-radius: .75rem; }
        .rbac-editor .form-label { font-weight: 600; }
        .project-picker { max-height: 260px; overflow-y: auto; }
        .project-picker .form-check { border-bottom: 1px solid var(--bs-border-color); padding: .65rem 1rem  .65rem 2.1rem; }
        .project-picker .form-check:last-child { border-bottom: 0; }
        #access-matrix th, #access-matrix td { white-space: nowrap; }
    </style>
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <ul class="nav nav-tabs nav-tabs-custom px-4 pt-3" id="admin-access-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" type="button" data-access-tab="account"><span class="d-none d-sm-inline">1. </span>Akun</button></li>
                <li class="nav-item"><button class="nav-link" type="button" data-access-tab="scope"><span class="d-none d-sm-inline">2. </span>Client &amp; Project</button></li>
                <li class="nav-item"><button class="nav-link" type="button" data-access-tab="access"><span class="d-none d-sm-inline">3. </span>Matriks Akses</button></li>
            </ul>

            <div class="p-4">
                <section data-access-panel="account">
                    <div class="row g-3">
                        <div class="col-lg-6"><label for="admin-name" class="form-label">Nama <span class="text-danger">*</span></label><input id="admin-name" name="name" class="form-control" value="{{ old('name', $editingUser->name ?? '') }}" required maxlength="255" autocomplete="name"></div>
                        <div class="col-lg-6"><label for="admin-email" class="form-label">Email <span class="text-danger">*</span></label><input type="email" id="admin-email" name="email" class="form-control" value="{{ old('email', $editingUser->email ?? '') }}" required maxlength="255" autocomplete="email"></div>
                        <div class="col-md-4"><label for="admin-password" class="form-label">Password {!! $isEditing ? '<span class="text-muted small">(opsional)</span>' : '<span class="text-danger">*</span>' !!}</label><div class="input-group"><input type="password" id="admin-password" name="password" class="form-control" @required(!$isEditing) minlength="8" maxlength="255" autocomplete="new-password"><button class="btn btn-outline-secondary" type="button" data-password-toggle="admin-password" aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan password"><i class="bx bx-show" aria-hidden="true"></i></button></div><small class="text-muted">{{ $isEditing ? 'Kosongkan bila password tidak diubah.' : 'Minimal 8 karakter.' }}</small></div>
                        <div class="col-md-4"><label for="admin-password-confirm" class="form-label">Konfirmasi Password</label><div class="input-group"><input type="password" id="admin-password-confirm" name="password_confirmation" class="form-control" @required(!$isEditing) minlength="8" maxlength="255" autocomplete="new-password"><button class="btn btn-outline-secondary" type="button" data-password-toggle="admin-password-confirm" aria-label="Tampilkan konfirmasi password" aria-pressed="false" title="Tampilkan konfirmasi password"><i class="bx bx-show" aria-hidden="true"></i></button></div></div>
                        <div class="col-md-2"><label for="admin-account-type" class="form-label">Jenis Akun</label><select name="account_type" id="admin-account-type" class="form-select"><option value="sentinel" @selected($accountType === 'sentinel')>Internal</option><option value="client" @selected($accountType === 'client')>Client</option></select></div>
                        <div class="col-md-2"><label for="admin-status" class="form-label">Status</label><select name="status" id="admin-status" class="form-select"><option value="active" @selected(old('status', $editingUser->status ?? 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $editingUser->status ?? '') === 'inactive')>Inactive</option></select></div>
                    </div>
                </section>

                <section data-access-panel="scope" hidden>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="admin-client" class="form-label">Client</label>
                            <select name="client_id" id="admin-client" class="form-select">
                                <option value="">Platform / tanpa client</option>
                                @foreach ($clients as $client)<option value="{{ $client->id }}" @selected((string) $clientId === (string) $client->id)>{{ $client->client_code }} — {{ $client->name }}</option>@endforeach
                            </select>
                            <small class="text-muted">Wajib untuk akun client. Pilih client lebih dahulu untuk memfilter project.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Project yang dapat diakses</label>
                            <div id="admin-projects" class="project-picker border rounded" aria-describedby="project-help">
                                @forelse ($projects as $project)
                                    <label class="form-check project-option" data-client-id="{{ $project->client_id }}">
                                        <input class="form-check-input project-check" type="checkbox" name="project_ids[]" value="{{ $project->id }}" @checked(in_array($project->id, $projectIds))>
                                        <span class="form-check-label"><strong>{{ $project->project_code }}</strong><span class="d-block text-muted small">{{ $project->name }}{{ $project->client ? ' · '.$project->client->name : '' }}</span></span>
                                    </label>
                                @empty
                                    <p class="text-muted small p-3 mb-0">Belum ada project.</p>
                                @endforelse
                            </div>
                            <small id="project-help" class="text-muted">Kosongkan hanya untuk role internal yang berlaku ke semua project.</small>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0"><i class="bx bx-info-circle me-1"></i>Jika project dipilih, role dan seluruh data/peta pengguna dibatasi hanya ke project tersebut.</div>
                </section>

                <section data-access-panel="access" hidden>
                    <div class="row g-3 align-items-start mb-4">
                        @if($isEditing)
                            <input type="hidden" name="role_id" value="{{ $editingRole->id }}">
                            <div class="col-lg-8"><div class="border rounded bg-light-subtle px-3 py-2"><small class="text-muted d-block">Role yang sedang diedit</small><strong>{{ $editingRole->display_name ?: $editingRole->name }}</strong><span class="text-muted ms-2 small">({{ $editingRole->name }})</span></div></div>
                        @else
                            <div class="col-lg-3"><label for="role-mode" class="form-label">Sumber Role</label><select name="role_mode" id="role-mode" class="form-select"><option value="new" @selected(old('role_mode', 'new') === 'new')>Buat role baru</option><option value="existing" @selected(old('role_mode') === 'existing')>Gunakan role internal</option></select></div>
                            <div class="col-lg-3 role-new-field"><label for="role-name" class="form-label">Kode Role</label><input id="role-name" name="role_name" class="form-control" value="{{ old('role_name') }}" placeholder="contoh: operator-project" maxlength="255"><small class="text-muted">Huruf, angka, titik, strip, dan underscore.</small></div>
                            <div class="col-lg-3 role-new-field"><label for="role-display-name" class="form-label">Nama Role</label><input id="role-display-name" name="role_display_name" class="form-control" value="{{ old('role_display_name') }}" placeholder="Contoh: Operator Project" maxlength="255"></div>
                            <div class="col-lg-6 role-existing-field" hidden><label for="role-id" class="form-label">Role Internal</label><select name="role_id" id="role-id" class="form-select"><option value="">Pilih role</option>@foreach ($roles as $role)<option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->display_name ?: $role->name }}</option>@endforeach</select></div>
                        @endif
                        <div class="{{ $isEditing ? 'col-lg-4 d-flex' : 'col-lg-3' }}">
                            @unless($isEditing)<label class="form-label d-block">&nbsp;</label>@endunless
                            <div class="form-check form-switch border rounded px-3 py-2 {{ $isEditing ? 'w-100 h-100 d-flex align-items-center' : '' }}"><input type="hidden" name="full_access" value="0"><input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="full-access" name="full_access" value="1" @checked(old('full_access', $editingRole->is_super_admin ?? false))><label class="form-check-label" for="full-access"><strong>Akses penuh</strong><small class="d-block text-muted">Semua menu &amp; CRUD.</small></label></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2"><div><h5 class="mb-1">Matriks Menu &amp; CRUD</h5><p class="text-muted mb-0 small">Daftar ini mengikuti menu yang tampil pada sidebar. Centang <em>Lihat</em> agar menu tampil.</p></div><button class="btn btn-sm btn-outline-primary" type="button" id="select-all-access">Pilih semua</button></div>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover align-middle mb-0" id="access-matrix">
                            <thead class="table-light"><tr><th class="ps-3">Menu Sidebar</th><th class="text-center">Lihat</th><th class="text-center">Tambah</th><th class="text-center">Ubah</th><th class="text-center">Hapus</th></tr></thead>
                            <tbody>
                            @forelse ($menus as $menu)
                                @php($oldActions = $permissionValues[$menu->resource] ?? [])
                                <tr>
                                    <td class="ps-3"><strong>{{ $menu->label }}</strong></td>
                                    <td class="text-center"><input class="form-check-input access-check menu-check" type="checkbox" name="menu_ids[]" value="{{ $menu->id }}" aria-label="Lihat {{ $menu->label }}" @checked(in_array($menu->id, $menuIds))></td>
                                    @foreach ($actions as $action)
                                        <td class="text-center"><input class="form-check-input access-check" type="checkbox" name="permissions[{{ $menu->resource }}][]" value="{{ $action }}" aria-label="{{ ucfirst($action) }} {{ $menu->label }}" @disabled(!$menu->resource) @checked(in_array($action, $oldActions))></td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Registry menu sidebar belum tersedia. Jalankan migration terbaru.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between gap-2"><button class="btn btn-light" type="button" id="previous-access-tab">Kembali</button><div><button class="btn btn-outline-primary me-2" type="button" id="next-access-tab">Lanjut</button><button class="btn btn-primary" type="submit"><i class="bx bx-save me-1"></i>{{ $isEditing ? 'Simpan Perubahan' : 'Simpan Admin & Akses' }}</button></div></div>
    </div>
</form>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = Array.from(document.querySelectorAll('[data-access-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-access-panel]'));
    let active = 0;
    const setTab = (index) => {
        active = Math.max(0, Math.min(index, tabs.length - 1));
        tabs.forEach((tab, position) => tab.classList.toggle('active', position === active));
        panels.forEach((panel, position) => panel.hidden = position !== active);
        document.getElementById('previous-access-tab').disabled = active === 0;
        document.getElementById('next-access-tab').hidden = active === tabs.length - 1;
    };
    tabs.forEach((tab, index) => tab.addEventListener('click', () => setTab(index)));
    document.getElementById('previous-access-tab').addEventListener('click', () => setTab(active - 1));
    document.getElementById('next-access-tab').addEventListener('click', () => setTab(active + 1));

    const client = document.getElementById('admin-client');
    const projects = Array.from(document.querySelectorAll('.project-option'));
    const filterProjects = () => projects.forEach((option) => {
        option.hidden = Boolean(client.value) && option.dataset.clientId !== client.value;
        if (option.hidden) option.querySelector('.project-check').checked = false;
    });
    client.addEventListener('change', filterProjects);
    filterProjects();

    const roleMode = document.getElementById('role-mode');
    const roleNew = document.querySelectorAll('.role-new-field');
    const roleExisting = document.querySelectorAll('.role-existing-field');
    const roleName = document.getElementById('role-name');
    const roleId = document.getElementById('role-id');
    const switchRoleMode = () => {
        const isNew = roleMode.value === 'new';
        roleNew.forEach((field) => field.hidden = !isNew);
        roleExisting.forEach((field) => field.hidden = isNew);
        roleName.required = isNew;
        roleId.required = !isNew;
    };
    if (roleMode) {
        roleMode.addEventListener('change', switchRoleMode);
        switchRoleMode();
    }

    const fullAccess = document.getElementById('full-access');
    const matrixChecks = Array.from(document.querySelectorAll('.access-check'));
    fullAccess.addEventListener('change', () => matrixChecks.forEach((check) => check.checked = fullAccess.checked));
    document.getElementById('select-all-access').addEventListener('click', () => {
        const next = matrixChecks.some((check) => !check.checked);
        matrixChecks.forEach((check) => check.checked = next);
        fullAccess.checked = next;
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const icon = button.querySelector('i');
        const label = button.dataset.passwordToggle === 'admin-password'
            ? 'password'
            : 'konfirmasi password';
        button.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!isVisible));
            button.setAttribute('aria-label', `${isVisible ? 'Tampilkan' : 'Sembunyikan'} ${label}`);
            button.title = `${isVisible ? 'Tampilkan' : 'Sembunyikan'} ${label}`;
            icon.classList.toggle('bx-show', isVisible);
            icon.classList.toggle('bx-hide', !isVisible);
        });
    });
    setTab({{ $errors->any() ? 2 : 0 }});
});
</script>
@endsection
