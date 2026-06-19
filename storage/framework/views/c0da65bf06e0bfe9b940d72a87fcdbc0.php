<?php $__env->startSection('title'); ?> Monitoring Stations <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Stations <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Monitoring Stations <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $clusters = collect(config('resq_dummy.clusters'))->keyBy('id');
    $monitoringStations = config('resq_dummy.monitoring_stations');
    $sensors = collect(config('resq_dummy.sensors'))->groupBy('monitoring_station_id');
    $dangerStation = collect($monitoringStations)->firstWhere('status', 'Danger');
?>

<div class="row">
    <div class="col-xl-5 mb-4">
        <div class="card h-100 mb-0">
            <div class="card-body">
                <h4 class="card-title mb-4">Monitoring Station Identity</h4>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Cluster</label>
                        <select class="form-select">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $clusters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cluster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option><?php echo e($cluster['id']); ?> - <?php echo e($cluster['name']); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Warning Station ID</label>
                            <input type="text" class="form-control" value="<?php echo e($dangerStation['id']); ?>" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Station Name</label>
                            <input type="text" class="form-control" value="<?php echo e($dangerStation['name']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Coordinate</label>
                        <input type="text" class="form-control" value="<?php echo e($dangerStation['coordinate']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select">
                            <option>Monitoring</option>
                            <option>Dissemination / Warning</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Save Station
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7 mb-4">
        <div class="card h-100 mb-0">
            <div class="card-body">
                <h4 class="card-title mb-4">Linked Device Configuration</h4>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <p class="text-muted mb-1">Data Logger</p>
                                <h6 class="mb-3"><?php echo e($dangerStation['logger_id']); ?></h6>
                            </div>
                            <a href="<?php echo e(route('data-loggers.index')); ?>" class="btn btn-light btn-sm w-100 text-start">
                                <i class="bx bx-data me-1"></i> Open Data Loggers
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <p class="text-muted mb-1">Connectivity</p>
                                <h6 class="mb-3"><?php echo e($dangerStation['connectivity_status']); ?></h6>
                            </div>
                            <a href="<?php echo e(route('connectivity.index')); ?>" class="btn btn-light btn-sm w-100 text-start">
                                <i class="bx bx-wifi me-1"></i> Open Connectivity
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <p class="text-muted mb-1">Credential / Auth</p>
                                <h6 class="mb-3">Linked to <?php echo e($dangerStation['logger_id']); ?></h6>
                            </div>
                            <a href="<?php echo e(route('credentials.index')); ?>" class="btn btn-light btn-sm w-100 text-start">
                                <i class="bx bx-key me-1"></i> Open Credentials
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Sensor Assignment</h4>
                    <a href="<?php echo e(route('sensors.index')); ?>" class="btn btn-light btn-sm">
                        <i class="bx bx-slider-alt me-1"></i> Sensor Validation
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sensor ID & Type</th>
                                <th>Slave ID (Station ID)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = ($sensors[$dangerStation['id']] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sensor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><strong><?php echo e($sensor['id']); ?></strong> - <?php echo e($sensor['type']); ?></td>
                                    <td><?php echo e($sensor['monitoring_station_id']); ?></td>
                                    <td>
                                        <span class="badge <?php echo e($sensor['status'] === 'Danger' ? 'bg-danger' : 'bg-success'); ?>">
                                            <?php echo e($sensor['status']); ?>

                                        </span>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No sensors assigned to this station.</td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Monitoring Station List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Station ID</th>
                                <th>Cluster</th>
                                <th>Logger</th>
                                <th>Warning ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $monitoringStations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($station['id']); ?></td>
                                    <td><?php echo e($station['cluster_id']); ?></td>
                                    <td><?php echo e($station['logger_id']); ?></td>
                                    <td><?php echo e($station['warning_station_id']); ?></td>
                                    <td>
                                        <span class="badge <?php echo e($station['status'] === 'Danger' ? 'bg-danger' : 'bg-success'); ?>">
                                            <?php echo e($station['status']); ?>

                                        </span>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/monitoring-stations/index.blade.php ENDPATH**/ ?>