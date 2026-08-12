@extends('layouts.master')

@section('title') Client Users @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Client Operations @endslot
@slot('title') Client Users @endslot
@endcomponent

@php
    $users = collect($users ?? []);
    $projects = collect($projects ?? []);
    $roles = collect($roles ?? []);
@endphp

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Client UI',
    'title' => 'Client User Management',
    'subtitle' => 'Manage user identity, account status, and project access permission inside this Client boundary.',
    'steps' => [
        ['label' => 'Master Client', 'icon' => 'bx bx-buildings'],
        ['label' => 'Client Users', 'icon' => 'bx bx-group', 'active' => true],
        ['label' => 'Access Permission', 'icon' => 'bx bx-lock-alt'],
    ],
])

<div class="row">
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Master Client</p><h4>{{ $client->client_code }}</h4><div class="ops-context-line">{{ $client->name }}</div></div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Operational Users</p><h4>{{ $operationalUserCount }} / {{ $maxOperationalUsers }}</h4><div class="ops-context-line">Recovery accounts are not counted</div></div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">Create Client User</h4>
        <form method="POST" action="{{ route('client-operations.users.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
                <div class="col-md-3 mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
                <div class="col-md-3 mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
                <div class="col-md-3 mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="suspended">Suspended</option><option value="inactive">Inactive</option></select></div>
                <div class="col-md-3 mb-3"><label class="form-label">Role</label><select name="role" class="form-select">@foreach($roles as $role)<option value="{{ $role->name }}">{{ $role->display_name ?? $role->name }}</option>@endforeach</select></div>
                <div class="col-md-3 mb-3"><label class="form-label">Access Level</label><select name="access_level" class="form-select"><option value="viewer">Viewer</option><option value="operator">Operator</option><option value="manager">Manager</option></select></div>
                <div class="col-md-4 mb-3"><label class="form-label">Projects</label><select name="project_ids[]" class="form-select" multiple>@foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->project_code }} - {{ $project->name }}</option>@endforeach</select></div>
                <div class="col-md-2 mb-3 d-flex align-items-end"><button class="btn btn-primary w-100" @disabled($operationalUserCount >= $maxOperationalUsers)>Create</button></div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">Client User Access</h4>
        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light"><tr><th>User</th><th>Status</th><th>Roles</th><th>Projects</th><th>Update</th></tr></thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong><div class="text-muted small">{{ $user->email }}</div></td>
                            <td>@include('modules.platform-operations.partials.status-badge', ['status' => $user->status])</td>
                            <td>{{ $user->roles->pluck('name')->implode(', ') }}</td>
                            <td>{{ $user->projects->pluck('project_code')->implode(', ') ?: 'Client-wide role' }}</td>
                            <td>
                                <form method="POST" action="{{ route('client-operations.users.update', $user) }}" class="d-flex flex-wrap gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="name" value="{{ $user->name }}">
                                    <input type="hidden" name="email" value="{{ $user->email }}">
                                    @foreach($user->projects as $project)
                                        <input type="hidden" name="project_ids[]" value="{{ $project->id }}">
                                    @endforeach
                                    <select name="status" class="form-select form-select-sm" style="width: 130px">
                                        @foreach(['active', 'suspended', 'inactive'] as $status)
                                            <option value="{{ $status }}" @selected($user->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <select name="role" class="form-select form-select-sm" style="width: 160px">
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="access_level" class="form-select form-select-sm" style="width: 120px">
                                        @foreach(['viewer', 'operator', 'manager'] as $level)
                                            <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No client users.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
