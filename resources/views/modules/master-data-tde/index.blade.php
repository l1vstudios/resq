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
                    <a href="{{ route('master-data-tde.template') }}" class="btn btn-light">
                        <i class="bx bx-download me-1"></i> Template CSV
                    </a>
                </div>

                @if(session('message'))
                    <div class="alert alert-success">{{ session('message') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('master-data-tde.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Matrix Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', 'TDE Matrix') }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Matrix Code</label>
                            <input type="text" name="matrix_code" class="form-control" value="{{ old('matrix_code') }}" placeholder="Auto if empty">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Version</label>
                            <input type="text" name="version_label" class="form-control" value="{{ old('version_label') }}" placeholder="v1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Project Scope</label>
                            <select name="project_id" class="form-select">
                                <option value="">Global</option>
                                @foreach($projects ?? [] as $project)
                                    <option value="{{ $project->id }}" @selected((int) old('project_id') === (int) $project->id)>{{ $project->project_code }} - {{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="tde-dropzone mb-3">
                        <div class="w-100 text-center">
                            <span class="tde-dropzone-icon mb-3"><i class="bx bx-cloud-upload"></i></span>
                            <h5 class="font-size-15 mb-1">Upload Matrix TDE</h5>
                            <p class="text-muted mb-3">Format .xlsx atau .csv, wajib berisi 243 kombinasi unik.</p>
                            <input type="file" name="matrix_file" id="tde-excel-file" class="d-none" accept=".xlsx,.csv" required>
                            <button type="button" class="btn btn-outline-primary" id="tde-browse-file">
                                <i class="bx bx-file-find me-1"></i> Browse File
                            </button>
                            <div class="text-muted mt-2" id="tde-selected-file">Belum ada file dipilih.</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-import me-1"></i> Import
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="card-title mb-1">Grid Data TDE</h4>
                        <p class="text-muted mb-0">Versioned matrix yang siap dipakai oleh konfigurasi TDE station.</p>
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
                                <th>Matrix Code</th>
                                <th>Name</th>
                                <th>Scope</th>
                                <th>Rows</th>
                                <th>Status</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody id="tde-grid-body">
                            @forelse($matrices ?? [] as $matrix)
                                <tr>
                                    <td>{{ $matrix->matrix_code }}</td>
                                    <td>
                                        {{ $matrix->name }}
                                        @if($matrix->version_label)
                                            <div class="text-muted small">{{ $matrix->version_label }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $matrix->project?->project_code ?? 'Global' }}</td>
                                    <td>{{ $matrix->import_summary['row_count'] ?? count($matrix->matrix_rows ?? []) }}</td>
                                    <td>@include('modules.platform-operations.partials.status-badge', ['status' => $matrix->status])</td>
                                    <td>{{ optional($matrix->updated_at)->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada master data TDE.</td>
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
