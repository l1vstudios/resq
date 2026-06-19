@extends('layouts.master')

@section('title') Klaster @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Konfigurasi Proyek @endslot
@slot('title') Klaster @endslot
@endcomponent

@php
    $project = config('resq_dummy.project');
    $clusters = config('resq_dummy.clusters');
@endphp

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Tambah Klaster</h4>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Proyek</label>
                        <select class="form-select">
                            <option>{{ $project['id'] }} - {{ $project['name'] }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Project ID</label>
                        <input type="text" class="form-control" value="{{ $project['id'] }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Klaster</label>
                        <input type="text" class="form-control" placeholder="Klaster 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ancaman Bencana</label>
                        <select class="form-select">
                            <option>Banjir</option>
                            <option>Longsor</option>
                            <option>Tsunami</option>
                            <option>Gempa Bumi</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Provinsi</label>
                            <input type="text" class="form-control" placeholder="Provinsi">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kab/Kota</label>
                            <input type="text" class="form-control" placeholder="Kab/Kota">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penerima Manfaat</label>
                        <input type="number" class="form-control" placeholder="0">
                    </div>
                    <button type="button" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Simpan Klaster
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Daftar Klaster</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID Klaster</th>
                                <th>Project ID</th>
                                <th>Nama Klaster</th>
                                <th>Ancaman</th>
                                <th>Lokasi</th>
                                <th>Stasiun</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clusters as $cluster)
                                <tr>
                                    <td>{{ $cluster['id'] }}</td>
                                    <td>{{ $cluster['project_id'] }}</td>
                                    <td>{{ $cluster['name'] }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $cluster['hazard'] }}</span></td>
                                    <td>{{ $cluster['province'] }} / {{ $cluster['city'] }}</td>
                                    <td>
                                        <div>{{ $cluster['monitoring_station_id'] }}</div>
                                        <div>{{ $cluster['warning_station_id'] }}</div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge {{ $cluster['status'] === 'Danger' ? 'bg-danger' : 'bg-success' }} me-2">
                                            {{ $cluster['status'] }}
                                        </span>
                                        <a href="{{ route('monitoring-stations.index') }}" class="btn btn-info btn-sm">
                                            <i class="bx bx-map-pin me-1"></i> Monitoring
                                        </a>
                                        <a href="{{ route('warning-stations.index') }}" class="btn btn-warning btn-sm">
                                            <i class="bx bx-bell me-1"></i> Warning
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
