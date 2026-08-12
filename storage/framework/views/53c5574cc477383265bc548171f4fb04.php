<?php $__env->startSection('title'); ?> Printable Report <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('modules.platform-operations.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
    @media print {
        .vertical-menu, #page-topbar, .footer { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .card { border: 0; box-shadow: none; }
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Reporting & Export <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Printable Report <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php ($rows = collect($report['rows'] ?? [])); ?>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="card-title mb-1"><?php echo e($report['target']['code']); ?> - <?php echo e($report['target']['name']); ?></h4>
                <div class="text-muted small"><?php echo e(ucfirst($report['target_type'])); ?> report from <?php echo e($report['from']->format('d M Y H:i')); ?> to <?php echo e($report['to']->format('d M Y H:i')); ?></div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">Print</button>
        </div>
        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light">
                    <tr><th>Station</th><th>Parameter</th><th>Value</th><th>Unit</th><th>Status</th><th>Alert</th><th>Received At</th></tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <tr>
                            <td><?php echo e($row['station_code']); ?></td>
                            <td><?php echo e($row['parameter']); ?></td>
                            <td><?php echo e($row['value']); ?></td>
                            <td><?php echo e($row['unit']); ?></td>
                            <td><?php echo e($row['status']); ?></td>
                            <td><?php echo e($row['alert_level']); ?></td>
                            <td><?php echo e($row['received_at']); ?></td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr><td colspan="7" class="text-center text-muted">No data for the selected period.</td></tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-muted small mt-3">Generated <?php echo e($report['generated_at']->toISOString()); ?>. Query limited to <?php echo e($report['bounded_limit']); ?> rows.</div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/client-operations/reporting-printable.blade.php ENDPATH**/ ?>