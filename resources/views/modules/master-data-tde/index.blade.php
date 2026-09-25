@extends('layouts.master')

@section('title') Master Data TDE @endsection

@section('css')
<style>
    .tde-dropzone {
        align-items: center;
        background: #f8fbff;
        border: 1px dashed #9fb4cf;
        border-radius: 6px;
        display: flex;
        min-height: 190px;
        padding: 24px;
    }

    .tde-dropzone-icon {
        align-items: center;
        background: #e8f1ff;
        border-radius: 6px;
        color: #2563eb;
        display: inline-flex;
        font-size: 28px;
        height: 56px;
        justify-content: center;
        width: 56px;
    }

    .tde-template-grid th,
    .tde-template-grid td {
        vertical-align: middle;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Configuration @endslot
@slot('title') Master Data TDE @endslot
@endcomponent

<div class="row">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h4 class="card-title mb-3">Import Excel</h4>
                <div class="tde-dropzone mb-3">
                    <div class="w-100 text-center">
                        <span class="tde-dropzone-icon mb-3"><i class="bx bx-cloud-upload"></i></span>
                        <h5 class="font-size-15 mb-1">Drag &amp; drop file Excel</h5>
                        <p class="text-muted mb-3">Format .xlsx atau .xls</p>
                        <button type="button" class="btn btn-outline-primary">
                            <i class="bx bx-file-find me-1"></i> Browse File
                        </button>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary flex-fill">
                        <i class="bx bx-import me-1"></i> Import
                    </button>
                    <button type="button" class="btn btn-light flex-fill">
                        <i class="bx bx-download me-1"></i> Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="card-title mb-1">Grid Data TDE</h4>
                        <p class="text-muted mb-0">Preview UI untuk data hasil import.</p>
                    </div>
                    <div class="input-group" style="max-width: 260px;">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search TDE">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-nowrap align-middle mb-0 tde-template-grid">
                        <thead class="table-light">
                            <tr>
                                <th>Kode TDE</th>
                                <th>Nama TDE</th>
                                <th>Wilayah</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ([
                                ['TDE-001', 'Baseline Evakuasi Padang', 'Padang', 'Evakuasi', 'Draft', '25 Sep 2026'],
                                ['TDE-002', 'Titik Dampak Sungai Utara', 'Agam', 'Hidromet', 'Ready', '24 Sep 2026'],
                                ['TDE-003', 'Area Paparan Pesisir', 'Pariaman', 'Tsunami', 'Review', '23 Sep 2026'],
                            ] as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row[0] }}</td>
                                    <td>{{ $row[1] }}</td>
                                    <td>{{ $row[2] }}</td>
                                    <td>{{ $row[3] }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary">{{ $row[4] }}</span></td>
                                    <td class="text-muted">{{ $row[5] }}</td>
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
