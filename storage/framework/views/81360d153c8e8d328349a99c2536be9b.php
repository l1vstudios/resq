<?php $__env->startSection('title'); ?> Canonical Database <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<link href="<?php echo e(URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
<link href="<?php echo e(URL::asset('build/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
<link href="<?php echo e(URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
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
    .canonical-domain-icon {
        width: 52px;
        height: 52px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff !important;
        font-size: 26px;
    }
    .canonical-domain-icon i { color: #fff !important; }
    .domain-icon-meteorology { background-color: #556ee6; }
    .domain-icon-hydrology { background-color: #34c38f; }
    .domain-icon-geotechnical { background-color: #f1b44c; }
    .badge-rdm { background-color: #556ee6; }
    .badge-rdp { background-color: #34c38f; }
    .badge-ppc { background-color: #f1b44c; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
    <?php $__env->slot('li_1'); ?> Configuration <?php $__env->endSlot(); ?>
    <?php $__env->slot('title'); ?> Canonical Database <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

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
                        <a class="nav-link" data-bs-toggle="tab" href="#presets" role="tab">
                            <span class="d-block d-sm-none"><i class="bx bx-list-check"></i></span>
                            <span class="d-none d-sm-block">Sensor Presets</span>
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
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $canonicalDomains; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $domain): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 canonical-domain-card domain-<?php echo e($key); ?> shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-sm me-3">
                                                <span class="avatar-title rounded-circle canonical-domain-icon domain-icon-<?php echo e($key); ?>">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($key == 'meteorology'): ?> <i class="bx bx-cloud-light-rain"></i>
                                                    <?php elseif($key == 'hydrology'): ?> <i class="bx bx-water"></i>
                                                    <?php else: ?> <i class="bx bx-landscape"></i> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </span>
                                            </div>
                                            <h5 class="font-size-15 mb-0"><?php echo e($domain['title']); ?></h5>
                                        </div>
                                        <p class="text-muted"><?php echo e($domain['description']); ?></p>
                                        <div class="mt-4">
                                            <h6 class="font-size-13 mb-3">Parameter Groups:</h6>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $domain['groups']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <span class="badge bg-light text-dark"><?php echo e($group); ?></span>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
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
                                        <form method="POST" action="<?php echo e(route('canonical-parameters.store')); ?>" id="canonical-parameter-form">
                                            <?php echo csrf_field(); ?>
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
                                            <h6 class="mt-4 mb-3">Physical Specifications (Validation Range)</h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Minimum Value</label>
                                                    <input type="number" name="min_value" class="form-control" placeholder="e.g. 0" step="any">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Maximum Value</label>
                                                    <input type="number" name="max_value" class="form-control" placeholder="e.g. 40" step="any">
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Resolution</label>
                                                    <input type="number" name="resolution" class="form-control" placeholder="e.g. 0.1" step="any" min="0">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Accuracy</label>
                                                    <input type="text" name="accuracy" class="form-control" placeholder="e.g. ±5%">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Datasheet URL</label>
                                                <input type="url" name="source_url" class="form-control" placeholder="https://manufacturer.example/sensor-datasheet">
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Source Reference</label>
                                                    <input type="text" name="source_reference" class="form-control" placeholder="e.g. Technical Specification table">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Source Note</label>
                                                    <input type="text" name="source_note" class="form-control" placeholder="e.g. RK900-11 official product page">
                                                </div>
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
                                                <th>Specification Source</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $canonicalParameters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $param): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <tr>
                                                <td><strong><?php echo e(is_array($param) ? $param['field_identity'] : $param->field_identity); ?></strong></td>
                                                <td><span class="badge bg-info text-uppercase"><?php echo e(is_array($param) ? $param['domain'] : $param->domain); ?></span></td>
                                                <td><?php echo e(is_array($param) ? $param['canonical_unit'] : $param->canonical_unit); ?></td>
                                                <td>
                                                    <?php
                                                        $origin = is_array($param)
                                                            ? $param['origin']
                                                            : ($param->is_platform_processed ? 'PPC' : 'RDM');
                                                        $badgeClass = str_contains($origin, 'RDM') ? 'badge-rdm' : (str_contains($origin, 'RDP') ? 'badge-rdp' : 'badge-ppc');
                                                    ?>
                                                    <span class="badge <?php echo e($badgeClass); ?>"><?php echo e($origin); ?></span>
                                                </td>
                                                <td><span class="badge bg-<?php echo e((is_array($param) ? ($param['status'] ?? 'active') : $param->status) === 'active' ? 'success' : 'secondary'); ?>"><?php echo e(is_array($param) ? ($param['status'] ?? 'active') : ucfirst($param->status)); ?></span></td>
                                                <td><?php echo e(is_array($param) ? $param['definition'] : $param->definition); ?></td>
                                                <td>
                                                    <?php
                                                        $requirements = is_array($param) ? ($param['input_requirements'] ?? []) : ($param->input_requirements ?? []);
                                                        $sourceUrl = $requirements['source_url'] ?? null;
                                                        $sourceReference = $requirements['source_reference'] ?? null;
                                                    ?>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sourceUrl): ?>
                                                        <a href="<?php echo e($sourceUrl); ?>" target="_blank" rel="noopener"><?php echo e($sourceReference ?: 'Datasheet'); ?></a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! is_array($param)): ?>
                                                        <div class="d-inline-flex gap-1">
                                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                                data-edit-form="#canonical-parameter-form"
                                                                data-edit-fields="<?php echo e(base64_encode(json_encode([
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
                                                                    'min_value' => $param->input_requirements['min_value'] ?? null,
                                                                    'max_value' => $param->input_requirements['max_value'] ?? null,
                                                                    'resolution' => $param->input_requirements['resolution'] ?? null,
                                                                    'accuracy' => $param->input_requirements['accuracy'] ?? null,
                                                                    'source_url' => $param->input_requirements['source_url'] ?? null,
                                                                    'source_reference' => $param->input_requirements['source_reference'] ?? null,
                                                                    'source_note' => $param->input_requirements['source_note'] ?? null,
                                                                ]))); ?>">Edit</button>
                                                            <form method="POST" action="<?php echo e(route('canonical-parameters.destroy', $param->id)); ?>">
                                                                <?php echo csrf_field(); ?>
                                                                <?php echo method_field('DELETE'); ?>
                                                                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SENSOR PRESETS TAB -->
                    <div class="tab-pane" id="presets" role="tabpanel">
                        <div class="row mt-4">
                            <div class="col-xl-12">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                            <div>
                                                <h5 class="mb-1">Sensor Preset</h5>
                                                <p class="text-muted mb-0">Buat template parameter per model sensor untuk bulk mapping.</p>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="reset-preset-form">Reset</button>
                                        </div>

                                        <form method="POST" action="<?php echo e(route('sensor-mapping-presets.store')); ?>" id="sensor-preset-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="preset_id" id="preset_id">

                                            <div class="mb-3">
                                                <label class="form-label">Preset Label <span class="text-danger">*</span></label>
                                                <input type="text" name="label" class="form-control" placeholder="RK510-01 Soil Moisture" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Manufacturer</label>
                                                    <input type="text" name="manufacturer" class="form-control" placeholder="Rika Sensor">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Device Model <span class="text-danger">*</span></label>
                                                    <input type="text" name="device_model" class="form-control" placeholder="RK510-01" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Communication Path</label>
                                                <input type="text" name="communication_path" class="form-control" placeholder="RS485 Modbus RTU">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="2" placeholder="Contoh: preset dari datasheet dan register map sensor soil moisture."></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                                <select name="status" class="form-select" required>
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                                <small class="text-muted">Hanya preset Active yang muncul di bulk mapping.</small>
                                            </div>

                                            <div class="d-flex align-items-center justify-content-between gap-2 mt-4 mb-2">
                                                <h6 class="mb-0">Preset Parameters</h6>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="add-preset-item">
                                                    <i class="bx bx-plus me-1"></i> Add Parameter
                                                </button>
                                            </div>
                                            <div class="alert alert-info py-2 mb-3">
                                                <strong>Register mapping:</strong> kolom <strong>Address Offset</strong> adalah alamat register relatif dari start address saat bulk mapping.
                                                Untuk manual RK900-11, nomor register tabel dikurangi 1 karena tabel manual mulai dari Register 1, sedangkan request Modbus mulai dari address 0.
                                                Contoh: manual register 3.4 berarti Address Offset 2 dengan Length 2 register.
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle mb-2" style="min-width: 980px;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="min-width: 220px;">Canonical Target</th>
                                                            <th style="min-width: 220px;">Source Parameter</th>
                                                            <th style="width: 120px;">Unit</th>
                                                            <th style="width: 160px;">Address Offset (-1)</th>
                                                            <th style="width: 160px;">Data Type</th>
                                                            <th style="width: 150px;">Byte Order</th>
                                                            <th style="width: 130px;">Length (Regs)</th>
                                                            <th style="width: 55px;"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="preset-items-body"></tbody>
                                                </table>
                                            </div>
                                            <small class="text-muted d-block mb-3">
                                                Address Offset = nomor register di manual - 1 jika manual memakai penomoran 1-based seperti RK900-11. Misal start address 0 dan manual register 3.4, isi offset 2, maka parameter dibaca mulai address 2. Length 1 = 16 bit, length 2 = 32 bit/float. Byte Order CDAB = word swap untuk beberapa float Modbus RK900-11.
                                            </small>

                                            <button type="submit" class="btn btn-primary w-100">Save Preset</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-12">
                                <div class="card border">
                                    <div class="card-body">
                                        <h5 class="mb-3">Saved Presets</h5>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($sensorPresets)): ?>
                                            <div class="alert alert-info mb-0">Belum ada sensor preset. Buat preset pertama dari form di sebelah kiri.</div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-bordered dt-responsive nowrap w-100 datatable">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Preset</th>
                                                            <th>Model</th>
                                                            <th>Parameters</th>
                                                            <th>Status</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensorPresets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $preset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                            <?php
                                                                $presetEditFields = [
                                                                    'preset_id' => $preset['id'],
                                                                    'label' => $preset['label'],
                                                                    'manufacturer' => $preset['manufacturer'],
                                                                    'device_model' => $preset['device_model'],
                                                                    'communication_path' => $preset['communication_path'],
                                                                    'description' => $preset['description'],
                                                                    'status' => $preset['status'],
                                                                    'items' => $preset['parameters'],
                                                                ];
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <strong><?php echo e($preset['label']); ?></strong>
                                                                    <div class="small text-muted"><?php echo e($preset['manufacturer'] ?: '-'); ?></div>
                                                                </td>
                                                                <td><?php echo e($preset['device_model'] ?: '-'); ?></td>
                                                                <td>
                                                                    <div class="d-flex flex-wrap gap-1">
                                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $preset['parameters']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                                            <span class="badge bg-light text-dark">
                                                                                <?php echo e($item['field_identity']); ?> +<?php echo e($item['register_offset']); ?> <?php echo e($item['value_type'] ? '(' . $item['value_type'] . ')' : ''); ?>

                                                                            </span>
                                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo e($preset['status'] === 'active' ? 'success' : 'secondary'); ?>">
                                                                        <?php echo e(ucfirst($preset['status'])); ?>

                                                                    </span>
                                                                </td>
                                                                <td class="text-end">
                                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($preset['id']): ?>
                                                                        <div class="d-inline-flex gap-1">
                                                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                                                data-edit-preset="<?php echo e(base64_encode(json_encode($presetEditFields))); ?>">Edit</button>
                                                                            <form method="POST" action="<?php echo e(route('sensor-mapping-presets.destroy', $preset['id'])); ?>">
                                                                                <?php echo csrf_field(); ?>
                                                                                <?php echo method_field('DELETE'); ?>
                                                                                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                                            </form>
                                                                        </div>
                                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
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

                        <div class="border rounded p-3 bg-light mb-4">
                            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
                                <div>
                                    <h5 class="mb-1">Tutorial Pengisian Mapping Sensor Multiparameter</h5>
                                    <p class="text-muted mb-0">
                                        Mapping dipakai agar sistem membaca parameter dari datasheet dan profile sensor, bukan menebak dari nama sensor. Satu sensor multiparameter dapat memiliki banyak mapping profile aktif.
                                    </p>
                                </div>
                                <span class="badge bg-primary">No Guessing</span>
                            </div>

                            <div class="row">
                                <div class="col-xl-4 mb-3">
                                    <h6 class="mb-2">1. Isi Master Parameter</h6>
                                    <ul class="mb-0 ps-3">
                                        <li><strong>Parameter Name</strong>: nama standar, contoh <code>WindSpeed</code>.</li>
                                        <li><strong>Domain</strong>: meteorology, hydrology, atau geotechnical.</li>
                                        <li><strong>Unit</strong>: unit canonical, contoh <code>m/s</code>, <code>hPa</code>, <code>lux</code>.</li>
                                        <li><strong>Physical Specifications</strong>: min, max, resolution, accuracy dari datasheet.</li>
                                        <li><strong>Datasheet URL</strong>: link sumber resmi agar data bisa diaudit.</li>
                                    </ul>
                                </div>
                                <div class="col-xl-4 mb-3">
                                    <h6 class="mb-2">2. Buat Mapping Profile</h6>
                                    <ul class="mb-0 ps-3">
                                        <li><strong>Sensor</strong>: pilih sensor fisik yang dibaca RedNode.</li>
                                        <li><strong>Profile Code</strong>: contoh <code>MAP-RK90011-WS01-WIND-SPEED</code>.</li>
                                        <li><strong>Manufacturer / Model</strong>: contoh <code>Rika Sensor</code> / <code>RK900-11</code>.</li>
                                        <li><strong>Register Address</strong>: ambil dari Modbus register map/manual sensor.</li>
                                        <li><strong>Source Parameter / Unit</strong>: nama asli dari manual, contoh <code>Wind speed</code> / <code>m/s</code>.</li>
                                    </ul>
                                </div>
                                <div class="col-xl-4 mb-3">
                                    <h6 class="mb-2">3. Hubungkan ke Canonical</h6>
                                    <ul class="mb-0 ps-3">
                                        <li><strong>Scale Factor</strong> dan <strong>Offset</strong>: pakai nilai dari manual; jika tidak ada gunakan <code>1</code> dan <code>0</code>.</li>
                                        <li><strong>Value Origin</strong>: pilih RDM jika nilai langsung pengukuran, RDP jika sudah dihitung perangkat.</li>
                                        <li><strong>Target Canonical Parameter</strong>: pilih master parameter yang sudah berisi range dan datasheet.</li>
                                        <li><strong>Status</strong>: aktifkan hanya jika register dan datasheet sudah valid.</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="alert alert-warning mb-3">
                                <strong>Penting:</strong> tabel spesifikasi produk biasanya hanya memberi range, resolusi, dan akurasi. Alamat register, data type, byte order, data length, scale factor, dan offset tetap harus berasal dari Modbus register map/manual sensor. Jika register belum jelas, simpan sebagai raw dulu.
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0 bg-white">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Contoh RK900-11 Parameter</th>
                                            <th>Range</th>
                                            <th>Resolution</th>
                                            <th>Accuracy</th>
                                            <th>Source</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>WindSpeed</code></td>
                                            <td>0 - 40 m/s</td>
                                            <td>0.1 m/s</td>
                                            <td>±5%</td>
                                            <td>Rika SPECIFICATIONS table</td>
                                        </tr>
                                        <tr>
                                            <td><code>WindDirection</code></td>
                                            <td>0 - 359°</td>
                                            <td>1°</td>
                                            <td>±3°</td>
                                            <td>Rika SPECIFICATIONS table</td>
                                        </tr>
                                        <tr>
                                            <td><code>Temperature</code></td>
                                            <td>-40 - 80°C</td>
                                            <td>0.1°C</td>
                                            <td>±1°C</td>
                                            <td>Rika SPECIFICATIONS table</td>
                                        </tr>
                                        <tr>
                                            <td><code>PM25</code> / <code>PM10</code></td>
                                            <td>0 - 2000 μg/m³</td>
                                            <td>1 μg/m³</td>
                                            <td>±5% / ±8%</td>
                                            <td>Rika SPECIFICATIONS table</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sensorMappingProfiles->isEmpty()): ?>
                        <div class="alert alert-warning">
                            No mapping profiles available yet. The mapping connects Raw Data parameters to Canonical Parameters.
                        </div>
                        <?php else: ?>
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
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensorMappingProfiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <tr>
                                        <td><?php echo e($profile->sensor ? $profile->sensor->sensor_code : 'N/A'); ?></td>
                                        <td><span class="text-danger"><?php echo e($profile->source_parameter); ?></span></td>
                                        <td><?php echo e($profile->source_unit); ?></td>
                                        <td>x<?php echo e($profile->scale_factor); ?> +<?php echo e($profile->offset); ?></td>
                                         <td>
                                            <span class="text-success fw-bold"><?php echo e($profile->canonicalParameter ? $profile->canonicalParameter->field_identity : 'N/A'); ?></span>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($profile->canonicalParameter && $profile->canonicalParameter->input_requirements): ?>
                                                <?php
                                                    $req = $profile->canonicalParameter->input_requirements;
                                                ?>
                                                <div class="small text-muted mt-1">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($req['min_value'] ?? null) !== null || ($req['max_value'] ?? null) !== null): ?>
                                                        Range: <?php echo e($req['min_value'] ?? '?'); ?> - <?php echo e($req['max_value'] ?? '?'); ?> <?php echo e($profile->canonicalParameter->canonical_unit); ?>

                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($req['resolution'] ?? null) !== null): ?>
                                                        <br/>Res: <?php echo e($req['resolution']); ?>

                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($req['accuracy'] ?? null) !== null): ?>
                                                        <br/>Acc: <?php echo e($req['accuracy']); ?>

                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($req['source_url'])): ?>
                                                        <br/><a href="<?php echo e($req['source_url']); ?>" target="_blank" rel="noopener"><?php echo e($req['source_reference'] ?? 'Datasheet'); ?></a>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                         </td>
                                        <td>
                                            <span class="badge bg-<?php echo e($profile->status == 'active' ? 'success' : 'secondary'); ?>">
                                                <?php echo e(ucfirst($profile->status)); ?>

                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="<?php echo e(route('canonical-mapping.destroy', $profile->id)); ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <!-- OBSERVATIONS TAB -->
                    <div class="tab-pane" id="observations" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                            <h5 class="mb-0">Latest Canonical Observations</h5>
                            <button type="button" class="btn btn-success btn-sm waves-effect waves-light" disabled>
                                <i class="bx bx-refresh me-1"></i> Refresh Data
                            </button>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canonicalObservations->isEmpty()): ?>
                        <div class="alert alert-info">
                            Canonical observations store the harmonized data based on the domain. No data available yet.
                        </div>
                        <?php else: ?>
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
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $canonicalObservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $obs): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <tr>
                                        <td><?php echo e($obs->observed_at->format('Y-m-d H:i:s')); ?></td>
                                        <td><span class="badge bg-info text-uppercase"><?php echo e($obs->domain); ?></span></td>
                                        <td>
                                            <?php echo e($obs->monitoringStation ? $obs->monitoringStation->station_code : 'N/A'); ?><br>
                                            <small class="text-muted"><?php echo e($obs->sensor ? $obs->sensor->sensor_code : 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                                $fields = is_string($obs->field_values) ? json_decode($obs->field_values, true) : $obs->field_values;
                                                $units = is_string($obs->field_units) ? json_decode($obs->field_units, true) : $obs->field_units;
                                                $count = 0;
                                            ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($fields && is_array($fields)): ?>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($count < 3): ?>
                                                        <div class="mb-1">
                                                            <strong><?php echo e($key); ?>:</strong> <?php echo e($val); ?> <?php echo e($units[$key] ?? ''); ?>

                                                        </div>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    <?php $count++; ?>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($fields) > 3): ?>
                                                    <span class="badge bg-light text-dark">+ <?php echo e(count($fields) - 3); ?> more</span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($obs->quality_status == 'valid'): ?>
                                                <span class="badge bg-success">Valid</span>
                                            <?php elseif($obs->quality_status == 'suspect'): ?>
                                                <span class="badge bg-warning">Suspect</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?php echo e(ucfirst($obs->quality_status)); ?></span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" title="View Detail"><i class="mdi mdi-eye"></i></button>
                                        </td>
                                    </tr>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
            <form action="<?php echo e(route('canonical-mapping.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sensor_id" class="form-label">Sensor (Raw Identity) <span class="text-danger">*</span></label>
                            <select class="form-select" id="sensor_id" name="sensor_id" required>
                                <option value="">Select Sensor</option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sensor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <option value="<?php echo e($sensor->id); ?>"
                                        data-slave-id="<?php echo e($sensor->slave_id); ?>"
                                        data-address="<?php echo e($sensor->address); ?>"
                                        data-parameter="<?php echo e($sensor->parameter); ?>"
                                        data-unit="<?php echo e($sensor->unit); ?>"
                                        data-scale-factor="<?php echo e($sensor->scale_factor); ?>"
                                        data-offset="<?php echo e($sensor->offset); ?>">
                                        <?php echo e($sensor->sensor_code); ?> (<?php echo e($sensor->parameter); ?>)
                                    </option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="profile_code" class="form-label">Profile Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="profile_code" name="profile_code" required placeholder="e.g. SENSOR-MAPPING-001">
                            <small class="text-muted">Untuk bulk, code ini menjadi prefix. Contoh: <code>MAP-SENSOR-2-WIND-SPEED</code>.</small>
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
                            <label for="register_address" class="form-label">Register Address (Start/Base)</label>
                            <input type="number" class="form-control" id="register_address" name="register_address">
                            <small class="text-muted">Untuk bulk: final address = start/base ini + Address Offset preset.</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="source_parameter" class="form-label">Source Parameter (Raw Identity)</label>
                            <input type="text" class="form-control" id="source_parameter" name="source_parameter" placeholder="e.g. Air_temp">
                            <small class="text-muted">Kosongkan jika memakai bulk preset; sistem akan mengisi dari item preset.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="source_unit" class="form-label">Source Unit (Raw Identity)</label>
                            <input type="text" class="form-control" id="source_unit" name="source_unit">
                        </div>
                    </div>
                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                            <div>
                                <label for="bulk_preset" class="form-label mb-1">Bulk Parameters by Sensor Model</label>
                                <div class="small text-muted">Pilih preset sesuai model sensor, lalu aktifkan parameter yang ingin dibuat mapping profile.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="select-bulk-preset-parameters">Select preset set</button>
                        </div>
                        <div class="row align-items-end">
                            <div class="col-md-6 mb-3">
                                <label for="bulk_preset" class="form-label">Sensor Preset</label>
                                <select class="form-select" id="bulk_preset" name="bulk_preset_key" <?php if(empty($mappingPresets)): ?> disabled <?php endif; ?>>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $mappingPresets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $preset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($preset['key']); ?>"><?php echo e($preset['label']); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        <option value="">Belum ada preset dari mapping profile</option>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Preset Source</label>
                                <input type="text" class="form-control" id="bulk_preset_source" value="Mapping profiles database" readonly>
                            </div>
                        </div>
                        <select class="form-select" id="canonical_parameter_ids" name="canonical_parameter_ids[]" multiple size="8">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $canonicalParameters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $param): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($param->id); ?>" data-field="<?php echo e($param->field_identity); ?>" hidden>
                                    <?php echo e($param->field_identity); ?> (<?php echo e($param->canonical_unit ?: '-'); ?>)
                                </option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                        <div class="small mt-2" id="bulk-selection-status">Belum ada parameter bulk yang dipilih.</div>
                        <small class="text-muted d-block mt-2">
                            Register Address di atas dipakai sebagai start/base address. Final address tiap parameter = start/base + Address Offset preset. Jika preset offset 0 dan start/base 0, parameter dibaca di address 0.
                        </small>
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
                            <label for="canonical_parameter_id" class="form-label">Target Canonical Parameter (Standardized)</label>
                            <select class="form-select" id="canonical_parameter_id" name="canonical_parameter_id">
                                 <option value="">Select Parameter</option>
                                 <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $canonicalParameters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $param): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                     <?php
                                         $req = $param->input_requirements ?? [];
                                         $specText = '';
                                         if(isset($req['min_value']) || isset($req['max_value'])) {
                                             $specText .= ' [' . ($req['min_value'] ?? '?') . '-' . ($req['max_value'] ?? '?') . ' ' . $param->canonical_unit . ']';
                                         }
                                         if(isset($req['accuracy'])) {
                                             $specText .= ' Acc: ' . $req['accuracy'];
                                         }
                                         if(isset($req['source_url'])) {
                                             $specText .= ' Source: ' . ($req['source_reference'] ?? $req['source_url']);
                                         }
                                     ?>
                                     <option value="<?php echo e($param->id); ?>" data-spec="<?php echo e($specText); ?>"><?php echo e($param->field_identity); ?> (<?php echo e($param->domain); ?>)<?php echo e($specText); ?></option>
                                 <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                             </select>
                             <small class="form-text text-muted mt-2" id="canonical-param-spec" style="display:none;">
                                 <strong>Specification:</strong> <span id="canonical-param-spec-text"></span>
                             </small>
                             <small class="text-muted d-block mt-2" id="single-target-help">Dipakai untuk mapping satu parameter. Jika Bulk Parameters dipilih, field ini akan dikosongkan otomatis.</small>
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

<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js')); ?>"></script>
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

        const canonicalParameterChoices = <?php echo json_encode($canonicalParameterChoices, 15, 512) ?>;

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function canonicalParameterOptions(selectedId) {
            const selected = String(selectedId || '');
            const options = ['<option value="">Select Parameter</option>'];

            (canonicalParameterChoices || []).forEach(function(parameter) {
                const unit = parameter.canonical_unit || '-';
                const label = parameter.field_identity + ' (' + unit + ')';
                options.push(
                    '<option value="' + escapeHtml(parameter.id) + '"'
                    + ' data-field="' + escapeHtml(parameter.field_identity) + '"'
                    + ' data-unit="' + escapeHtml(parameter.canonical_unit || '') + '"'
                    + (String(parameter.id) === selected ? ' selected' : '')
                    + '>' + escapeHtml(label) + '</option>'
                );
            });

            return options.join('');
        }

        function reindexPresetItems() {
            $('#preset-items-body tr').each(function(index) {
                $(this).find('[data-preset-field]').each(function() {
                    const field = $(this).data('preset-field');
                    $(this).attr('name', 'items[' + index + '][' + field + ']');
                });
            });
        }

        function addPresetItemRow(item = {}) {
            const row = $(
                '<tr>' +
                    '<td><select class="form-select form-select-sm preset-canonical-select" data-preset-field="canonical_parameter_id" required>' + canonicalParameterOptions(item.canonical_parameter_id) + '</select></td>' +
                    '<td><input type="text" class="form-control form-control-sm preset-source-parameter" data-preset-field="source_parameter" required></td>' +
                    '<td><input type="text" class="form-control form-control-sm preset-source-unit" data-preset-field="source_unit"></td>' +
                    '<td><input type="number" min="0" class="form-control form-control-sm" data-preset-field="register_offset" required></td>' +
                    '<td><select class="form-select form-select-sm" data-preset-field="value_type"><option value="">Default sensor</option><option value="float32">float32 - 32 bit</option><option value="uint16">uint16 - 16 bit</option><option value="int16">int16 - 16 bit</option><option value="uint32">uint32 - 32 bit</option><option value="int32">int32 - 32 bit</option></select><input type="hidden" data-preset-field="function_code" value="FC03"></td>' +
                    '<td><select class="form-select form-select-sm" data-preset-field="byte_order"><option value="">Default</option><option value="ABCD">ABCD</option><option value="CDAB">CDAB</option><option value="BADC">BADC</option><option value="DCBA">DCBA</option></select></td>' +
                    '<td><input type="number" min="1" class="form-control form-control-sm" data-preset-field="data_length"></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm" data-remove-preset-item><i class="bx bx-trash"></i></button></td>' +
                '</tr>'
            );

            row.find('.preset-source-parameter').val(item.source_parameter || '');
            row.find('.preset-source-unit').val(item.source_unit || '');
            row.find('[data-preset-field="register_offset"]').val(item.register_offset ?? 0);
            row.find('[data-preset-field="value_type"]').val(item.value_type || '');
            row.find('[data-preset-field="function_code"]').val(item.function_code || 'FC03');
            row.find('[data-preset-field="data_length"]').val(item.data_length || '');
            row.find('[data-preset-field="byte_order"]').val(item.byte_order || '');
            $('#preset-items-body').append(row);
            reindexPresetItems();
        }

        function resetPresetForm() {
            const form = document.querySelector('#sensor-preset-form');
            if (!form) {
                return;
            }

            form.reset();
            $('#preset_id').val('');
            $('#preset-items-body').empty();
            addPresetItemRow({ register_offset: 0 });
        }

        $('#add-preset-item').on('click', function() {
            addPresetItemRow({ register_offset: $('#preset-items-body tr').length });
        });

        $('#reset-preset-form').on('click', resetPresetForm);

        $('#preset-items-body').on('click', '[data-remove-preset-item]', function() {
            $(this).closest('tr').remove();
            if ($('#preset-items-body tr').length === 0) {
                addPresetItemRow({ register_offset: 0 });
            }
            reindexPresetItems();
        });

        $('#preset-items-body').on('change', '.preset-canonical-select', function() {
            const option = $(this).find('option:selected');
            const row = $(this).closest('tr');
            const sourceParameter = row.find('.preset-source-parameter');
            const sourceUnit = row.find('.preset-source-unit');

            if (!sourceParameter.val()) {
                sourceParameter.val(option.data('field') || '');
            }

            if (!sourceUnit.val()) {
                sourceUnit.val(option.data('unit') || '');
            }
        });

        $('#preset-items-body').on('change', '[data-preset-field="value_type"]', function() {
            const row = $(this).closest('tr');
            const dataLength = row.find('[data-preset-field="data_length"]');
            const valueType = String($(this).val() || '').toLowerCase();

            if (!dataLength.val()) {
                dataLength.val(valueType.includes('32') || valueType.includes('float') ? 2 : 1);
            }
        });

        $('[data-edit-preset]').on('click', function() {
            const encoded = $(this).attr('data-edit-preset');
            if (!encoded) {
                return;
            }

            try {
                const fields = JSON.parse(atob(encoded));
                const form = document.querySelector('#sensor-preset-form');

                Object.keys(fields || {}).forEach(function(name) {
                    if (name === 'items') {
                        return;
                    }

                    const input = form.querySelector('[name="' + name + '"]');
                    if (input) {
                        input.value = fields[name] ?? '';
                    }
                });

                $('#preset-items-body').empty();
                (fields.items || []).forEach(function(item) {
                    addPresetItemRow(item);
                });

                if ($('#preset-items-body tr').length === 0) {
                    addPresetItemRow({ register_offset: 0 });
                }

                const presetTab = document.querySelector('a[href="#presets"]');
                if (window.bootstrap && presetTab) {
                    window.bootstrap.Tab.getOrCreateInstance(presetTab).show();
                }
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } catch (error) {
                console.error(error);
            }
        });

        resetPresetForm();

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
                $('#source_parameter').val(parameter !== undefined && parameter !== 0 && parameter !== '0' ? parameter : '');
                $('#source_unit').val(unit !== undefined && unit !== 0 && unit !== '0' ? unit : '');
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

        const bulkPresetList = <?php echo json_encode($mappingPresets, 15, 512) ?>;
        const bulkPresets = Object.fromEntries((bulkPresetList || []).map(function(preset) {
            return [preset.key, preset];
        }));

        function selectedBulkPreset() {
            return bulkPresets[$('#bulk_preset').val()] || null;
        }

        function applyBulkPresetVisibility(selectAll = false) {
            const preset = selectedBulkPreset();

            if (!preset) {
                $('#canonical_parameter_ids option').each(function() {
                    this.selected = false;
                    $(this).prop('hidden', true);
                });
                $('#bulk-selection-status')
                    .removeClass('text-success')
                    .addClass('text-muted')
                    .text('Belum ada preset. Buat mapping profile referensi dengan Device Model yang sama terlebih dahulu.');
                $('#select-bulk-preset-parameters').prop('disabled', true);
                refreshBulkMode();
                return;
            }

            $('#select-bulk-preset-parameters').prop('disabled', false);
            const parameterIds = (preset.parameters || []).map(function(parameter) {
                return String(parameter.canonical_parameter_id);
            });

            $('#canonical_parameter_ids option').each(function() {
                const visible = parameterIds.includes(String(this.value));
                $(this).prop('hidden', !visible);
                if (!visible || selectAll) {
                    this.selected = visible && selectAll;
                }
            });

            $('#bulk_preset_source').val('Mapping profiles database - ' + preset.label);
            $('#manufacturer').val($('#manufacturer').val() || preset.manufacturer);
            $('#device_model').val(preset.device_model);
            $('#communication_path').val($('#communication_path').val() || preset.communication_path);
            $('#source_parameter').val('');
            $('#source_unit').val('');
            refreshBulkMode();
        }

        function refreshBulkMode() {
            const selected = $('#canonical_parameter_ids').val() || [];
            const hasBulk = selected.length > 0;
            const preset = selectedBulkPreset();
            const presetLabel = preset ? preset.label : 'preset';

            $('#bulk-selection-status')
                .toggleClass('text-success', hasBulk)
                .toggleClass('text-muted', !hasBulk)
                .text(hasBulk
                    ? selected.length + ' parameter ' + presetLabel + ' akan dibuatkan mapping profile.'
                    : 'Belum ada parameter bulk yang dipilih.');

            if (hasBulk) {
                $('#canonical_parameter_id').val('').prop('disabled', true);
                $('#canonical-param-spec-text').text('');
                $('#canonical-param-spec').hide();
                $('#single-target-help').text('Bulk aktif: Target Canonical single tidak perlu dipilih.');
            } else {
                $('#canonical_parameter_id').prop('disabled', false);
                $('#single-target-help').text('Dipakai untuk mapping satu parameter. Jika Bulk Parameters dipilih, field ini akan dikosongkan otomatis.');
            }
        }

        $('#bulk_preset').on('change', function() {
            applyBulkPresetVisibility(false);
        });

        $('#select-bulk-preset-parameters').on('click', function() {
            applyBulkPresetVisibility(true);
            if (!$('#profile_code').val()) {
                var sensorCode = $('#sensor_id option:selected').text().split(' (')[0].trim();
                $('#profile_code').val(sensorCode ? 'MAP-' + sensorCode : 'MAP-' + $('#bulk_preset').val());
            }
        });

        $('#canonical_parameter_ids').on('change', refreshBulkMode);
        $('#device_model').on('change keyup', function() {
            const model = String($(this).val() || '').toLowerCase();
            const matchedPreset = (bulkPresetList || []).find(function(preset) {
                return String(preset.device_model || '').toLowerCase() === model
                    || String(preset.label || '').toLowerCase().includes(model);
            });

            if (matchedPreset) {
                $('#bulk_preset').val(matchedPreset.key);
                applyBulkPresetVisibility(false);
            }
        });

        applyBulkPresetVisibility(false);

        $('form[action="<?php echo e(route('canonical-mapping.store')); ?>"]').on('submit', function(event) {
            const hasBulk = $('#canonical_parameter_ids').val() && $('#canonical_parameter_ids').val().length > 0;
            const hasSingle = Boolean($('#canonical_parameter_id').val());

            if (!hasBulk && !hasSingle) {
                event.preventDefault();
                alert('Pilih Target Canonical Parameter atau Bulk Parameters terlebih dahulu.');
            }
        });

        $('#canonical_parameter_id').on('change', function() {
            var spec = $(this).find('option:selected').data('spec');

            if (spec) {
                $('#canonical-param-spec-text').text(spec);
                $('#canonical-param-spec').show();
            } else {
                $('#canonical-param-spec-text').text('');
                $('#canonical-param-spec').hide();
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/canonical-database/index.blade.php ENDPATH**/ ?>