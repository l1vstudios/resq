<?php $__env->startSection('title'); ?> Telemetry Configuration <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Telemetry <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Telemetry Configuration <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $sensors = collect($sensors ?? config('resq_dummy.sensors'));
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
    $telemetryReadings = collect($telemetryReadings ?? []);
    $alertClass = fn ($level) => in_array($level, ['Awas', 'Danger']) ? 'bg-danger' : ($level === 'Siaga' ? 'bg-warning' : ($level === 'Waspada' ? 'bg-info' : 'bg-success'));
?>

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Telemetry Reading</h4>
                <form method="POST" action="<?php echo e(route('telemetry.store')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Sensor</label>
                        <select name="sensor_id" class="form-select" required <?php if($sensors->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensors->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sensor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($sensor['db_id']); ?>"><?php echo e($sensor['id']); ?> - <?php echo e($sensor['parameter'] ?? $sensor['type']); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data Logger</label>
                        <select name="data_logger_id" class="form-select">
                            <option value="">-</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $dataLoggers->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $logger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($logger['db_id']); ?>"><?php echo e($logger['id']); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <input name="value" class="form-control" placeholder="2.8 m">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Alert Level</label>
                            <select name="alert_level" class="form-select" required>
                                <option>Normal</option>
                                <option>Waspada</option>
                                <option>Siaga</option>
                                <option>Awas</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option>Normal</option>
                                <option>Waspada</option>
                                <option>Siaga</option>
                                <option>Awas</option>
                                <option>Danger</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Received At</label>
                        <input type="datetime-local" name="received_at" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary" <?php if($sensors->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>
                        Save Telemetry
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Latest Sensor State</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Station</th>
                                <th>Sensor</th>
                                <th>Value</th>
                                <th>Threshold</th>
                                <th>Alert</th>
                                <th>Status</th>
                                <th>Last Seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $sensors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sensor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($sensor['monitoring_station_id'] ?? '-'); ?></td>
                                    <td><?php echo e($sensor['id'] ?? '-'); ?></td>
                                    <td><?php echo e($sensor['value'] ?? '-'); ?></td>
                                    <td><?php echo e($sensor['threshold'] ?? '-'); ?></td>
                                    <td><span class="badge <?php echo e($alertClass($sensor['alert_level'] ?? 'Normal')); ?>"><?php echo e($sensor['alert_level'] ?? 'Normal'); ?></span></td>
                                    <td><span class="badge <?php echo e($alertClass($sensor['status'] ?? 'Normal')); ?>"><?php echo e($sensor['status'] ?? 'Normal'); ?></span></td>
                                    <td><?php echo e($sensor['last_seen'] ?? '-'); ?></td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada sensor.</td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Telemetry History</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Received</th>
                                <th>Logger</th>
                                <th>Station</th>
                                <th>Sensor</th>
                                <th>Value</th>
                                <th>Alert</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $telemetryReadings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($reading['received_at'] ?? '-'); ?></td>
                                    <td><?php echo e($reading['data_logger_id'] ?? '-'); ?></td>
                                    <td><?php echo e($reading['monitoring_station_id'] ?? '-'); ?></td>
                                    <td><?php echo e($reading['sensor_id'] ?? '-'); ?></td>
                                    <td><?php echo e($reading['value'] ?? '-'); ?></td>
                                    <td><span class="badge <?php echo e($alertClass($reading['alert_level'] ?? 'Normal')); ?>"><?php echo e($reading['alert_level'] ?? 'Normal'); ?></span></td>
                                    <td><span class="badge <?php echo e($alertClass($reading['status'] ?? 'Normal')); ?>"><?php echo e($reading['status'] ?? 'Normal'); ?></span></td>
                                    <td class="text-end">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($reading['db_id'])): ?>
                                            <form method="POST" action="<?php echo e(route('device-setup.destroy', ['type' => 'telemetry', 'id' => $reading['db_id']])); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada telemetry history.</td>
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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/telemetry/index.blade.php ENDPATH**/ ?>