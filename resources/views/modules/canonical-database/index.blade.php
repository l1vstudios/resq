@extends('layouts.master')
@section('title') Canonical Database @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .nav-tabs-custom .nav-item .nav-link.active {
        color: #556ee6;
        background-color: #f8f9fa;
        border-color: #f8f9fa;
    }
    .canonical-domain-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .canonical-domain-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .domain-meteorology { border-left-color: #556ee6; }
    .domain-hydrology { border-left-color: #34c38f; }
    .domain-geotechnical { border-left-color: #f1b44c; }
    .badge-rdm { background-color: #556ee6; }
    .badge-rdp { background-color: #34c38f; }
    .badge-ppc { background-color: #f1b44c; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Configuration @endslot
    @slot('title') Canonical Database @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Sentinel EMP - Canonical Database Concept</h4>
                <p class="text-muted">
                    Canonical Database adalah struktur data standar yang mengakomodir data pengukuran (Raw Data), hasil perhitungan perangkat (Device-Processed), dan hasil perhitungan platform (Platform-Processed) dalam domain observasi yang seragam.
                </p>

                <!-- Nav tabs -->
                <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#domains" role="tab">
                            <span class="d-block d-sm-none"><i class="fas fa-home"></i></span>
                            <span class="d-none d-sm-block">Canonical Domains</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#parameters" role="tab">
                            <span class="d-block d-sm-none"><i class="far fa-user"></i></span>
                            <span class="d-none d-sm-block">Canonical Parameters</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#mapping" role="tab">
                            <span class="d-block d-sm-none"><i class="far fa-envelope"></i></span>
                            <span class="d-none d-sm-block">Sensor Mapping Profiles</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#observations" role="tab">
                            <span class="d-block d-sm-none"><i class="fas fa-cog"></i></span>
                            <span class="d-none d-sm-block">Canonical Observations</span>
                        </a>
                    </li>
                </ul>

                <!-- Tab panes -->
                <div class="tab-content p-3 text-muted">
                    <!-- DOMAINS TAB -->
                    <div class="tab-pane active" id="domains" role="tabpanel">
                        <div class="row mt-4">
                            @foreach($canonicalDomains as $key => $domain)
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 canonical-domain-card domain-{{ $key }} shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-sm me-3">
                                                <span class="avatar-title rounded-circle bg-primary bg-soft text-primary font-size-18">
                                                    @if($key == 'meteorology') <i class="bx bx-cloud-rain"></i>
                                                    @elseif($key == 'hydrology') <i class="bx bx-water"></i>
                                                    @else <i class="bx bx-landscape"></i> @endif
                                                </span>
                                            </div>
                                            <h5 class="font-size-15 mb-0">{{ $domain['title'] }}</h5>
                                        </div>
                                        <p class="text-muted">{{ $domain['description'] }}</p>
                                        <div class="mt-4">
                                            <h6 class="font-size-13 mb-3">Parameter Groups:</h6>
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($domain['groups'] as $group)
                                                <span class="badge bg-light text-dark">{{ $group }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="alert alert-info mt-3" role="alert">
                            <i class="mdi mdi-information-outline me-2"></i>
                            <strong>Data Classification:</strong>
                            <span class="badge badge-rdm ms-2">RDM</span> Re-identified Direct Measurement
                            <span class="badge badge-rdp ms-2">RDP</span> Re-identified Device-Processed
                            <span class="badge badge-ppc ms-2">PPC</span> Platform-Processed Canonical Data
                        </div>
                    </div>

                    <!-- PARAMETERS TAB -->
                    <div class="tab-pane" id="parameters" role="tabpanel">
                        <div class="row mt-4">
                            <div class="col-xl-4">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h5 class="mb-0">Master Parameter</h5>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-reset-form="#canonical-parameter-form">Reset</button>
                                        </div>
                                        <form method="POST" action="{{ route('canonical-parameters.store') }}" id="canonical-parameter-form">
                                            @csrf
                                            <input type="hidden" name="canonical_parameter_id">
                                            <div class="mb-3">
                                                <label class="form-label">Parameter Name</label>
                                                <input type="text" name="field_identity" class="form-control" placeholder="WaterLevel" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Domain</label>
                                                    <select name="domain" class="form-select" required>
                                                        <option value="meteorology">Meteorology</option>
                                                        <option value="hydrology">Hydrology</option>
                                                        <option value="geotechnical">Geotechnical</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Unit</label>
                                                    <input type="text" name="canonical_unit" class="form-control" placeholder="m">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Data Type</label>
                                                    <select name="data_type" class="form-select" required>
                                                        <option value="decimal">Decimal</option>
                                                        <option value="integer">Integer</option>
                                                        <option value="boolean">Boolean</option>
                                                        <option value="string">String</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Characteristic</label>
                                                    <select name="measurement_characteristic" class="form-select">
                                                        <option value="instantaneous">Instantaneous</option>
                                                        <option value="accumulated">Accumulated</option>
                                                        <option value="calculated">Calculated</option>
                                                        <option value="status">Status</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Definition</label>
                                                <textarea name="definition" class="form-control" rows="3"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Formula</label>
                                                <textarea name="formula" class="form-control" rows="2" placeholder="Optional"></textarea>
                                            </div>
                                            <div class="row align-items-end">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="active">Active</option>
                                                        <option value="inactive">Inactive</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_platform_processed" value="1" id="canonical-is-platform">
                                                        <label class="form-check-label" for="canonical-is-platform">Platform processed</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">Save Parameter</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-8">
                                <div class="table-responsive">
                                    <table class="table table-bordered dt-responsive nowrap w-100 datatable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Canonical Parameter</th>
                                                <th>Domain</th>
                                                <th>Unit</th>
                                                <th>Origin Type</th>
                                                <th>Status</th>
                                                <th>Definition</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($canonicalParameters as $param)
                                            <tr>
                                                <td><strong>{{ is_array($param) ? $param['field_identity'] : $param->field_identity }}</strong></td>
                                                <td><span class="badge bg-info text-uppercase">{{ is_array($param) ? $param['domain'] : $param->domain }}</span></td>
                                                <td>{{ is_array($param) ? $param['canonical_unit'] : $param->canonical_unit }}</td>
                                                <td>
                                                    @php
                                                        $origin = is_array($param)
                                                            ? $param['origin']
                                                            : ($param->is_platform_processed ? 'PPC' : 'RDM');
                                                        $badgeClass = str_contains($origin, 'RDM') ? 'badge-rdm' : (str_contains($origin, 'RDP') ? 'badge-rdp' : 'badge-ppc');
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">{{ $origin }}</span>
                                                </td>
                                                <td><span class="badge bg-{{ (is_array($param) ? ($param['status'] ?? 'active') : $param->status) === 'active' ? 'success' : 'secondary' }}">{{ is_array($param) ? ($param['status'] ?? 'active') : ucfirst($param->status) }}</span></td>
                                                <td>{{ is_array($param) ? $param['definition'] : $param->definition }}</td>
                                                <td class="text-end">
                                                    @if(! is_array($param))
                                                        <div class="d-inline-flex gap-1">
                                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                                data-edit-form="#canonical-parameter-form"
                                                                data-edit-fields="{{ base64_encode(json_encode([
                                                                    'canonical_parameter_id' => $param->id,
                                                                    'field_identity' => $param->field_identity,
                                                                    'definition' => $param->definition,
                                                                    'domain' => $param->domain,
                                                                    'canonical_unit' => $param->canonical_unit,
                                                                    'data_type' => $param->data_type,
                                                                    'measurement_characteristic' => $param->measurement_characteristic,
                                                                    'formula' => $param->formula,
                                                                    'status' => $param->status,
                                                                    'is_platform_processed' => $param->is_platform_processed ? 1 : 0,
                                                                ])) }}">Edit</button>
                                                            <form method="POST" action="{{ route('canonical-parameters.destroy', $param->id) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SENSOR MAPPING TAB -->
                    <div class="tab-pane" id="mapping" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                            <h5 class="mb-0">Sensor to Canonical Mapping</h5>
                            <button type="button" class="btn btn-primary btn-sm waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#addMappingProfileModal">
                                <i class="bx bx-plus me-1"></i> Add Mapping Profile
                            </button>
                        </div>

                        @if($sensorMappingProfiles->isEmpty())
                        <div class="alert alert-warning">
                            No mapping profiles available yet. The mapping connects Raw Data parameters to Canonical Parameters.
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-bordered dt-responsive nowrap w-100 datatable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sensor</th>
                                        <th>Source Parameter</th>
                                        <th>Source Unit</th>
                                        <th>Scale/Offset</th>
                                        <th><i class="bx bx-right-arrow-alt text-primary"></i> Canonical Target</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sensorMappingProfiles as $profile)
                                    <tr>
                                        <td>{{ $profile->sensor ? $profile->sensor->sensor_code : 'N/A' }}</td>
                                        <td><span class="text-danger">{{ $profile->source_parameter }}</span></td>
                                        <td>{{ $profile->source_unit }}</td>
                                        <td>x{{ $profile->scale_factor }} +{{ $profile->offset }}</td>
                                        <td><span class="text-success fw-bold">{{ $profile->canonicalParameter ? $profile->canonicalParameter->field_identity : 'N/A' }}</span></td>
                                        <td>
                                            <span class="badge bg-{{ $profile->status == 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($profile->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('canonical-mapping.destroy', $profile->id) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>

                    <!-- OBSERVATIONS TAB -->
                    <div class="tab-pane" id="observations" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                            <h5 class="mb-0">Latest Canonical Observations</h5>
                            <button type="button" class="btn btn-success btn-sm waves-effect waves-light" disabled>
                                <i class="bx bx-refresh me-1"></i> Refresh Data
                            </button>
                        </div>

                        @if($canonicalObservations->isEmpty())
                        <div class="alert alert-info">
                            Canonical observations store the harmonized data based on the domain. No data available yet.
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped dt-responsive nowrap w-100 datatable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Observation Time</th>
                                        <th>Domain</th>
                                        <th>Station / Sensor</th>
                                        <th>Field Values</th>
                                        <th>Data Quality</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($canonicalObservations as $obs)
                                    <tr>
                                        <td>{{ $obs->observed_at->format('Y-m-d H:i:s') }}</td>
                                        <td><span class="badge bg-info text-uppercase">{{ $obs->domain }}</span></td>
                                        <td>
                                            {{ $obs->monitoringStation ? $obs->monitoringStation->station_code : 'N/A' }}<br>
                                            <small class="text-muted">{{ $obs->sensor ? $obs->sensor->sensor_code : 'N/A' }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $fields = is_string($obs->field_values) ? json_decode($obs->field_values, true) : $obs->field_values;
                                                $units = is_string($obs->field_units) ? json_decode($obs->field_units, true) : $obs->field_units;
                                                $count = 0;
                                            @endphp
                                            @if($fields && is_array($fields))
                                                @foreach($fields as $key => $val)
                                                    @if($count < 3)
                                                        <div class="mb-1">
                                                            <strong>{{ $key }}:</strong> {{ $val }} {{ $units[$key] ?? '' }}
                                                        </div>
                                                    @endif
                                                    @php $count++; @endphp
                                                @endforeach
                                                @if(count($fields) > 3)
                                                    <span class="badge bg-light text-dark">+ {{ count($fields) - 3 }} more</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if($obs->quality_status == 'valid')
                                                <span class="badge bg-success">Valid</span>
                                            @elseif($obs->quality_status == 'suspect')
                                                <span class="badge bg-warning">Suspect</span>
                                            @else
                                                <span class="badge bg-danger">{{ ucfirst($obs->quality_status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" title="View Detail"><i class="mdi mdi-eye"></i></button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal Add Mapping Profile -->
<div class="modal fade" id="addMappingProfileModal" tabindex="-1" role="dialog" aria-labelledby="addMappingProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMappingProfileModalLabel">Add Sensor Mapping Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('canonical-mapping.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sensor_id" class="form-label">Sensor (Raw Identity) <span class="text-danger">*</span></label>
                            <select class="form-select" id="sensor_id" name="sensor_id" required>
                                <option value="">Select Sensor</option>
                                @foreach($sensors as $sensor)
                                    <option value="{{ $sensor->id }}"
                                        data-slave-id="{{ $sensor->slave_id }}"
                                        data-address="{{ $sensor->address }}"
                                        data-parameter="{{ $sensor->parameter }}"
                                        data-unit="{{ $sensor->unit }}"
                                        data-scale-factor="{{ $sensor->scale_factor }}"
                                        data-offset="{{ $sensor->offset }}">
                                        {{ $sensor->sensor_code }} ({{ $sensor->parameter }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="profile_code" class="form-label">Profile Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="profile_code" name="profile_code" required placeholder="e.g. SENSOR-MAPPING-001">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="manufacturer" class="form-label">Manufacturer</label>
                            <input type="text" class="form-control" id="manufacturer" name="manufacturer">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="device_model" class="form-label">Device Model</label>
                            <input type="text" class="form-control" id="device_model" name="device_model">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="communication_path" class="form-label">Communication Path</label>
                            <input type="text" class="form-control" id="communication_path" name="communication_path">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="slave_id" class="form-label">Slave ID</label>
                            <input type="number" class="form-control" id="slave_id" name="slave_id">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="register_address" class="form-label">Register Address</label>
                            <input type="number" class="form-control" id="register_address" name="register_address">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="source_parameter" class="form-label">Source Parameter (Raw Identity) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="source_parameter" name="source_parameter" required placeholder="e.g. Air_temp">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="source_unit" class="form-label">Source Unit (Raw Identity)</label>
                            <input type="text" class="form-control" id="source_unit" name="source_unit">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="scale_factor" class="form-label">Scale Factor</label>
                            <input type="number" step="any" class="form-control" id="scale_factor" name="scale_factor" value="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="offset" class="form-label">Offset</label>
                            <input type="number" step="any" class="form-control" id="offset" name="offset" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="value_origin" class="form-label">Value Origin <span class="text-danger">*</span></label>
                            <select class="form-select" id="value_origin" name="value_origin" required>
                                <option value="direct_measurement">RDM - Direct Measurement</option>
                                <option value="device_processed">RDP - Device Processed</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="canonical_parameter_id" class="form-label">Target Canonical Parameter (Standardized) <span class="text-danger">*</span></label>
                            <select class="form-select" id="canonical_parameter_id" name="canonical_parameter_id" required>
                                <option value="">Select Parameter</option>
                                @foreach($canonicalParameters as $param)
                                    <option value="{{ $param->id }}">{{ $param->field_identity }} ({{ $param->domain }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Mapping</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script>
    $(document).ready(function() {
        function fillForm(formSelector, fields) {
            const form = document.querySelector(formSelector);
            if (!form) {
                return;
            }

            Object.keys(fields || {}).forEach(function(name) {
                const input = form.querySelector('[name="' + name + '"]');
                if (!input) {
                    return;
                }

                if (input.type === 'checkbox') {
                    input.checked = Boolean(Number(fields[name]));
                    return;
                }

                input.value = fields[name] ?? '';
            });

            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        $('[data-edit-form]').on('click', function() {
            const formSelector = $(this).data('edit-form');
            const encoded = $(this).attr('data-edit-fields');
            if (!encoded) {
                return;
            }

            try {
                fillForm(formSelector, JSON.parse(atob(encoded)));
            } catch (error) {
                console.error(error);
            }
        });

        $('[data-reset-form]').on('click', function() {
            const form = document.querySelector($(this).data('reset-form'));
            if (!form) {
                return;
            }

            form.reset();
            form.querySelectorAll('input[type="hidden"]').forEach(function(input) {
                input.value = '';
            });
        });

        // Handle Auto-fill when Sensor is selected
        $('#sensor_id').on('change', function() {
            var selectedOption = $(this).find('option:selected');

            if (selectedOption.val() !== "") {
                var slaveId = selectedOption.data('slave-id');
                var address = selectedOption.data('address');
                var parameter = selectedOption.data('parameter');
                var unit = selectedOption.data('unit');
                var scaleFactor = selectedOption.data('scale-factor');
                var offset = selectedOption.data('offset');
                var sensorCode = selectedOption.text().split(' (')[0].trim();

                // Auto fill fields
                $('#slave_id').val(slaveId !== undefined ? slaveId : '');
                $('#register_address').val(address !== undefined ? address : '');
                $('#source_parameter').val(parameter !== undefined ? parameter : '');
                $('#source_unit').val(unit !== undefined ? unit : '');
                $('#scale_factor').val(scaleFactor !== undefined ? scaleFactor : '1');
                $('#offset').val(offset !== undefined ? offset : '0');

                // Set default profile code based on sensor code if empty
                if($('#profile_code').val() === '') {
                    $('#profile_code').val('MAP-' + sensorCode);
                }
            } else {
                // Clear fields if no sensor selected
                $('#slave_id').val('');
                $('#register_address').val('');
                $('#source_parameter').val('');
                $('#source_unit').val('');
                $('#scale_factor').val('1');
                $('#offset').val('0');
            }
        });

        $('.datatable').DataTable({
            responsive: true,
            language: {
                paginate: {
                    previous: "<i class='mdi mdi-chevron-left'>",
                    next: "<i class='mdi mdi-chevron-right'>"
                }
            },
            drawCallback: function() {
                $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
            }
        });
    });
</script>
@endsection
