<?php $__env->startSection('title'); ?> Administrative Monitoring <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('modules.platform-operations.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Platform Operations <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Administrative Monitoring <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $stations = collect($stations ?? []);
    $filters = ['Active', 'Inactive', 'Expiring Soon', 'Attention'];
?>

<div class="emp-ui">
<?php echo $__env->make('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Platform Operations',
    'title' => 'Administrative Monitoring',
    'subtitle' => 'Project station list for registration status, service status, service period, entitlement, and administrative attention.',
    'steps' => [
        ['label' => 'Administrative Monitoring', 'icon' => 'bx bx-id-card', 'active' => true],
        ['label' => 'Project Station List', 'icon' => 'bx bx-table'],
        ['label' => 'Select Station', 'icon' => 'bx bx-target-lock'],
        ['label' => 'Station UI', 'icon' => 'bx bx-desktop'],
    ],
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="card-title mb-0">All Station Administrative Monitoring</h4>
            <div class="ops-filter-row">
                <a href="<?php echo e(route('platform-operations.administrative.index')); ?>" class="btn btn-sm <?php echo e(empty($activeFilter) ? 'btn-primary' : 'btn-outline-primary'); ?>">All</a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $filters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('platform-operations.administrative.index', ['status' => $filter])); ?>" class="btn btn-sm <?php echo e($activeFilter === $filter ? 'btn-primary' : 'btn-outline-primary'); ?>"><?php echo e($filter); ?></a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Station</th>
                        <th>Project</th>
                        <th>Registration</th>
                        <th>Service</th>
                        <th>Period</th>
                        <th>Entitlement</th>
                        <th>Attention</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $stations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php ($admin = $row['administrative'] ?? []); ?>
                        <tr>
                            <td>
                                <a href="<?php echo e(route('platform-operations.stations.show', [$row['station']['id'], 'tab' => 'administrative'])); ?>" class="fw-semibold">
                                    <?php echo e($row['station']['station_code']); ?>

                                </a>
                                <div class="text-muted small"><?php echo e($row['station']['name'] ?? '-'); ?></div>
                            </td>
                            <td><?php echo e($row['station']['project_code'] ?? '-'); ?></td>
                            <td><?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $admin['registration_status'] ?? 'unknown'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                            <td><?php echo e($admin['service_status'] ?? '-'); ?></td>
                            <td><?php echo e($admin['service_period']['start'] ?? '-'); ?> - <?php echo e($admin['service_period']['end'] ?? '-'); ?></td>
                            <td><?php echo e($admin['entitlement'] ?? '-'); ?></td>
                            <td><?php echo e($admin['administrative_attention'] ?? '-'); ?></td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr><td colspan="7" class="text-center text-muted">No administrative monitoring rows.</td></tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/platform-operations/administrative.blade.php ENDPATH**/ ?>