<?php $__env->startSection('title'); ?> Sensors <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Monitoring <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Sensors <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $monitoringStations = config('resq_dummy.monitoring_stations');
    $sensors = config('resq_dummy.sensors');
    $dangerSensor = collect($sensors)->firstWhere('status', 'Danger');
?>

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Add Sensor</h4>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Monitoring Station</label>
                        <select class="form-select">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $monitoringStations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option><?php echo e($station['id']); ?> - <?php echo e($station['name']); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type Of Sensor</label>
                        <input type="text" class="form-control" value="<?php echo e($dangerSensor['type']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model Of Sensor</label>
                        <input type="text" class="form-control" placeholder="Model">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vendor</label>
                        <input type="text" class="form-control" placeholder="Vendor">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Manufacturing</label>
                            <input type="date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Installation</label>
                            <input type="date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Active</label>
                        <input type="date" class="form-control">
                    </div>
                    <button type="button" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Save Sensor
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Sensor Validation</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sensor</th>
                                <th>Monitoring ID</th>
                                <th>Warning ID</th>
                                <th>Parameter</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $sensors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sensor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td>
                                        <div><?php echo e($sensor['id']); ?></div>
                                        <small class="text-muted"><?php echo e($sensor['type']); ?></small>
                                    </td>
                                    <td><?php echo e($sensor['monitoring_station_id']); ?></td>
                                    <td><?php echo e($sensor['warning_station_id']); ?></td>
                                    <td><?php echo e($sensor['parameter']); ?></td>
                                    <td>
                                        <span class="badge <?php echo e($sensor['status'] === 'Danger' ? 'bg-danger' : 'bg-success'); ?>">
                                            <?php echo e($sensor['status']); ?>

                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sensor['status'] === 'Danger'): ?>
                                            <a href="<?php echo e(route('warning-stations.index')); ?>#command-test" class="btn btn-danger btn-sm">
                                                <i class="bx bx-broadcast me-1"></i> Send warning
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-light btn-sm">
                                                <i class="bx bx-check-circle me-1"></i> Normal
                                            </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                <h4 class="card-title mb-4">RS485 / Modbus Configuration</h4>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Slave ID</label>
                        <input type="number" class="form-control" placeholder="1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Baudrate</label>
                        <select class="form-select">
                            <option>9600</option>
                            <option>19200</option>
                            <option>38400</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Register Mapping Profile</label>
                        <input type="text" class="form-control" placeholder="profile_water_level_v1">
                    </div>
                </div>
                <button type="button" class="btn btn-primary">
                    <i class="bx bx-cog me-1"></i> Run Sensor Validation
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/sensors/index.blade.php ENDPATH**/ ?>