<?php $__env->startSection('title'); ?> Credential / Authentication <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Devices <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Credential / Authentication <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $credentials = config('resq_dummy.credentials');
?>

<div class="row">
    <div class="col-12">
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
                                <th>Password Hash</th>
                                <th>Certificate Ref</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Revoked At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $credentials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $credential): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($credential['id']); ?></td>
                                    <td><?php echo e($credential['logger_id']); ?></td>
                                    <td><code><?php echo e($credential['device_token']); ?></code></td>
                                    <td><?php echo e($credential['mqtt_username']); ?></td>
                                    <td><code><?php echo e($credential['mqtt_password_hash']); ?></code></td>
                                    <td><?php echo e($credential['certificate_ref']); ?></td>
                                    <td><span class="badge bg-success"><?php echo e($credential['credential_status']); ?></span></td>
                                    <td><?php echo e($credential['created_at']); ?></td>
                                    <td><?php echo e($credential['revoked_at'] ?? '-'); ?></td>
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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/credentials/index.blade.php ENDPATH**/ ?>