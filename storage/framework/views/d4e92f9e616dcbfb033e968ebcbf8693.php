<?php $__env->startSection('title'); ?> Client Station Operations <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('modules.platform-operations.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> <?php echo e($operationsTitle ?? 'Client Operations'); ?> <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> <?php echo e($project->project_code); ?> Stations <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $stations = collect($stations ?? []);
    $perspective = $perspective ?? 'integrity';
    $isAdministrative = $perspective === 'administrative';
    $filters = $isAdministrative
        ? ['Active', 'Inactive', 'Expiring Soon', 'Attention']
        : ['Healthy', 'Warning', 'Critical', 'Offline'];
    $heading = $isAdministrative ? 'Project Station Administrative Monitoring' : 'Project Station Integrity';
?>

<div class="emp-ui">
<?php echo $__env->make('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Client UI',
    'title' => $heading,
    'subtitle' => 'Active project station list for '.$project->project_code.' with Client-authorized operational context only.',
    'steps' => [
        ['label' => 'Active Project', 'icon' => 'bx bx-folder-open'],
        ['label' => 'Project Station List', 'icon' => 'bx bx-table', 'active' => true],
        ['label' => 'Select Station', 'icon' => 'bx bx-search'],
        ['label' => 'Shared Station UI', 'icon' => 'bx bx-desktop'],
    ],
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="row">
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Active Project</p><h4><?php echo e($project->project_code); ?></h4><div class="ops-context-line"><?php echo e($project->name); ?></div></div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Perspective</p><h4><?php echo e($isAdministrative ? 'Administrative' : 'Integrity'); ?></h4><div class="ops-context-line">Project-scoped stations only</div></div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Rows</p><h4><?php echo e($stations->count()); ?></h4><div class="ops-context-line">Filtered operational read model</div></div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="card-title mb-0"><?php echo e($heading); ?></h4>
            <div class="ops-filter-row">
                <a href="<?php echo e(route('client-operations.projects.stations', [$project, 'tab' => $perspective])); ?>" class="btn btn-sm <?php echo e(empty($activeFilter) ? 'btn-primary' : 'btn-outline-primary'); ?>">All</a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $filters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="<?php echo e(route('client-operations.projects.stations', [$project, 'tab' => $perspective, 'status' => $filter])); ?>" class="btn btn-sm <?php echo e($activeFilter === $filter ? 'btn-primary' : 'btn-outline-primary'); ?>"><?php echo e($filter); ?></a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isAdministrative): ?>
                        <tr>
                            <th>Station</th>
                            <th>Registration</th>
                            <th>Service</th>
                            <th>Period</th>
                            <th>Entitlement</th>
                            <th>Attention</th>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <th>Station</th>
                            <th>Overall</th>
                            <th>Connectivity</th>
                            <th>Telemetry</th>
                            <th>Last Seen</th>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $stations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php ($admin = $row['administrative'] ?? []); ?>
                        <?php ($integrity = $row['integrity'] ?? []); ?>
                        <tr>
                            <td>
                                <a href="<?php echo e($row['station_url']); ?>" class="fw-semibold"><?php echo e($row['station']['station_code']); ?></a>
                                <div class="text-muted small"><?php echo e($row['station']['name'] ?? '-'); ?></div>
                            </td>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isAdministrative): ?>
                                <td><?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $admin['registration_status'] ?? 'unknown'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                                <td><?php echo e($admin['service_status'] ?? '-'); ?></td>
                                <td><?php echo e($admin['service_period']['start'] ?? '-'); ?> - <?php echo e($admin['service_period']['end'] ?? '-'); ?></td>
                                <td><?php echo e($admin['entitlement'] ?? '-'); ?></td>
                                <td><?php echo e($admin['administrative_attention'] ?? '-'); ?></td>
                            <?php else: ?>
                                <td><?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $integrity['overall'] ?? 'Warning'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                                <td><?php echo e($integrity['components']['connectivity']['status'] ?? '-'); ?></td>
                                <td><?php echo e($integrity['components']['telemetry_data_health']['status'] ?? '-'); ?></td>
                                <td><?php echo e(! empty($integrity['last_seen_at']) ? \Illuminate\Support\Carbon::parse($integrity['last_seen_at'])->diffForHumans() : '-'); ?></td>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr><td colspan="<?php echo e($isAdministrative ? 6 : 5); ?>" class="text-center text-muted">No stations match this view.</td></tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/client-operations/station-list.blade.php ENDPATH**/ ?>