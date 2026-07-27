<?php $__env->startSection('title'); ?> Project Setup <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Project Configuration <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Project Setup <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $project = $project ?? config('resq_dummy.project');
    $projects = collect($projects ?? []);
    $clusters = collect($clusters ?? config('resq_dummy.clusters'));
    $monitoringStations = collect($monitoringStations ?? config('resq_dummy.monitoring_stations'));
    $warningStations = collect($warningStations ?? config('resq_dummy.warning_stations'));
    $sensors = collect($sensors ?? config('resq_dummy.sensors'));
    $mstPrefixes = collect($mstPrefixes ?? []);
    $responsePlans = collect($responsePlans ?? []);
    $provinces = $provinces ?? config('indonesia.provinces') ?? [];
    $databaseReady = $databaseReady ?? false;
    $sensorTypes = [
        'water_level' => 'Water Level / TMA',
        'rain_gauge' => 'Rain Gauge / Curah Hujan',
        'tide_level' => 'Tide Level / Pasang Surut',
        'seismic_vibration' => 'Seismic / Ground Vibration',
        'ground_movement' => 'Ground Movement / Landslide',
        'soil_moisture' => 'Soil Moisture',
        'river_flow' => 'River Flow / Debit',
        'weather_station' => 'Weather Station',
        'temperature' => 'Temperature',
        'humidity' => 'Humidity',
        'pressure' => 'Pressure',
        'wind_speed' => 'Wind Speed',
        'wind_direction' => 'Wind Direction',
        'battery_bms' => 'Battery / BMS',
        'solar_charger' => 'Solar Charger',
        'device_health' => 'Device Health',
    ];
    $dataLoggerTypes = [
        'float32' => 'Float 32-bit',
        'float64' => 'Float 64-bit / Double',
        'int8' => 'Integer 8-bit',
        'int16' => 'Integer 16-bit',
        'int32' => 'Integer 32-bit',
        'int64' => 'Integer 64-bit',
        'uint8' => 'Unsigned Integer 8-bit',
        'uint16' => 'Unsigned Integer 16-bit',
        'uint32' => 'Unsigned Integer 32-bit',
        'uint64' => 'Unsigned Integer 64-bit',
        'boolean' => 'Boolean',
        'string' => 'String / Text',
        'ascii' => 'ASCII',
        'hex' => 'Hexadecimal',
        'byte' => 'Byte',
        'raw' => 'Raw Payload',
    ];
    $weatherParameters = [
        'temperature' => 'Suhu',
        'humidity' => 'Kelembapan',
        'pressure' => 'Tekanan Udara',
        'wind_speed' => 'Kecepatan Angin',
        'wind_direction' => 'Arah Angin',
        'rainfall' => 'Curah Hujan',
        'solar_radiation' => 'Radiasi Matahari',
        'battery_voltage' => 'Tegangan Baterai',
    ];

    $selectedWorkspace = $clusters->first();
    $selectedMonitoringStation = $monitoringStations->first();
    $selectedWarningStation = $warningStations->first();
    $selectedSensor = $sensors->first();
    $projectMonitoringStartUrl = route('projects.start-monitoring');
    $projectMonitoringStopUrl = route('projects.stop-monitoring');
    $projectMonitoringLiveUrl = route('projects.live-monitoring');
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('message')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo e(session('message')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
    <div class="alert alert-danger">
        <strong>Data belum bisa disimpan.</strong>
        <ul class="mb-0">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <li><?php echo e($error); ?></li>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </ul>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($databaseReady)): ?>
    <div class="alert alert-warning">
        Database setup belum dimigrate. Jalankan <code>php artisan migrate</code> agar form CRUD bisa menyimpan data.
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="card-title mb-1">Project Setup Flow</h4>
                        <p class="text-muted mb-0"><?php echo e($project['name'] ?? 'RESQ Project'); ?> - <?php echo e($project['id'] ?? '-'); ?></p>
                    </div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary"><?php echo e($projects->count()); ?> project</span>
                        <span class="badge bg-success-subtle text-success"><?php echo e($clusters->count()); ?> workspace</span>
                        <span class="badge bg-info-subtle text-info"><?php echo e($monitoringStations->count()); ?> monitoring</span>
                        <span class="badge bg-warning-subtle text-warning"><?php echo e($warningStations->count()); ?> warning</span>
                        <span class="badge bg-danger-subtle text-danger"><?php echo e($sensors->count()); ?> sensor</span>
                    </div>
                </div>

                <ul class="nav nav-tabs nav-tabs-custom flex-wrap" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="project-tab" data-bs-toggle="tab" data-bs-target="#project-tab-pane" type="button" role="tab">Project</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="geospatial-tab" data-bs-toggle="tab" data-bs-target="#geospatial-tab-pane" type="button" role="tab">Geospatial</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="monitoring-tab" data-bs-toggle="tab" data-bs-target="#monitoring-tab-pane" type="button" role="tab">Monitoring Station</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="warning-tab" data-bs-toggle="tab" data-bs-target="#warning-tab-pane" type="button" role="tab">Warning Station</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="data-tab" data-bs-toggle="tab" data-bs-target="#data-tab-pane" type="button" role="tab">Sensor & Data</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="operation-tab" data-bs-toggle="tab" data-bs-target="#operation-tab-pane" type="button" role="tab">Operational & Response</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="user-setup-tab" data-bs-toggle="tab" data-bs-target="#user-setup-tab-pane" type="button" role="tab">User Setup</button></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row" id="project-monitoring-runtime" data-start-url="<?php echo e($projectMonitoringStartUrl); ?>" data-stop-url="<?php echo e($projectMonitoringStopUrl); ?>" data-live-url="<?php echo e($projectMonitoringLiveUrl); ?>">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
                    <div>
                        <h4 class="card-title mb-1">Project Live Monitoring</h4>
                        <p class="text-muted mb-0">Mulai koneksi logger project dan pantau nilai sensor terbaru.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-end gap-2">
                        <div>
                            <label class="form-label mb-1">Project</label>
                            <select class="form-select" id="project-monitor-select" <?php if(! $databaseReady || $projects->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $projects->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <option value="<?php echo e($item['db_id']); ?>"><?php echo e($item['id']); ?> - <?php echo e($item['name']); ?></option>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-success" id="project-monitor-start" <?php if(! $databaseReady || $projects->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>
                            <i class="bx bx-play me-1"></i> Start Monitoring
                        </button>
                        <button type="button" class="btn btn-danger" id="project-monitor-stop" <?php if(! $databaseReady || $projects->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>
                            <i class="bx bx-stop me-1"></i> Stop Monitoring
                        </button>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Logger Online</div><div class="fs-5 fw-bold text-success" id="project-monitor-online">0 / 0</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Sensor Realtime</div><div class="fs-5 fw-bold text-primary" id="project-monitor-fresh">0 / 0</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Refresh</div><div class="fs-5 fw-bold" id="project-monitor-refresh">-</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Status</div><div class="fs-5 fw-bold" id="project-monitor-state">Idle</div></div></div>
                </div>

                <div class="alert alert-info py-2 d-none" id="project-monitor-message"></div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Logger / Station</th>
                                <th>Sensor</th>
                                <th>Value</th>
                                <th>Multi Parameter</th>
                                <th style="width:130px;">Update</th>
                                <th style="width:110px;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="project-monitor-rows">
                            <tr><td colspan="6" class="text-center text-muted py-3">Pilih project lalu klik Start Monitoring.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th style="width:90px;">Time</th><th>Logger</th><th>Message</th><th style="width:90px;">Result</th></tr></thead>
                        <tbody id="project-monitor-log-rows">
                            <tr><td colspan="4" class="text-center text-muted py-2">Belum ada log monitoring.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tab-content text-muted">
    <div class="tab-pane fade show active" id="project-tab-pane" role="tabpanel" aria-labelledby="project-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Set Up New Project</h4>
                        <form method="POST" action="<?php echo e(route('projects.store')); ?>" id="project-form">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label">Project ID</label>
                                <input type="text" name="project_code" class="form-control" value="<?php echo e(old('project_code')); ?>" placeholder="PRJ-RESQ-001" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Project Name</label>
                                <input type="text" name="name" class="form-control" value="<?php echo e(old('name')); ?>" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Project Owner</label>
                                <input type="text" name="owner" class="form-control" value="<?php echo e(old('owner')); ?>" <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Project Date</label>
                                <input type="date" name="project_date" class="form-control" value="<?php echo e(old('project_date')); ?>" <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                            </div>
                            <input type="hidden" name="status" value="Active">
                            <button type="submit" class="btn btn-primary" <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                <i class="bx bx-save me-1"></i> Save / Update Project
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Project List</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Name</th>
                                        <th>Owner</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <tr>
                                            <td><?php echo e($item['id']); ?></td>
                                            <td><?php echo e($item['name']); ?></td>
                                            <td><?php echo e($item['owner']); ?></td>
                                            <td><?php echo e($item['date']); ?></td>
                                            <td><span class="badge bg-success"><?php echo e($item['status']); ?></span></td>
                                            <td class="text-end">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($item['db_id'])): ?>
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#project-form"
                                                            data-edit-fields="<?php echo e(base64_encode(json_encode([
                                                                'project_code' => $item['id'] ?? '',
                                                                'name' => $item['name'] ?? '',
                                                                'owner' => $item['owner'] ?? '',
                                                                'project_date' => $item['date'] ?? '',
                                                                'status' => $item['status'] ?? 'Active',
                                                            ]))); ?>">Edit</button>
                                                        <form method="POST" action="<?php echo e(route('project-setup.destroy', ['type' => 'project', 'id' => $item['db_id']])); ?>">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada project database.</td></tr>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="geospatial-tab-pane" role="tabpanel" aria-labelledby="geospatial-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Add Geospatial Workspace</h4>
                        <form method="POST" action="<?php echo e(route('project-workspaces.store')); ?>" id="workspace-form">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select" required <?php if(! $databaseReady || $projects->isEmpty()): echo 'disabled'; endif; ?>>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($item['db_id']); ?>"><?php echo e($item['id']); ?> - <?php echo e($item['name']); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Workspace ID</label>
                                <input type="text" name="workspace_code" class="form-control" placeholder="CLS-TSU-PDG" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Workspace Name</label>
                                <input type="text" name="name" class="form-control" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Province</label>
                                <select name="province" class="form-select" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $provinces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $province): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($province); ?>"><?php echo e($province); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Hazard</label>
                                    <input type="text" name="hazard" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3"><label class="form-label">Beneficiaries</label><input type="number" name="beneficiaries" class="form-control" value="0" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                                <div class="col-md-4 mb-3"><label class="form-label">Latitude</label><input type="number" step="0.0000001" name="latitude" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                                <div class="col-md-4 mb-3"><label class="form-label">Longitude</label><input type="number" step="0.0000001" name="longitude" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            </div>
                            <input type="hidden" name="status" value="Normal">
                            <button type="submit" class="btn btn-primary" <?php if(! $databaseReady || $projects->isEmpty()): echo 'disabled'; endif; ?>>Save / Update Workspace</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Workspace Listing</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Province</th><th>City</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $clusters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cluster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <tr>
                                            <td><?php echo e($cluster['id']); ?></td>
                                            <td><?php echo e($cluster['name']); ?></td>
                                            <td><?php echo e($cluster['province']); ?></td>
                                            <td><?php echo e($cluster['city']); ?></td>
                                            <td><span class="badge <?php echo e($cluster['status'] === 'Danger' ? 'bg-danger' : 'bg-success'); ?>"><?php echo e($cluster['status']); ?></span></td>
                                            <td class="text-end">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($cluster['db_id'])): ?>
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#workspace-form"
                                                            data-edit-fields="<?php echo e(base64_encode(json_encode([
                                                                'project_id' => $cluster['project_db_id'] ?? '',
                                                                'workspace_code' => $cluster['id'] ?? '',
                                                                'name' => $cluster['name'] ?? '',
                                                                'province' => $cluster['province'] ?? '',
                                                                'hazard' => $cluster['hazard'] ?? '',
                                                                'city' => $cluster['city'] ?? '',
                                                                'beneficiaries' => $cluster['beneficiaries'] ?? 0,
                                                                'latitude' => $cluster['latitude'] ?? '',
                                                                'longitude' => $cluster['longitude'] ?? '',
                                                                'status' => $cluster['status'] ?? 'Normal',
                                                            ]))); ?>">Edit</button>
                                                        <form method="POST" action="<?php echo e(route('project-setup.destroy', ['type' => 'workspace', 'id' => $cluster['db_id']])); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn-outline-danger btn-sm">Delete</button></form>
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
        </div>
    </div>

    <div class="tab-pane fade" id="monitoring-tab-pane" role="tabpanel" aria-labelledby="monitoring-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Monitoring Station Registry</h4>
                        <form method="POST" action="<?php echo e(route('project-monitoring-stations.store')); ?>" id="monitoring-form">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label">Geospatial Workspace</label>
                                <select name="workspace_id" class="form-select" required <?php if(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $clusters->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cluster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($cluster['db_id']); ?>"><?php echo e($cluster['id']); ?> - <?php echo e($cluster['name']); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Station ID</label><input name="station_code" class="form-control" placeholder="MS-PDG-001" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            <div class="mb-3"><label class="form-label">Station Name</label><input name="name" class="form-control" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            <div class="mb-3"><label class="form-label">Coordinate</label><input name="coordinate" class="form-control" placeholder="-0.9200, 100.3600" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Logger ID</label><input name="logger_id" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Connectivity</label><select name="connectivity_status" class="form-select" <?php if(! $databaseReady): echo 'disabled'; endif; ?>><option>Online</option><option>Offline</option></select></div>
                            </div>
                            <input type="hidden" name="logger_status" value="Active">
                            <input type="hidden" name="status" value="Normal">
                            <button type="submit" class="btn btn-primary" <?php if(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>Save / Update Monitoring</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Monitoring Station List</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Workspace</th><th>Logger</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $monitoringStations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <tr>
                                            <td><?php echo e($station['id']); ?></td><td><?php echo e($station['name']); ?></td><td><?php echo e($station['cluster_id']); ?></td><td><?php echo e($station['logger_id']); ?></td>
                                            <td><span class="badge <?php echo e($station['status'] === 'Danger' ? 'bg-danger' : 'bg-success'); ?>"><?php echo e($station['status']); ?></span></td>
                                            <td class="text-end">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($station['db_id'])): ?>
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#monitoring-form"
                                                            data-edit-fields="<?php echo e(base64_encode(json_encode([
                                                                'workspace_id' => $station['workspace_db_id'] ?? '',
                                                                'station_code' => $station['id'] ?? '',
                                                                'name' => $station['name'] ?? '',
                                                                'coordinate' => $station['coordinate'] ?? '',
                                                                'logger_id' => $station['logger_id'] ?? '',
                                                                'connectivity_status' => $station['connectivity_status'] ?? 'Online',
                                                                'logger_status' => $station['logger_status'] ?? 'Active',
                                                                'status' => $station['status'] ?? 'Normal',
                                                            ]))); ?>">Edit</button>
                                                        <form method="POST" action="<?php echo e(route('project-setup.destroy', ['type' => 'monitoring', 'id' => $station['db_id']])); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn-outline-danger btn-sm">Delete</button></form>
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
        </div>
    </div>

    <div class="tab-pane fade" id="warning-tab-pane" role="tabpanel" aria-labelledby="warning-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Warning Station Registry</h4>
                        <form method="POST" action="<?php echo e(route('project-warning-stations.store')); ?>" id="warning-form">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" required <?php if(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $clusters->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cluster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><option value="<?php echo e($cluster['db_id']); ?>"><?php echo e($cluster['id']); ?> - <?php echo e($cluster['name']); ?></option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></select></div>
                            <div class="mb-3"><label class="form-label">Source Monitoring</label><select name="monitoring_station_id" class="form-select" <?php if(! $databaseReady || $monitoringStations->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>><option value="">-</option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $monitoringStations->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><option value="<?php echo e($station['db_id']); ?>"><?php echo e($station['id']); ?> - <?php echo e($station['name']); ?></option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></select></div>
                            <div class="mb-3"><label class="form-label">Warning Station ID</label><input name="station_code" class="form-control" placeholder="WS-PDG-001" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            <div class="mb-3"><label class="form-label">Station Name</label><input name="name" class="form-control" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Zone ID</label><input name="zone_id" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Controller ID</label><input name="controller_id" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Coordinate</label><input name="coordinate" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            <div class="mb-3"><label class="form-label">Controller Model</label><input name="controller_model" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            <div class="mb-3"><label class="form-label">Vendor</label><input name="controller_vendor" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            <div class="mb-3">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['Siren', 'Audio System', 'LED Display', 'Beacon Lamp']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="output_devices[]" value="<?php echo e($device); ?>" id="warningDevice<?php echo e(Str::slug($device)); ?>" <?php if(! $databaseReady): echo 'disabled'; endif; ?>><label class="form-check-label" for="warningDevice<?php echo e(Str::slug($device)); ?>"><?php echo e($device); ?></label></div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                            <input type="hidden" name="controller_status" value="Standby">
                            <input type="hidden" name="status" value="Normal">
                            <button type="submit" class="btn btn-primary" <?php if(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>Save / Update Warning</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Warning Station List</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Workspace</th><th>Source MS</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $warningStations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <tr>
                                            <td><?php echo e($station['id']); ?></td><td><?php echo e($station['name']); ?></td><td><?php echo e($station['cluster_id']); ?></td><td><?php echo e($station['source_monitoring_station_id']); ?></td>
                                            <td><span class="badge <?php echo e($station['status'] === 'Danger' ? 'bg-danger' : 'bg-success'); ?>"><?php echo e($station['status']); ?></span></td>
                                            <td class="text-end">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($station['db_id'])): ?>
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#warning-form"
                                                            data-edit-fields="<?php echo e(base64_encode(json_encode([
                                                                'workspace_id' => $station['workspace_db_id'] ?? '',
                                                                'monitoring_station_id' => $station['monitoring_station_db_id'] ?? '',
                                                                'station_code' => $station['id'] ?? '',
                                                                'name' => $station['name'] ?? '',
                                                                'zone_id' => $station['zone_id'] ?? '',
                                                                'controller_id' => $station['controller_id'] ?? '',
                                                                'coordinate' => $station['coordinate'] ?? '',
                                                                'controller_model' => $station['controller_model'] ?? '',
                                                                'controller_vendor' => $station['controller_vendor'] ?? '',
                                                                'output_devices' => $station['output_devices'] ?? [],
                                                                'controller_status' => $station['controller_status'] ?? 'Standby',
                                                                'status' => $station['status'] ?? 'Normal',
                                                            ]))); ?>">Edit</button>
                                                        <form method="POST" action="<?php echo e(route('project-setup.destroy', ['type' => 'warning', 'id' => $station['db_id']])); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn-outline-danger btn-sm">Delete</button></form>
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
        </div>
    </div>

    <div class="tab-pane fade" id="data-tab-pane" role="tabpanel" aria-labelledby="data-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Sensor & Data Configuration</h4>
                        <form method="POST" action="<?php echo e(route('project-sensors.store')); ?>" id="sensor-form">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" required <?php if(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $clusters->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cluster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><option value="<?php echo e($cluster['db_id']); ?>"><?php echo e($cluster['id']); ?></option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></select></div>
                            <div class="mb-3"><label class="form-label">Monitoring Station</label><select name="monitoring_station_id" class="form-select" required <?php if(! $databaseReady || $monitoringStations->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $monitoringStations->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><option value="<?php echo e($station['db_id']); ?>"><?php echo e($station['id']); ?> - <?php echo e($station['name']); ?></option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></select></div>
	                            <div class="mb-3"><label class="form-label">Warning Station</label><select name="warning_station_id" class="form-select" <?php if(! $databaseReady): echo 'disabled'; endif; ?>><option value="">-</option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $warningStations->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><option value="<?php echo e($station['db_id']); ?>"><?php echo e($station['id']); ?></option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></select></div>
	                            <div class="mb-3"><label class="form-label">Sensor ID</label><input name="sensor_code" class="form-control" placeholder="PS-PDG-01" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
	                            <div class="row">
	                                <div class="col-md-4 mb-3">
		                                    <label class="form-label">Prefix Sensors</label>
		                                    <select name="mst_prefix_id" class="form-select" required <?php if(! $databaseReady || $mstPrefixes->whereNotNull('id')->isEmpty()): echo 'disabled'; endif; ?>>
	                                        <option value="">-</option>
	                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $mstPrefixes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prefix): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
	                                            <?php
	                                                $prefixDbId = data_get($prefix, 'db_id', data_get($prefix, 'id'));
	                                                $prefixCode = data_get($prefix, 'prefix_code', data_get($prefix, 'id'));
	                                                $prefixName = data_get($prefix, 'name');
	                                            ?>
	                                            <option value="<?php echo e($prefixDbId); ?>"><?php echo e($prefixCode); ?><?php echo e($prefixName ? ' - ' . $prefixName : ''); ?></option>
	                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
	                                    </select>
	                                </div>
		                                <div class="col-md-4 mb-3"><label class="form-label">Slave ID</label><input name="slave_id" class="form-control" placeholder="1" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
		                                <div class="col-md-4 mb-3"><label class="form-label">Start Address</label><input name="address" class="form-control" placeholder="0" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
	                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Function Code</label>
                                    <select name="function_code" class="form-select" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                        <option value="FC03">FC03 - Holding Register</option>
                                        <option value="FC04">FC04 - Input Register</option>
                                        <option value="FC01">FC01 - Coils</option>
                                        <option value="FC02">FC02 - Discrete Inputs</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="quantity" class="form-control" value="1" min="1" max="125" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Poll Interval (ms)</label>
                                    <input type="number" name="poll_interval_ms" class="form-control" value="1000" min="250" max="60000" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                </div>
                            </div>
	                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Sensor Type</label>
                                    <select name="type" class="form-select" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensorTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Data Logger Type</label>
                                    <select name="data_type" class="form-select" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $dataLoggerTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Parameter</label><input name="parameter" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Unit</label><input name="unit" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            </div>
                            <div class="mb-3 d-none" id="weather-parameters-wrap">
                                <label class="form-label">Parameter Sensor Cuaca</label>
                                <div class="row g-2">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $weatherParameters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="weather_parameters[]" value="<?php echo e($value); ?>" id="weatherParam<?php echo e(Str::studly($value)); ?>" <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                                <label class="form-check-label" for="weatherParam<?php echo e(Str::studly($value)); ?>"><?php echo e($label); ?></label>
                                            </div>
                                        </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Scale Factor</label><input type="number" step="0.0001" name="scale_factor" class="form-control" value="1" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Offset</label><input type="number" step="0.0001" name="offset" class="form-control" value="0" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Threshold / Rule</label>
                                    <input name="threshold" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Alert Level</label>
                                    <select name="alert_level" class="form-select" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                        <option>Waspada</option>
                                        <option>Siaga</option>
                                        <option>Awas</option>
                                        <option>Normal</option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="reading_method" value="Absolute">
                            <input type="hidden" name="status" value="Normal">
	                            <button type="submit" class="btn btn-primary" <?php if(! $databaseReady || $monitoringStations->whereNotNull('db_id')->isEmpty() || $mstPrefixes->whereNotNull('id')->isEmpty()): echo 'disabled'; endif; ?>>Save / Update Sensor</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Sensor Registry</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
	                                <thead class="table-light"><tr><th>ID</th><th>Type</th><th>Data Type</th><th>Prefix</th><th>Slave</th><th>Start Address</th><th>FC</th><th>Qty</th><th>Poll</th><th>Monitoring</th><th>Warning</th><th></th></tr></thead>
                                <tbody>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sensor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <?php
                                            $sensorWeatherParameters = collect($sensor['weather_parameters'] ?? [])
                                                ->map(fn ($parameter) => $weatherParameters[$parameter] ?? Str::headline($parameter))
                                                ->filter()
                                                ->implode(', ');
                                        ?>
                                        <tr>
	                                            <td><?php echo e($sensor['id']); ?></td><td><div><?php echo e($sensorTypes[$sensor['type'] ?? ''] ?? ($sensor['type'] ?? '-')); ?></div><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sensorWeatherParameters): ?><small class="text-muted"><?php echo e($sensorWeatherParameters); ?></small><?php elseif(! empty($sensor['parameter'])): ?><small class="text-muted"><?php echo e($sensor['parameter']); ?></small><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td><td><?php echo e($dataLoggerTypes[$sensor['data_type'] ?? ''] ?? ($sensor['data_type'] ?? '-')); ?></td><td><?php echo e($sensor['mst_prefix'] ?? '-'); ?></td><td><?php echo e($sensor['slave_id'] ?? '-'); ?></td><td><?php echo e($sensor['address'] ?? '-'); ?></td><td><?php echo e($sensor['function_code'] ?? 'FC03'); ?></td><td><?php echo e($sensor['quantity'] ?? 1); ?></td><td><?php echo e($sensor['poll_interval_ms'] ?? 1000); ?> ms</td><td><?php echo e($sensor['monitoring_station_id']); ?></td><td><?php echo e($sensor['warning_station_id']); ?></td>
                                            <td class="text-end">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($sensor['db_id'])): ?>
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#sensor-form"
                                                            data-edit-fields="<?php echo e(base64_encode(json_encode([
                                                                'workspace_id' => $sensor['workspace_db_id'] ?? '',
                                                                'monitoring_station_id' => $sensor['monitoring_station_db_id'] ?? '',
                                                                'warning_station_id' => $sensor['warning_station_db_id'] ?? '',
                                                                'mst_prefix_id' => $sensor['mst_prefix_db_id'] ?? '',
                                                                'sensor_code' => $sensor['id'] ?? '',
                                                                'slave_id' => $sensor['slave_id'] ?? '',
                                                                'address' => $sensor['address'] ?? '',
                                                                'function_code' => $sensor['function_code'] ?? 'FC03',
                                                                'quantity' => $sensor['quantity'] ?? 1,
                                                                'poll_interval_ms' => $sensor['poll_interval_ms'] ?? 1000,
                                                                'type' => $sensor['type'] ?? 'soil_moisture',
                                                                'data_type' => $sensor['data_type'] ?? 'uint16',
                                                                'parameter' => $sensor['parameter'] ?? '',
                                                                'weather_parameters' => $sensor['weather_parameters'] ?? [],
                                                                'unit' => $sensor['unit'] ?? '',
                                                                'scale_factor' => $sensor['scale_factor'] ?? 1,
                                                                'offset' => $sensor['offset'] ?? 0,
                                                                'threshold' => $sensor['threshold'] ?? '',
                                                                'alert_level' => $sensor['alert_level'] ?? 'Normal',
                                                                'reading_method' => $sensor['reading_method'] ?? 'Absolute',
                                                                'status' => $sensor['status'] ?? 'Normal',
                                                            ]))); ?>">Edit</button>
                                                        <form method="POST" action="<?php echo e(route('project-setup.destroy', ['type' => 'sensor', 'id' => $sensor['db_id']])); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn-outline-danger btn-sm">Delete</button></form>
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
        </div>
    </div>

    <div class="tab-pane fade" id="operation-tab-pane" role="tabpanel" aria-labelledby="operation-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Response Plan / Act</h4>
                        <form method="POST" action="<?php echo e(route('project-response-plans.store')); ?>">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" <?php if(! $databaseReady): echo 'disabled'; endif; ?>><option value="">-</option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $clusters->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cluster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><option value="<?php echo e($cluster['db_id']); ?>"><?php echo e($cluster['id']); ?></option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></select></div>
                            <div class="mb-3"><label class="form-label">Sensor</label><select name="sensor_id" class="form-select" <?php if(! $databaseReady): echo 'disabled'; endif; ?>><option value="">-</option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensors->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sensor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><option value="<?php echo e($sensor['db_id']); ?>"><?php echo e($sensor['id']); ?></option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></select></div>
                            <div class="mb-3"><label class="form-label">Warning Station</label><select name="warning_station_id" class="form-select" <?php if(! $databaseReady): echo 'disabled'; endif; ?>><option value="">-</option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $warningStations->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?><option value="<?php echo e($station['db_id']); ?>"><?php echo e($station['id']); ?></option><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?></select></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="dashboard_notif" value="1" checked <?php if(! $databaseReady): echo 'disabled'; endif; ?>><label class="form-check-label">Dashboard Notif</label></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="sms_blasting" value="1" <?php if(! $databaseReady): echo 'disabled'; endif; ?>><label class="form-check-label">SMS Blasting</label></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="warning_station_act" value="1" <?php if(! $databaseReady): echo 'disabled'; endif; ?>><label class="form-check-label">Warning Station</label></div>
                            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></textarea></div>
                            <button type="submit" class="btn btn-danger" <?php if(! $databaseReady): echo 'disabled'; endif; ?>>Save Response Plan</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Operational Rules</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3"><label class="form-label">Alert Level</label><select class="form-select"><option>Normal</option><option>Waspada</option><option>Siaga</option><option>Awas</option></select></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Reading Method</label><select class="form-select"><option>Absolute</option><option>Accumulative</option><option>Moving Average</option><option>Probability</option></select></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Rule</label><input class="form-control" value="<?php echo e($selectedSensor['threshold'] ?? ''); ?>"></div>
                        </div>
                        <p class="text-muted mb-0">Rule detail tersimpan bersama data sensor dan response plan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="user-setup-tab-pane" role="tabpanel" aria-labelledby="user-setup-tab" tabindex="0">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">User Setup</h4>
                <p class="text-muted mb-0">Account setup masih memakai modul Admin yang sudah ada. Data project scope dapat diarahkan ke project/workspace yang dibuat di tab sebelumnya.</p>
                <a href="<?php echo e(route('admins.index')); ?>" class="btn btn-primary mt-3">Open Account Registry</a>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
    (function () {
        const sensorType = document.querySelector('#sensor-form [name="type"]');
        const weatherWrap = document.getElementById('weather-parameters-wrap');

        if (!sensorType || !weatherWrap) {
            return;
        }

        function syncWeatherParameters() {
            weatherWrap.classList.toggle('d-none', sensorType.value !== 'weather_station');
        }

        sensorType.addEventListener('change', syncWeatherParameters);
        syncWeatherParameters();
    })();

    (function () {
        const root = document.getElementById('project-monitoring-runtime');

        if (!root) {
            return;
        }

        const csrfToken = <?php echo json_encode(csrf_token(), 15, 512) ?>;
        const startUrl = root.dataset.startUrl;
        const stopUrl = root.dataset.stopUrl;
        const liveUrl = root.dataset.liveUrl;
        const select = document.getElementById('project-monitor-select');
        const startButton = document.getElementById('project-monitor-start');
        const stopButton = document.getElementById('project-monitor-stop');
        const rowsEl = document.getElementById('project-monitor-rows');
        const logRowsEl = document.getElementById('project-monitor-log-rows');
        const messageEl = document.getElementById('project-monitor-message');
        const onlineEl = document.getElementById('project-monitor-online');
        const freshEl = document.getElementById('project-monitor-fresh');
        const refreshEl = document.getElementById('project-monitor-refresh');
        const stateEl = document.getElementById('project-monitor-state');
        const logs = [];
        const previousValues = new Map();
        let timer = null;
        let busy = false;

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showMonitorMessage(message, type = 'info') {
            messageEl.className = 'alert py-2 alert-' + type;
            messageEl.textContent = message || '';
            messageEl.classList.toggle('d-none', !message);
        }

        function appendMonitorLog(logger, message, result) {
            logs.unshift({
                time: new Date().toLocaleTimeString(),
                logger,
                message,
                result,
            });

            logRowsEl.innerHTML = logs.slice(0, 30).map((row) => {
                const ok = String(row.result || '').toLowerCase().includes('sukses');
                return '<tr>' +
                    '<td>' + escapeHtml(row.time) + '</td>' +
                    '<td class="fw-bold">' + escapeHtml(row.logger || '-') + '</td>' +
                    '<td>' + escapeHtml(row.message || '-') + '</td>' +
                    '<td><span class="badge ' + (ok ? 'bg-success' : 'bg-danger') + '">' + escapeHtml(row.result || '-') + '</span></td>' +
                '</tr>';
            }).join('');
        }

        function appendMonitorLogLines(logger) {
            const lines = Array.isArray(logger.terminal_log) && logger.terminal_log.length
                ? logger.terminal_log
                : [logger.message];

            lines.slice().reverse().forEach((line) => {
                appendMonitorLog(logger.logger_code, line, logger.ok ? 'Sukses' : 'Gagal');
            });
        }

        function formatTime(value) {
            return value ? new Date(value).toLocaleTimeString() : '-';
        }

        function renderParameterValues(values) {
            if (!Array.isArray(values) || !values.length) {
                return '<span class="text-muted">-</span>';
            }

            return values.map((item) => {
                const label = item.label || item.parameter || '-';
                const value = item.value_text || item.value || '-';

                return '<span class="badge bg-info-subtle text-info border border-info-subtle me-1 mb-1">' +
                    escapeHtml(label) + ': ' + escapeHtml(value) +
                '</span>';
            }).join('');
        }

        function renderLiveData(data) {
            const summary = data.summary || {};
            const sensors = Array.isArray(data.sensors) ? data.sensors : [];

            onlineEl.textContent = (summary.online_loggers || 0) + ' / ' + (summary.loggers || 0);
            freshEl.textContent = (summary.fresh_sensors || 0) + ' / ' + (summary.sensors || 0);
            refreshEl.textContent = formatTime(data.generated_at);
            stateEl.textContent = (summary.online_loggers || 0) > 0 ? 'Running' : 'Menunggu';
            stateEl.className = 'fs-5 fw-bold ' + ((summary.online_loggers || 0) > 0 ? 'text-success' : 'text-muted');
            updateMonitorButtons();

            rowsEl.innerHTML = sensors.length
                ? sensors.map((sensor) => {
                    const key = String(sensor.id);
                    const currentValue = JSON.stringify([sensor.value, sensor.parameter_values, sensor.received_at]);
                    const changed = previousValues.has(key) && previousValues.get(key) !== currentValue;
                    previousValues.set(key, currentValue);

                    const statusLower = String(sensor.status || '').toLowerCase();
                    const badge = sensor.fresh
                        ? (statusLower.includes('awas') ? 'bg-danger' : 'bg-success')
                        : (sensor.online ? 'bg-info' : 'bg-secondary');
                    const valueClass = sensor.fresh ? (changed ? 'text-primary fw-bold' : 'fw-bold') : 'text-muted fw-bold';

                    return '<tr>' +
                        '<td><div class="fw-bold">' + escapeHtml(sensor.logger_code || '-') + '</div><small class="text-muted">' + escapeHtml(sensor.station || '-') + '</small></td>' +
                        '<td><div class="fw-bold">' + escapeHtml(sensor.sensor_code || '-') + '</div><small class="text-muted">' + escapeHtml(sensor.sensor_label || sensor.sensor_type || '-') + '</small></td>' +
                        '<td><span class="' + valueClass + '">' + escapeHtml(sensor.value ?? '-') + '</span></td>' +
                        '<td>' + renderParameterValues(sensor.parameter_values) + '</td>' +
                        '<td>' + escapeHtml(formatTime(sensor.received_at)) + '</td>' +
                        '<td><span class="badge ' + badge + '">' + escapeHtml(sensor.status || '-') + '</span></td>' +
                    '</tr>';
                }).join('')
                : '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada sensor pada project ini.</td></tr>';
        }

        async function loadLiveData() {
            if (!select || !select.value) {
                return;
            }

            const url = new URL(liveUrl, window.location.origin);
            url.searchParams.set('project_id', select.value);
            url.searchParams.set('_', Date.now());
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'Cache-Control': 'no-store' },
                cache: 'no-store',
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.ok === false) {
                throw new Error(data.message || 'Live monitoring gagal dibaca.');
            }

            renderLiveData(data);
        }

        function startPolling() {
            clearInterval(timer);
            loadLiveData().catch((error) => showMonitorMessage(error.message, 'warning'));
            timer = setInterval(() => {
                loadLiveData().catch((error) => showMonitorMessage(error.message, 'warning'));
            }, 2000);
        }

        function stopPolling() {
            clearInterval(timer);
            timer = null;
        }

        function updateMonitorButtons() {
            if (startButton) {
                startButton.disabled = busy || !select || !select.value;
            }

            if (stopButton) {
                stopButton.disabled = busy || !select || !select.value;
            }
        }

        async function submitMonitoringAction(action) {
            if (!select || !select.value) {
                showMonitorMessage('Pilih project dulu.', 'warning');
                return;
            }

            const isStart = action === 'start';
            const button = isStart ? startButton : stopButton;
            const originalText = button.innerHTML;
            busy = true;
            updateMonitorButtons();
            button.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> ' + (isStart ? 'Starting...' : 'Stopping...');
            showMonitorMessage(isStart ? 'Menghubungkan semua logger pada project...' : 'Menghentikan monitoring semua logger pada project...', 'info');

            try {
                const response = await fetch(isStart ? startUrl : stopUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ project_id: select.value }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    (data.loggers || []).forEach((logger) => {
                        appendMonitorLogLines(logger);
                    });
                    throw new Error(data.message || (isStart ? 'Start monitoring gagal.' : 'Stop monitoring gagal.'));
                }

                (data.loggers || []).forEach((logger) => {
                    appendMonitorLogLines(logger);
                });
                showMonitorMessage(data.message + (isStart ? ' Live data akan refresh otomatis.' : ' Monitoring sudah berhenti.'), 'success');

                if (isStart) {
                    startPolling();
                } else {
                    stopPolling();
                    await loadLiveData().catch(() => {});
                    stateEl.textContent = 'Stopped';
                    stateEl.className = 'fs-5 fw-bold text-danger';
                }
            } catch (error) {
                appendMonitorLog('Project', error.message, 'Gagal');
                showMonitorMessage(error.message, 'warning');
            } finally {
                busy = false;
                button.innerHTML = originalText;
                updateMonitorButtons();
            }
        }

        if (startButton) {
            startButton.addEventListener('click', () => submitMonitoringAction('start'));
        }

        if (stopButton) {
            stopButton.addEventListener('click', () => submitMonitoringAction('stop'));
        }

        if (select) {
            select.addEventListener('change', () => {
                previousValues.clear();
                startPolling();
            });
            updateMonitorButtons();
            startPolling();
        }
    })();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/projects/index.blade.php ENDPATH**/ ?>