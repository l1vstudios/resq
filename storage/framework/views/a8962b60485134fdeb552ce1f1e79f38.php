<?php $__env->startSection('title'); ?> Connectivity <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Devices <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Connectivity <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $connectivity = config('resq_dummy.connectivity');
?>

<div class="row">
    <div class="col-12">
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
                                <th>Topic / API Path</th>
                                <th>Gateway</th>
                                <th>SIM</th>
                                <th>IMEI</th>
                                <th>APN</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $connectivity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($item['id']); ?></td>
                                    <td><?php echo e($item['logger_id']); ?></td>
                                    <td><?php echo e($item['communication_type']); ?></td>
                                    <td><?php echo e($item['protocol']); ?></td>
                                    <td><?php echo e($item['host_or_endpoint']); ?></td>
                                    <td><?php echo e($item['port']); ?></td>
                                    <td><?php echo e($item['topic_or_api_path']); ?></td>
                                    <td><?php echo e($item['gateway_id']); ?></td>
                                    <td><?php echo e($item['sim_number']); ?></td>
                                    <td><?php echo e($item['imei']); ?></td>
                                    <td><?php echo e($item['apn']); ?></td>
                                    <td><span class="badge bg-success"><?php echo e($item['connectivity_status']); ?></span></td>
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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/connectivity/index.blade.php ENDPATH**/ ?>