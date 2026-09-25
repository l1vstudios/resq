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
                        <input type="file" id="tde-excel-file" class="d-none" accept=".xlsx,.xls">
                        <button type="button" class="btn btn-outline-primary" id="tde-browse-file">
                            <i class="bx bx-file-find me-1"></i> Browse File
                        </button>
                        <div class="text-muted mt-2" id="tde-selected-file">Belum ada file dipilih.</div>
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
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada master data TDE.</td>
                            </tr>
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
    var browseButton = document.getElementById('tde-browse-file');
    var fileInput = document.getElementById('tde-excel-file');
    var selectedFile = document.getElementById('tde-selected-file');

    browseButton?.addEventListener('click', function () {
        fileInput?.click();
    });

    fileInput?.addEventListener('change', function () {
        selectedFile.textContent = this.files && this.files.length
            ? this.files[0].name
            : 'Belum ada file dipilih.';
    });

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
