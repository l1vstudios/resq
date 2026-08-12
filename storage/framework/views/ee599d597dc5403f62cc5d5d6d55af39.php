<?php $__env->startSection('title'); ?> Notification Detail <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('modules.platform-operations.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Inbox <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Notification Detail <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="card-title mb-1"><?php echo e($notification->title); ?></h4>
                <div class="text-muted small"><?php echo e($notification->category); ?> / <?php echo e($notification->event_type); ?></div>
            </div>
            <?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $notification->read_at ? 'Healthy' : 'Warning', 'label' => $notification->read_at ? 'Read' : 'Unread'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <p><?php echo e($notification->body); ?></p>
        <div class="table-responsive mb-3">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <tbody>
                    <tr><th>Project</th><td><?php echo e($notification->project?->project_code ?? '-'); ?></td></tr>
                    <tr><th>Corridor</th><td><?php echo e($notification->corridor?->corridor_code ?? '-'); ?></td></tr>
                    <tr><th>Monitoring Station</th><td><?php echo e($notification->monitoringStation?->station_code ?? '-'); ?></td></tr>
                    <tr><th>Warning Station</th><td><?php echo e($notification->warningStation?->station_code ?? '-'); ?></td></tr>
                    <tr><th>Timestamp</th><td><?php echo e(optional($notification->occurred_at ?? $notification->created_at)->toISOString()); ?></td></tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('client-operations.inbox.index')); ?>" class="btn btn-outline-secondary">History</a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($contextUrl): ?>
                <a href="<?php echo e($contextUrl); ?>" class="btn btn-primary">Open Context</a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/client-operations/inbox-detail.blade.php ENDPATH**/ ?>