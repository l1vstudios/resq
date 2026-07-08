@extends('layouts.master')

@section('title') Prefix Sensors @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Device Setup @endslot
@slot('title') Prefix Sensors @endslot
@endcomponent

@php
    $mstPrefixes = collect($mstPrefixes ?? []);
@endphp

@if (session('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Data belum bisa disimpan.</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Prefix Sensors Setup</h4>
                <form method="POST" action="{{ route('mst-prefixes.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Prefix</label>
                        <input name="prefix_code" class="form-control" placeholder="MST-A" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" placeholder="Sensor Kelembapan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option>Active</option>
                            <option>Inactive</option>
                            <option>Maintenance</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save / Update Prefix</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Prefix Sensors List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Prefix</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Sensors</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mstPrefixes as $prefix)
                                <tr>
                                    <td>{{ $prefix['id'] }}</td>
                                    <td>{{ $prefix['name'] ?? '-' }}</td>
                                    <td>{{ $prefix['description'] ?? '-' }}</td>
                                    <td>{{ $prefix['sensors'] ?? 0 }}</td>
                                    <td>
                                        <span class="badge {{ ($prefix['status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $prefix['status'] ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @isset($prefix['db_id'])
                                            <form method="POST" action="{{ route('device-setup.destroy', ['type' => 'mst-prefix', 'id' => $prefix['db_id']]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        @endisset
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada prefix sensors.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
