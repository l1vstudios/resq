<?php $__env->startSection('title'); ?> Data Loggers <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Device Setup <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Data Loggers <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
    $monitoringStations = collect($monitoringStations ?? []);
?>

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Data Logger Setup</h4>
                <form method="POST" action="<?php echo e(route('data-loggers.store')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Monitoring Station</label>
                        <select name="monitoring_station_id" class="form-select">
                            <option value="">-</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $monitoringStations->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($station['db_id']); ?>"><?php echo e($station['id']); ?> - <?php echo e($station['name']); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logger ID</label>
                        <input name="logger_code" class="form-control" placeholder="DL-PDG-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Serial Number</label>
                        <input name="serial_number" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input name="logger_model" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vendor</label>
                            <input name="vendor" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Firmware</label>
                            <input name="firmware_version" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Device Label / QR</label>
                            <input name="device_label" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="logger_status" class="form-select" required>
                            <option>Active</option>
                            <option>Inactive</option>
                            <option>Maintenance</option>
                            <option>Fault</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save / Update Logger</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Data Logger List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Logger ID</th>
                                <th>Monitoring Station</th>
                                <th>Serial Number</th>
                                <th>Model</th>
                                <th>Vendor</th>
                                <th>Firmware</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $dataLoggers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $logger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($logger['id'] ?? '-'); ?></td>
                                    <td><?php echo e($logger['monitoring_station_id'] ?? '-'); ?></td>
                                    <td><?php echo e($logger['serial_number'] ?? '-'); ?></td>
                                    <td><?php echo e($logger['logger_model'] ?? '-'); ?></td>
                                    <td><?php echo e($logger['vendor'] ?? '-'); ?></td>
                                    <td><?php echo e($logger['firmware_version'] ?? '-'); ?></td>
                                    <td><span class="badge <?php echo e(($logger['logger_status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary'); ?>"><?php echo e($logger['logger_status'] ?? '-'); ?></span></td>
                                    <td class="text-end">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($logger['db_id'])): ?>
                                            <form method="POST" action="<?php echo e(route('device-setup.destroy', ['type' => 'data-logger', 'id' => $logger['db_id']])); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada data logger.</td>
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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/data-loggers/index.blade.php ENDPATH**/ ?>