<?php $__env->startSection('title'); ?> Monitoring Stations <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Registered <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Monitoring Stations <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $monitoringStations = collect($monitoringStations ?? config('resq_dummy.monitoring_stations'));
    $sensorsByStation = collect($sensors ?? config('resq_dummy.sensors'))->groupBy('monitoring_station_id');
    $totalStations = $monitoringStations->count();
    $onlineStations = $monitoringStations->where('connectivity_status', 'Online')->count();
    $activeLoggers = $monitoringStations->where('logger_status', 'Active')->count();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Monitoring Station</p>
                <h4 class="mb-0"><?php echo e($totalStations); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Logger Active</p>
                <h4 class="mb-0"><?php echo e($activeLoggers); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Connectivity Online</p>
                <h4 class="mb-0"><?php echo e($onlineStations); ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Monitoring Station Registry</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Station ID</th>
                                <th>Workspace</th>
                                <th>Station Name</th>
                                <th>Coordinate</th>
                                <th>Data Logger</th>
                                <th>Logger Status</th>
                                <th>Connectivity</th>
                                <th>Warning Station</th>
                                <th>Sensors</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $monitoringStations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($station['id'] ?? '-'); ?></td>
                                    <td><?php echo e($station['cluster_id'] ?? '-'); ?></td>
                                    <td><?php echo e($station['name'] ?? '-'); ?></td>
                                    <td><?php echo e($station['coordinate'] ?? '-'); ?></td>
                                    <td><?php echo e($station['logger_id'] ?? '-'); ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo e($station['logger_status'] ?? '-'); ?></span></td>
                                    <td><span class="badge <?php echo e(($station['connectivity_status'] ?? '') === 'Online' ? 'bg-success' : 'bg-secondary'); ?>"><?php echo e($station['connectivity_status'] ?? '-'); ?></span></td>
                                    <td><?php echo e($station['warning_station_id'] ?? '-'); ?></td>
                                    <td><?php echo e(($sensorsByStation[$station['id'] ?? ''] ?? collect())->count()); ?></td>
                                    <td>
                                        <span class="badge <?php echo e(in_array($station['status'] ?? '', ['Danger', 'Bahaya', 'Awas']) ? 'bg-danger' : (($station['status'] ?? '') === 'Waspada' ? 'bg-warning' : 'bg-success')); ?>">
                                            <?php echo e($station['status'] ?? '-'); ?>

                                        </span>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">Belum ada monitoring station.</td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/monitoring-stations/index.blade.php ENDPATH**/ ?>