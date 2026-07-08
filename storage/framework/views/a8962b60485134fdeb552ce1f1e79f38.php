<?php $__env->startSection('title'); ?> Connectivity <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Device Setup <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Connectivity <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $connectivity = collect($connectivity ?? config('resq_dummy.connectivity'));
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
?>

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Connectivity Setup</h4>
                <form method="POST" action="<?php echo e(route('connectivity.store')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Data Logger</label>
                        <select name="data_logger_id" class="form-select" required <?php if($dataLoggers->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $dataLoggers->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $logger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($logger['db_id']); ?>"><?php echo e($logger['id']); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Connectivity ID</label>
                        <input name="connectivity_code" class="form-control" placeholder="CONN-PDG-001" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="communication_type" class="form-select">
                                <option>Cellular</option>
                                <option>Ethernet</option>
                                <option>WiFi</option>
                                <option>LoRa</option>
                                <option>Satellite</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Protocol</label>
                            <select name="protocol" class="form-select">
                                <option>MQTT</option>
                                <option>HTTP</option>
                                <option>Modbus TCP</option>
                                <option>TCP</option>
                                <option>UDP</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Host / Endpoint</label>
                        <input name="host_or_endpoint" class="form-control" placeholder="mqtt.resq.local">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Port</label>
                            <input type="number" name="port" class="form-control" placeholder="1883">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Topic / API Path</label>
                            <input name="topic_or_api_path" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Gateway</label><input name="gateway_id" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">SIM</label><input name="sim_number" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">IMEI</label><input name="imei" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">APN</label><input name="apn" class="form-control"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="connectivity_status" class="form-select" required>
                            <option>Online</option>
                            <option>Offline</option>
                            <option>Degraded</option>
                            <option>Maintenance</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" <?php if($dataLoggers->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>Save / Update Connectivity</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Connectivity List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Connectivity ID</th>
                                <th>Logger ID</th>
                                <th>Type</th>
                                <th>Protocol</th>
                                <th>Host / Endpoint</th>
                                <th>Port</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $connectivity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($item['id'] ?? '-'); ?></td>
                                    <td><?php echo e($item['logger_id'] ?? '-'); ?></td>
                                    <td><?php echo e($item['communication_type'] ?? '-'); ?></td>
                                    <td><?php echo e($item['protocol'] ?? '-'); ?></td>
                                    <td><?php echo e($item['host_or_endpoint'] ?? '-'); ?></td>
                                    <td><?php echo e($item['port'] ?? '-'); ?></td>
                                    <td><span class="badge <?php echo e(($item['connectivity_status'] ?? '') === 'Online' ? 'bg-success' : 'bg-secondary'); ?>"><?php echo e($item['connectivity_status'] ?? '-'); ?></span></td>
                                    <td class="text-end">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($item['db_id'])): ?>
                                            <form method="POST" action="<?php echo e(route('device-setup.destroy', ['type' => 'connectivity', 'id' => $item['db_id']])); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada connectivity.</td>
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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/connectivity/index.blade.php ENDPATH**/ ?>