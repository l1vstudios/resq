<?php $__env->startSection('title'); ?> Warning Stations <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Registered <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Warning Stations <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $warningStations = collect($warningStations ?? config('resq_dummy.warning_stations'));
    $wsControllers = collect($wsControllers ?? config('resq_dummy.ws_controllers'))->keyBy('warning_station_id');
    $totalWarnings = $warningStations->count();
    $publicWarningEnabled = $warningStations->where('public_warning_enabled', true)->count();
    $standbyControllers = $warningStations->filter(fn ($station) => ($wsControllers[$station['id'] ?? '']['controller_status'] ?? $station['controller_status'] ?? '') === 'Standby')->count();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Warning Station</p>
                <h4 class="mb-0"><?php echo e($totalWarnings); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Public Warning Enabled</p>
                <h4 class="mb-0"><?php echo e($publicWarningEnabled); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Controller Standby</p>
                <h4 class="mb-0"><?php echo e($standbyControllers); ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Warning Station Registry</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Warning ID</th>
                                <th>Workspace</th>
                                <th>Source Monitoring</th>
                                <th>Station Name</th>
                                <th>Zone</th>
                                <th>Coordinate</th>
                                <th>Controller</th>
                                <th>Output Devices</th>
                                <th>Public Warning</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $warningStations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <?php
                                    $controller = $wsControllers[$station['id'] ?? ''] ?? [
                                        'id' => $station['controller_id'] ?? '-',
                                        'controller_model' => $station['controller_model'] ?? '-',
                                        'vendor' => $station['controller_vendor'] ?? '-',
                                        'controller_status' => $station['controller_status'] ?? '-',
                                    ];
                                    $outputDevices = collect($station['output_devices'] ?? [])->filter()->implode(', ');
                                ?>
                                <tr>
                                    <td><?php echo e($station['id'] ?? '-'); ?></td>
                                    <td><?php echo e($station['cluster_id'] ?? '-'); ?></td>
                                    <td><?php echo e($station['source_monitoring_station_id'] ?? '-'); ?></td>
                                    <td><?php echo e($station['name'] ?? '-'); ?></td>
                                    <td><?php echo e($station['zone_id'] ?? '-'); ?></td>
                                    <td><?php echo e($station['coordinate'] ?? '-'); ?></td>
                                    <td>
                                        <div><?php echo e($controller['id'] ?? '-'); ?></div>
                                        <small class="text-muted"><?php echo e($controller['controller_model'] ?? '-'); ?> / <?php echo e($controller['controller_status'] ?? '-'); ?></small>
                                    </td>
                                    <td><?php echo e($outputDevices ?: '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo e(($station['public_warning_enabled'] ?? false) ? 'bg-success' : 'bg-secondary'); ?>">
                                            <?php echo e(($station['public_warning_enabled'] ?? false) ? 'Enabled' : 'Disabled'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo e(in_array($station['status'] ?? '', ['Danger', 'Bahaya', 'Awas']) ? 'bg-danger' : (($station['status'] ?? '') === 'Waspada' ? 'bg-warning' : 'bg-success')); ?>">
                                            <?php echo e($station['status'] ?? '-'); ?>

                                        </span>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">Belum ada warning station.</td>
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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/warning-stations/index.blade.php ENDPATH**/ ?>