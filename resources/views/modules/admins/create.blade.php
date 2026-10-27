@extends('layouts.master')

@section('title') Tambah Admin & Hak Akses @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Administration @endslot
@slot('li_2') <a href="{{ route('admins.index') }}">Admin Management</a> @endslot
@slot('title') Tambah Admin & Hak Akses @endslot
@endcomponent

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h4 class="mb-1">Tambah Admin &amp; Hak Akses</h4>
        <p class="text-muted mb-0">Buat akun, tentukan scope client/project, lalu atur menu dan aksi CRUD dalam satu role.</p>
    </div>
    <a href="{{ route('admins.index') }}" class="btn btn-light"><i class="bx bx-arrow-back me-1"></i>Kembali</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Data belum dapat disimpan.</strong>
        <ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('admins.store') }}" id="rbac-admin-form">
    @csrf
    <input type="hidden" name="_admin_form" value="create">

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
                        <div class="col-md-6"><label for="admin-name" class="form-label">Nama <span class="text-danger">*</span></label><input id="admin-name" name="name" class="form-control" value="{{ old('name') }}" required maxlength="255" autocomplete="name"></div>
                        <div class="col-md-6"><label for="admin-email" class="form-label">Email <span class="text-danger">*</span></label><input type="email" id="admin-email" name="email" class="form-control" value="{{ old('email') }}" required maxlength="255" autocomplete="email"></div>
                        <div class="col-md-4"><label for="admin-password" class="form-label">Password <span class="text-danger">*</span></label><input type="password" id="admin-password" name="password" class="form-control" required minlength="8" maxlength="255" autocomplete="new-password"><small class="text-muted">Minimal 8 karakter.</small></div>
                        <div class="col-md-4"><label for="admin-password-confirm" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label><input type="password" id="admin-password-confirm" name="password_confirmation" class="form-control" required minlength="8" maxlength="255" autocomplete="new-password"></div>
                        <div class="col-md-2"><label for="admin-account-type" class="form-label">Jenis Akun</label><select name="account_type" id="admin-account-type" class="form-select"><option value="sentinel" @selected(old('account_type', 'sentinel') === 'sentinel')>Internal</option><option value="client" @selected(old('account_type') === 'client')>Client</option></select></div>
                        <div class="col-md-2"><label for="admin-status" class="form-label">Status</label><select name="status" id="admin-status" class="form-select"><option value="active" @selected(old('status', 'active') === 'active')>Active</option><option value="inactive" @selected(old('status') === 'inactive')>Inactive</option></select></div>
                    </div>
                </section>

                <section data-access-panel="scope" hidden>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="admin-client" class="form-label">Client</label>
                            <select name="client_id" id="admin-client" class="form-select">
                                <option value="">Platform / tanpa client</option>
                                @foreach ($clients as $client)<option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->client_code }} — {{ $client->name }}</option>@endforeach
                            </select>
                            <small class="text-muted">Wajib untuk akun client. Pilih client lebih dahulu untuk memfilter project.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="admin-projects" class="form-label">Project yang dapat diakses</label>
                            <select name="project_ids[]" id="admin-projects" class="form-select" multiple size="7" aria-describedby="project-help">
                                @foreach ($projects as $project)<option value="{{ $project->id }}" data-client-id="{{ $project->client_id }}" @selected(in_array($project->id, old('project_ids', [])))>{{ $project->project_code }} — {{ $project->name }}{{ $project->client ? ' ('.$project->client->name.')' : '' }}</option>@endforeach
                            </select>
                            <small id="project-help" class="text-muted">Kosongkan hanya untuk role internal yang berlaku ke semua project.</small>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0"><i class="bx bx-info-circle me-1"></i>Jika project dipilih, role dan seluruh data/peta pengguna dibatasi hanya ke project tersebut.</div>
                </section>

                <section data-access-panel="access" hidden>
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-3"><label for="role-mode" class="form-label">Sumber Role</label><select name="role_mode" id="role-mode" class="form-select"><option value="new" @selected(old('role_mode', 'new') === 'new')>Buat role baru</option><option value="existing" @selected(old('role_mode') === 'existing')>Gunakan role internal</option></select></div>
                        <div class="col-md-4 role-new-field"><label for="role-name" class="form-label">Kode Role</label><input id="role-name" name="role_name" class="form-control" value="{{ old('role_name') }}" placeholder="contoh: lumajang-operator" maxlength="255"><small class="text-muted">Huruf, angka, titik, strip, dan underscore.</small></div>
                        <div class="col-md-4 role-new-field"><label for="role-display-name" class="form-label">Nama Role</label><input id="role-display-name" name="role_display_name" class="form-control" value="{{ old('role_display_name') }}" placeholder="Contoh: Operator Lumajang" maxlength="255"></div>
                        <div class="col-md-8 role-existing-field" hidden><label for="role-id" class="form-label">Role Internal</label><select name="role_id" id="role-id" class="form-select"><option value="">Pilih role</option>@foreach ($roles as $role)<option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->display_name ?: $role->name }}</option>@endforeach</select></div>
                        <div class="col-md-4 role-new-field"><div class="form-check form-switch border rounded p-3"><input type="hidden" name="full_access" value="0"><input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="full-access" name="full_access" value="1" @checked(old('full_access'))><label class="form-check-label" for="full-access"><strong>Akses penuh</strong><small class="d-block text-muted">Centang semua menu dan CRUD.</small></label></div></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2"><div><h5 class="mb-1">Matriks Menu &amp; CRUD</h5><p class="text-muted mb-0 small">Centang <em>Lihat</em> agar menu tampil. Aksi CRUD hanya aktif untuk resource yang diizinkan.</p></div><button class="btn btn-sm btn-outline-primary" type="button" id="select-all-access">Pilih semua</button></div>
                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover align-middle mb-0" id="access-matrix">
                            <thead class="table-light"><tr><th class="ps-3">Menu</th><th>Resource</th><th class="text-center">Lihat</th><th class="text-center">Tambah</th><th class="text-center">Ubah</th><th class="text-center">Hapus</th></tr></thead>
                            <tbody>
                            @forelse ($menus as $menu)
                                @php($oldActions = old('permissions.'.$menu->resource, []))
                                <tr>
                                    <td class="ps-3"><strong>{{ $menu->label }}</strong><small class="d-block text-muted">{{ $menu->route_name ?: $menu->code }}</small></td>
                                    <td><code>{{ $menu->resource ?: '—' }}</code></td>
                                    <td class="text-center"><input class="form-check-input access-check menu-check" type="checkbox" name="menu_ids[]" value="{{ $menu->id }}" aria-label="Lihat {{ $menu->label }}" @checked(in_array($menu->id, old('menu_ids', [])))></td>
                                    @foreach ($actions as $action)
                                        <td class="text-center"><input class="form-check-input access-check" type="checkbox" name="permissions[{{ $menu->resource }}][]" value="{{ $action }}" aria-label="{{ ucfirst($action) }} {{ $menu->label }}" @disabled(!$menu->resource) @checked(in_array($action, $oldActions))></td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Registry menu belum tersedia. Jalankan migration terbaru.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between gap-2"><button class="btn btn-light" type="button" id="previous-access-tab">Kembali</button><div><button class="btn btn-outline-primary me-2" type="button" id="next-access-tab">Lanjut</button><button class="btn btn-primary" type="submit"><i class="bx bx-save me-1"></i>Simpan Admin &amp; Akses</button></div></div>
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
    const projects = document.getElementById('admin-projects');
    const filterProjects = () => Array.from(projects.options).forEach((option) => {
        option.hidden = Boolean(client.value) && option.dataset.clientId !== client.value;
        if (option.hidden) option.selected = false;
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
    roleMode.addEventListener('change', switchRoleMode);
    switchRoleMode();

    const fullAccess = document.getElementById('full-access');
    const matrixChecks = Array.from(document.querySelectorAll('.access-check'));
    fullAccess.addEventListener('change', () => matrixChecks.forEach((check) => check.checked = fullAccess.checked));
    document.getElementById('select-all-access').addEventListener('click', () => {
        const next = matrixChecks.some((check) => !check.checked);
        matrixChecks.forEach((check) => check.checked = next);
        fullAccess.checked = next;
    });
    setTab({{ $errors->any() ? 2 : 0 }});
});
</script>
@endsection
