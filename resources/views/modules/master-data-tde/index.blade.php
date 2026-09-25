@extends('layouts.master')

@section('title') Master Data TDE @endsection

@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
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
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="card-title mb-1">Import Excel</h4>
                        <p class="text-muted mb-0">Upload master data TDE dari file Excel.</p>
                    </div>
                    <button type="button" class="btn btn-light">
                        <i class="bx bx-download me-1"></i> Template
                    </button>
                </div>
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
                <button type="button" class="btn btn-primary">
                    <i class="bx bx-import me-1"></i> Import
                </button>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
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
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="tde-grid-body">
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
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm" data-tde-delete data-tde-code="{{ $row[0] }}">
                                            <i class="bx bx-trash me-1"></i> Hapus
                                        </button>
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

@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-tde-delete]');
        if (!button) {
            return;
        }

        var row = button.closest('tr');
        var code = button.dataset.tdeCode || 'data ini';
        var removeRow = function () {
            row?.remove();
            var body = document.getElementById('tde-grid-body');
            if (body && !body.querySelector('tr')) {
                body.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Belum ada master data TDE.</td></tr>';
            }
        };

        if (window.Swal) {
            Swal.fire({
                title: 'Hapus Master Data TDE?',
                text: code + ' akan dihapus dari grid.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, hapus',
                cancelButtonText: 'No',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#74788d'
            }).then(function (result) {
                if (result.isConfirmed) {
                    removeRow();
                    Swal.fire('Terhapus', 'Master Data TDE berhasil dihapus dari grid.', 'success');
                }
            });
            return;
        }

        if (window.confirm('Hapus ' + code + '?')) {
            removeRow();
        }
    });
</script>
@endsection
