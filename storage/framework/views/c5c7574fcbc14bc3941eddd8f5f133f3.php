<?php $__env->startSection('title'); ?> Inbox <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('modules.platform-operations.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Client Operations <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Inbox <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="emp-ui">
<?php echo $__env->make('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Client UI',
    'title' => 'Inbox',
    'subtitle' => 'Persistent operational and system notification history with source context and navigation to valid project, corridor, or station pages.',
    'steps' => [
        ['label' => 'Bell', 'icon' => 'bx bx-bell'],
        ['label' => 'Unread Count', 'icon' => 'bx bx-badge'],
        ['label' => 'Inbox', 'icon' => 'bx bx-envelope', 'active' => true],
        ['label' => 'Notification Detail', 'icon' => 'bx bx-detail'],
    ],
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0">Notification History</h4>
            <span class="text-muted small">Operational and system notifications</span>
        </div>
        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light">
                    <tr><th>Status</th><th>Category</th><th>Notification</th><th>Context</th><th>Timestamp</th></tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <tr>
                            <td><?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $notification->read_at ? 'Healthy' : 'Warning', 'label' => $notification->read_at ? 'Read' : 'Unread'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                            <td><?php echo e($notification->category); ?></td>
                            <td>
                                <a class="fw-semibold" href="<?php echo e(route('client-operations.inbox.show', $notification)); ?>"><?php echo e($notification->title); ?></a>
                                <div class="text-muted small"><?php echo e($notification->event_type); ?></div>
                            </td>
                            <td><?php echo e($notification->project?->project_code ?? '-'); ?> <?php echo e($notification->corridor?->corridor_code ?? $notification->monitoringStation?->station_code); ?></td>
                            <td><?php echo e(optional($notification->occurred_at ?? $notification->created_at)->format('d M Y H:i')); ?></td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr><td colspan="5" class="text-center text-muted">No notifications.</td></tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3"><?php echo e($notifications->links()); ?></div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/client-operations/inbox.blade.php ENDPATH**/ ?>