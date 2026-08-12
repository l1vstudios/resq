<?php $__env->startSection('title'); ?> Platform Operations <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<link href="/build/libs/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<?php echo $__env->make('modules.platform-operations.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Sentinel Console <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Platform Operations <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $projectSummaries = collect($projectSummaries ?? []);
    $activeProjects = $projectSummaries->whereNotIn('state', ['OFFLINE'])->count();
    $totalStations = $projectSummaries->sum('station_count');
    $flowLinks = $flowLinks ?? [];
?>

<div class="emp-ui">
<?php echo $__env->make('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Sentinel Console',
    'title' => 'Platform Operations',
    'subtitle' => 'National landing page for project distribution, active project context, operational state, integrity, and administrative monitoring.',
    'steps' => [
        ['label' => 'National', 'icon' => 'bx bx-map-alt', 'url' => $flowLinks['national'] ?? route('platform-operations.index'), 'active' => true],
        ['label' => 'Select Project', 'icon' => 'bx bx-folder-open', 'url' => $flowLinks['select_project'] ?? '#ops-project-list'],
        ['label' => 'Project Context', 'icon' => 'bx bx-radar', 'url' => $flowLinks['project_context'] ?? '#ops-project-list'],
        ['label' => 'Corridor', 'icon' => 'bx bx-git-branch', 'url' => $flowLinks['corridor'] ?? '#ops-project-list'],
        ['label' => 'Station UI', 'icon' => 'bx bx-station', 'url' => $flowLinks['station_ui'] ?? '#ops-project-list'],
    ],
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="row">
    <div class="col-md-4">
        <div class="card ops-kpi">
            <div class="card-body">
                <p class="text-muted">Projects</p>
                <h4><?php echo e($projectSummaries->count()); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card ops-kpi">
            <div class="card-body">
                <p class="text-muted">Monitoring Stations</p>
                <h4><?php echo e($totalStations); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card ops-kpi">
            <div class="card-body">
                <p class="text-muted">Active Project Contexts</p>
                <h4><?php echo e($activeProjects); ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card" id="ops-project-list">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="card-title mb-0">Project Distribution</h4>
                    <span class="badge bg-info-subtle text-info">3D Terrain / Contour GIS</span>
                </div>
                <div id="ops-national-map" class="ops-map ops-map-lg"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Project List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle ops-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Project</th>
                                <th>Area</th>
                                <th>Stations</th>
                                <th>State</th>
                                <th>Last Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $projectSummaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo e(route('platform-operations.projects.show', $project['id'])); ?>" class="fw-semibold">
                                            <?php echo e($project['project_code']); ?>

                                        </a>
                                        <div class="text-muted small"><?php echo e($project['name']); ?></div>
                                    </td>
                                    <td><?php echo e($project['area']); ?></td>
                                    <td><?php echo e($project['station_count']); ?></td>
                                    <td><?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $project['state']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                                    <td><?php echo e($project['last_update'] ? \Illuminate\Support\Carbon::parse($project['last_update'])->diffForHumans() : '-'); ?></td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No accessible projects.</td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="/build/libs/leaflet/leaflet.js"></script>
<?php echo $__env->make('modules.platform-operations.partials.map-script', ['mapId' => 'ops-national-map', 'points' => $mapProjects ?? [], 'lines' => $mapLines ?? [], 'mapStyle' => 'terrain3d'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/platform-operations/national.blade.php ENDPATH**/ ?>