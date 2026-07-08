<?php $__env->startSection('title'); ?> Credentials <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Device Setup <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Credentials <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $credentials = collect($credentials ?? config('resq_dummy.credentials'));
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
?>

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Credential Setup</h4>
                <form method="POST" action="<?php echo e(route('credentials.store')); ?>">
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
                        <label class="form-label">Credential ID</label>
                        <input name="credential_code" class="form-control" placeholder="CRED-PDG-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Device Token / API Key</label>
                        <input name="device_token" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">MQTT Username</label><input name="mqtt_username" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Password Hash</label><input name="mqtt_password_hash" class="form-control"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Certificate Ref</label>
                        <input name="certificate_ref" class="form-control" placeholder="cert/MS-PDG-001.pem">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="credential_status" class="form-select" required>
                                <option>Active</option>
                                <option>Revoked</option>
                                <option>Expired</option>
                                <option>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Revoked At</label>
                            <input type="datetime-local" name="revoked_at" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" <?php if($dataLoggers->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>Save / Update Credential</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Credential / Authentication List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Credential ID</th>
                                <th>Logger ID</th>
                                <th>Device Token / API Key</th>
                                <th>MQTT Username</th>
                                <th>Certificate Ref</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $credentials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $credential): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($credential['id'] ?? '-'); ?></td>
                                    <td><?php echo e($credential['logger_id'] ?? '-'); ?></td>
                                    <td><code><?php echo e($credential['device_token'] ?? '-'); ?></code></td>
                                    <td><?php echo e($credential['mqtt_username'] ?? '-'); ?></td>
                                    <td><?php echo e($credential['certificate_ref'] ?? '-'); ?></td>
                                    <td><span class="badge <?php echo e(($credential['credential_status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary'); ?>"><?php echo e($credential['credential_status'] ?? '-'); ?></span></td>
                                    <td><?php echo e($credential['created_at'] ?? '-'); ?></td>
                                    <td class="text-end">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($credential['db_id'])): ?>
                                            <form method="POST" action="<?php echo e(route('device-setup.destroy', ['type' => 'credential', 'id' => $credential['db_id']])); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Belum ada credential.</td>
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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/credentials/index.blade.php ENDPATH**/ ?>