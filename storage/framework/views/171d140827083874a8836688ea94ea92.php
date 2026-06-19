<?php $__env->startSection('title'); ?> Telemetry Configuration <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Configuration <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Telemetry <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $sensors = config('resq_dummy.sensors');
?>

<div class="row">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Telemetry Configuration</h4>
                    <button type="button" class="btn btn-primary btn-sm">
                        <i class="bx bx-refresh me-1"></i> Telemetry Test
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Station</th>
                                <th>Sensor</th>
                                <th>Value</th>
                                <th>Threshold</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sensor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($sensor['monitoring_station_id']); ?></td>
                                    <td><?php echo e($sensor['id']); ?></td>
                                    <td><?php echo e($sensor['value']); ?></td>
                                    <td><?php echo e($sensor['threshold']); ?></td>
                                    <td>
                                        <span class="badge <?php echo e($sensor['status'] === 'Danger' ? 'bg-danger' : 'bg-success'); ?>">
                                            <?php echo e($sensor['status']); ?>

                                        </span>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Hazard Level Classification</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Level</th>
                                <th>Parameter</th>
                                <th>Threshold</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sensor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo e($sensor['status'] === 'Danger' ? 'bg-danger' : 'bg-success'); ?>">
                                            <?php echo e($sensor['status']); ?>

                                        </span>
                                    </td>
                                    <td><?php echo e($sensor['parameter']); ?></td>
                                    <td><?php echo e($sensor['threshold']); ?></td>
                                    <td>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sensor['status'] === 'Danger'): ?>
                                            Trigger <?php echo e($sensor['warning_station_id']); ?> and send warning to public
                                        <?php else: ?>
                                            Store telemetry only
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

    <div class="col-xl-5">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Power & System Monitoring</h4>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Controller / Logger</label>
                        <select class="form-select">
                            <option>DL-0001</option>
                            <option>WS-CTRL-001</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Device Type</label>
                        <select class="form-select">
                            <option>Smart Battery / BMS</option>
                            <option>Solar Charger</option>
                            <option>Power Controller</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Communication Type</label>
                            <select class="form-select">
                                <option>RS485 / Modbus</option>
                                <option>Analog</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slave ID</label>
                            <input type="number" class="form-control" placeholder="3">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Register Mapping Profile</label>
                        <input type="text" class="form-control" placeholder="bms_profile_v1">
                    </div>
                    <button type="button" class="btn btn-primary">
                        <i class="bx bx-battery me-1"></i> Save Monitoring Profile
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/telemetry/index.blade.php ENDPATH**/ ?>