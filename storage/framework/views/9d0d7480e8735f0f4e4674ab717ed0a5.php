<?php $__env->startSection('title'); ?> Project Operations <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<link href="/build/libs/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<?php echo $__env->make('modules.platform-operations.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> <?php echo e($operationsTitle ?? 'Platform Operations'); ?> <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> <?php echo e($project->project_code); ?> <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $runtime = $runtime ?? [];
    $stations = collect($runtime['stations'] ?? []);
    $corridors = collect($corridors ?? []);
    $criticalStations = $stations->filter(fn ($row) => in_array($row['integrity']['overall'] ?? '', ['Critical', 'Offline'], true))->count();
    $showNationalLink = $showNationalLink ?? true;
    $firstCorridor = $corridors->first();
    $firstStation = $stations->first();
?>

<div class="emp-ui">
<?php echo $__env->make('modules.platform-operations.partials.page-hero', [
    'eyebrow' => $operationsTitle ?? 'Platform Operations',
    'title' => $project->project_code.' Operational Page',
    'subtitle' => $project->name.' active project context with spatial operational map and corridor monitoring list.',
    'steps' => [
        ['label' => 'Active Project Context', 'icon' => 'bx bx-folder-open', 'url' => '#station-context', 'active' => true],
        ['label' => 'Spatial Map', 'icon' => 'bx bx-map', 'url' => '#ops-project-map'],
        ['label' => 'Corridor List', 'icon' => 'bx bx-list-ul', 'url' => '#ops-corridor-list'],
        ['label' => 'Corridor', 'icon' => 'bx bx-git-branch', 'url' => $firstCorridor['url'] ?? '#ops-corridor-list'],
        ['label' => 'Station UI', 'icon' => 'bx bx-station', 'url' => isset($firstStation['station']['id']) ? route(($showNationalLink ? 'platform-operations' : 'client-operations').'.stations.show', $firstStation['station']['id']) : '#ops-project-map'],
    ],
    'actions' => $showNationalLink ? [
        ['label' => 'National', 'url' => route('platform-operations.index'), 'icon' => 'bx bx-map-alt'],
    ] : [],
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

	<div class="row" id="station-context">
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Project</p><h4><?php echo e($project->project_code); ?></h4><div class="ops-context-line"><?php echo e($project->name); ?></div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Stations</p><h4><?php echo e($stations->count()); ?></h4><div class="ops-context-line"><?php echo e($criticalStations); ?> need attention</div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Corridors</p><h4><?php echo e($corridors->count()); ?></h4><div class="ops-context-line">Operational relationships</div></div></div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Last Refresh</p><h4 class="font-size-18"><?php echo e(isset($runtime['generated_at']) ? \Illuminate\Support\Carbon::parse($runtime['generated_at'])->format('H:i') : '-'); ?></h4><div class="ops-context-line">Runtime read model</div></div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
	        <div class="card" id="ops-corridor-list">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Spatial Operational Map</h4>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showNationalLink): ?>
                        <a href="<?php echo e(route('platform-operations.index')); ?>" class="btn btn-sm btn-outline-secondary">National</a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div id="ops-project-map" class="ops-map"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Corridor List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle ops-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Corridor</th>
                                <th>Stations</th>
                                <th>Hazard</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $corridors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $corridor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td>
                                        <a class="fw-semibold" href="<?php echo e($corridor['url']); ?>"><?php echo e($corridor['corridor_code']); ?></a>
                                        <div class="text-muted small"><?php echo e($corridor['name']); ?></div>
                                    </td>
                                    <td><?php echo e($corridor['station_count']); ?></td>
                                    <td><?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $corridor['hazard_state']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                                    <td><?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $corridor['status']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr><td colspan="4" class="text-center text-muted">No corridors registered.</td></tr>
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
<?php echo $__env->make('modules.platform-operations.partials.map-script', ['mapId' => 'ops-project-map', 'points' => $mapStations ?? [], 'lines' => $mapLines ?? [], 'mapStyle' => 'terrain3d'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/platform-operations/project.blade.php ENDPATH**/ ?>