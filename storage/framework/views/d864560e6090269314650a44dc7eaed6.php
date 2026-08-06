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
        'humidity' => 'Kelembapan',
        'pressure' => 'Tekanan Udara',
        'wind_speed' => 'Kecepatan Angin',
        'wind_direction' => 'Arah Angin',
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
                    <li class="nav-item" role="presentation"><button class="nav-link" id="canonical-tab" data-bs-toggle="tab" data-bs-target="#canonical-tab-pane" type="button" role="tab">Canonical Data</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="operation-tab" data-bs-toggle="tab" data-bs-target="#operation-tab-pane" type="button" role="tab">Operational & Response</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="user-setup-tab" data-bs-toggle="tab" data-bs-target="#user-setup-tab-pane" type="button" role="tab">User Setup</button></li>
                </ul>
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
                <div class="card mb-3">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Data Logger Setup</h4>
                        <form method="POST" action="<?php echo e(route('data-loggers.store')); ?>" id="project-data-logger-form">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label">Monitoring Station</label>
                                <select name="monitoring_station_id" class="form-select" required <?php if(! $databaseReady || $monitoringStations->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $monitoringStations->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($station['db_id']); ?>"><?php echo e($station['id']); ?> - <?php echo e($station['name']); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logger ID</label>
                                <input name="logger_code" class="form-control" placeholder="DL-PDG-001" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Serial Number</label><input name="serial_number" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Model</label><input name="logger_model" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Vendor</label><input name="vendor" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Firmware</label><input name="firmware_version" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Device Label / QR</label><input name="device_label" class="form-control" <?php if(! $databaseReady): echo 'disabled'; endif; ?>></div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="logger_status" class="form-select" required <?php if(! $databaseReady): echo 'disabled'; endif; ?>>
                                    <option>Active</option>
                                    <option>Inactive</option>
                                    <option>Maintenance</option>
                                    <option>Fault</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" <?php if(! $databaseReady || $monitoringStations->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>Save / Update Logger</button>
                        </form>
                    </div>
                </div>

                <div class="card">
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
                        <h5 class="font-size-14 mb-3">Data Logger Registry</h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>Logger ID</th><th>Monitoring</th><th>Serial</th><th>Model</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $dataLoggers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $logger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <tr>
                                            <td><?php echo e($logger['id'] ?? '-'); ?></td>
                                            <td><?php echo e($logger['monitoring_station_id'] ?? '-'); ?></td>
                                            <td><?php echo e($logger['serial_number'] ?? '-'); ?></td>
                                            <td><?php echo e($logger['logger_model'] ?? '-'); ?></td>
                                            <td><span class="badge <?php echo e(($logger['logger_status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary'); ?>"><?php echo e($logger['logger_status'] ?? '-'); ?></span></td>
                                            <td class="text-end">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($logger['db_id'])): ?>
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#project-data-logger-form"
                                                            data-edit-fields="<?php echo e(base64_encode(json_encode([
                                                                'monitoring_station_id' => $logger['monitoring_station_db_id'] ?? '',
                                                                'logger_code' => $logger['id'] ?? '',
                                                                'serial_number' => $logger['serial_number'] ?? '',
                                                                'logger_model' => $logger['logger_model'] ?? '',
                                                                'vendor' => $logger['vendor'] ?? '',
                                                                'firmware_version' => $logger['firmware_version'] ?? '',
                                                                'device_label' => $logger['device_label'] ?? '',
                                                                'logger_status' => $logger['logger_status'] ?? 'Active',
                                                            ]))); ?>">Edit</button>
                                                        <form method="POST" action="<?php echo e(route('device-setup.destroy', ['type' => 'data-logger', 'id' => $logger['db_id']])); ?>">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada data logger.</td></tr>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
	                                <thead class="table-light"><tr><th>ID</th><th>Type</th><th>Data Type</th><th>Prefix</th><th>Slave</th><th>Start Address</th><th>FC</th><th>Qty</th><th>Poll</th><th>Monitoring</th><th>Warning</th><th>Canonical</th><th></th></tr></thead>
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
                                            <td>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sensor['is_canonical_mapped'] ?? false): ?>
                                                    <span class="badge bg-success-subtle text-success">Sudah Mapping</span>
                                                <?php else: ?>
                                                    <div class="d-flex flex-column align-items-start gap-2">
                                                        <span class="badge bg-danger-subtle text-danger">Belum Mapping Canonical</span>
                                                        <a href="<?php echo e(route('canonical-database.index')); ?>#mapping" class="btn btn-outline-danger btn-sm">
                                                            Mapping
                                                        </a>
                                                    </div>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </td>
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

    <div class="tab-pane fade" id="canonical-tab-pane" role="tabpanel" aria-labelledby="canonical-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Canonical Data Mapping</h4>
                        <form method="POST" action="<?php echo e(route('canonical-mapping.store')); ?>" id="canonical-mapping-form">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label">Sensor</label>
                                <select class="form-select" name="sensor_id" required>
                                    <option value="" disabled selected>Pilih Sensor...</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensors->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($s['db_id']); ?>"><?php echo e($s['id']); ?> - <?php echo e($sensorTypes[$s['type'] ?? ''] ?? ($s['type'] ?? '-')); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Profile Code</label>
                                <input type="text" class="form-control" name="profile_code" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Canonical Parameter</label>
                                <select class="form-select" name="canonical_parameter_id" required>
                                    <option value="" disabled selected>Pilih Parameter...</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $canonicalParameters ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($cp->id); ?>"><?php echo e($cp->domain); ?> / <?php echo e($cp->field_identity); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Source Parameter (Opsional)</label>
                                <input type="text" class="form-control" name="source_parameter">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Value Origin</label>
                                <select class="form-select" name="value_origin" required>
                                    <option value="direct_measurement">Direct Measurement</option>
                                    <option value="device_processed">Device Processed</option>
                                    <option value="system_calculated">System Calculated</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Scale Factor</label>
                                    <input type="number" step="0.0001" class="form-control" name="scale_factor" value="1" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Offset</label>
                                    <input type="number" step="0.0001" class="form-control" name="offset" value="0" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Mapping</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Mapping List</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Profile Code</th>
                                        <th>Sensor</th>
                                        <th>Canonical Parameter</th>
                                        <th>Origin</th>
                                        <th>Scale/Offset</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $sensorMappingProfiles ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <tr>
                                        <td><?php echo e($profile->profile_code); ?></td>
                                        <td><?php echo e($profile->sensor->sensor_code ?? '-'); ?></td>
                                        <td><?php echo e($profile->canonicalParameter->field_identity ?? '-'); ?></td>
                                        <td><?php echo e(str_replace('_', ' ', Str::title($profile->value_origin))); ?></td>
                                        <td><?php echo e($profile->scale_factor ?? 1); ?> / <?php echo e($profile->offset ?? 0); ?></td>
                                        <td>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($profile->status === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    data-edit-form="#canonical-mapping-form"
                                                    data-edit-fields="<?php echo e(base64_encode(json_encode([
                                                        'sensor_id' => $profile->sensor_id,
                                                        'profile_code' => $profile->profile_code,
                                                        'canonical_parameter_id' => $profile->canonical_parameter_id,
                                                        'source_parameter' => $profile->source_parameter ?? '',
                                                        'value_origin' => $profile->value_origin,
                                                        'scale_factor' => $profile->scale_factor ?? 1,
                                                        'offset' => $profile->offset ?? 0,
                                                        'status' => $profile->status,
                                                    ]))); ?>">Edit</button>
                                                <form method="POST" action="<?php echo e(route('canonical-mapping.destroy', $profile->id)); ?>">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada mapping Canonical Data.</td>
                                    </tr>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
        const quantity = document.querySelector('#sensor-form [name="quantity"]');
        const parameter = document.querySelector('#sensor-form [name="parameter"]');
        const weatherWrap = document.getElementById('weather-parameters-wrap');

        if (!sensorType || !weatherWrap) {
            return;
        }

        function defaultWeatherParameterValues() {
            const base = ['temperature', 'humidity', 'pressure', 'wind_speed', 'wind_direction', 'rainfall', 'solar_radiation', 'battery_voltage'];
            const hint = String(parameter?.value || '').toLowerCase();

            if (hint.includes('angin') || hint.includes('wind')) {
                return ['wind_speed', 'wind_direction'].concat(base.filter((item) => !['wind_speed', 'wind_direction'].includes(item)));
            }

            if (hint.includes('hujan') || hint.includes('rain')) {
                return ['rainfall'].concat(base.filter((item) => item !== 'rainfall'));
            }

            return base;
        }

        function applyDefaultWeatherChecks() {
            const checks = Array.from(weatherWrap.querySelectorAll('input[type="checkbox"]'));
            const selected = checks.filter((check) => check.checked);

            if (sensorType.value !== 'weather_station' || selected.length) {
                return;
            }

            const limit = Math.max(parseInt(quantity?.value || '1', 10) || 1, 1);
            const defaults = defaultWeatherParameterValues().slice(0, limit);
            checks.forEach((check) => {
                check.checked = defaults.includes(check.value);
            });
        }

        function syncWeatherParameters() {
            weatherWrap.classList.toggle('d-none', sensorType.value !== 'weather_station');
            applyDefaultWeatherChecks();
        }

        sensorType.addEventListener('change', syncWeatherParameters);
        quantity?.addEventListener('change', applyDefaultWeatherChecks);
        parameter?.addEventListener('input', applyDefaultWeatherChecks);
        syncWeatherParameters();
    })();

</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/projects/index.blade.php ENDPATH**/ ?>