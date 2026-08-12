<?php $__env->startSection('title'); ?> Corridor Monitoring <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<link href="/build/libs/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<?php echo $__env->make('modules.platform-operations.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> <?php echo e($operationsTitle ?? 'Platform Operations'); ?> <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> <?php echo e($corridor->corridor_code); ?> <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $stations = collect($stations ?? []);
    $stationRows = collect($stationRows ?? []);
    $currentPreview = collect($currentPreview ?? []);
    $seriesPreview = collect($seriesPreview ?? []);
    $projectRoute = $projectRoute ?? route('platform-operations.projects.show', $project);
    $reportUrl = $reportUrl ?? null;
    $firstStationRow = $stationRows->first();
    $stationUiUrl = $firstStationRow['station_url'] ?? (isset($firstStationRow['station']['id']) ? route('platform-operations.stations.show', $firstStationRow['station']['id']) : '#ops-station-list');
?>

<div class="emp-ui">
<?php echo $__env->make('modules.platform-operations.partials.page-hero', [
    'eyebrow' => $operationsTitle ?? 'Platform Operations',
    'title' => $corridor->corridor_code.' Corridor Monitoring Page',
    'subtitle' => 'Multi-station corridor workspace with spatial view, station list, current-data preview, short time-series, and contextual functions.',
    'steps' => [
        ['label' => 'Project', 'icon' => 'bx bx-folder', 'url' => $projectRoute],
        ['label' => 'Select Corridor', 'icon' => 'bx bx-map-pin', 'url' => $projectRoute.'#ops-corridor-list'],
        ['label' => 'Corridor Monitoring', 'icon' => 'bx bx-git-branch', 'url' => '#ops-corridor-map', 'active' => true],
        ['label' => 'Station UI', 'icon' => 'bx bx-desktop', 'url' => $stationUiUrl],
    ],
    'actions' => array_values(array_filter([
        $reportUrl ? ['label' => 'Reporting & Export', 'url' => $reportUrl, 'icon' => 'bx bx-file'] : null,
        ['label' => 'Project', 'url' => $projectRoute, 'icon' => 'bx bx-folder-open'],
    ])),
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="row">
    <div class="col-md-4">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Project</p><h4><?php echo e($project->project_code); ?></h4><div class="ops-context-line"><?php echo e($project->name); ?></div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Corridor</p><h4><?php echo e($corridor->corridor_code); ?></h4><div class="ops-context-line"><?php echo e($corridor->name); ?></div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Stations</p><h4><?php echo e($stations->count()); ?></h4><div class="ops-context-line">Selectable via map or list</div></div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
	        <div class="card" id="ops-station-list">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="card-title mb-0">Corridor Spatial Map</h4>
                    <div class="d-flex gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($reportUrl): ?>
                            <a href="<?php echo e($reportUrl); ?>" class="btn btn-sm btn-outline-primary">Report</a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <a href="<?php echo e($projectRoute); ?>" class="btn btn-sm btn-outline-secondary">Project</a>
                    </div>
                </div>
                <div id="ops-corridor-map" class="ops-map"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Station List</h4>
                <div class="ops-station-list">
                    <div class="list-group">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $stationRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <a href="<?php echo e($row['station_url'] ?? route('platform-operations.stations.show', $row['station']['id'])); ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo e($row['station']['station_code']); ?></strong>
                                    <?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $row['hazard']['state'] ?? 'NORMAL'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                </div>
                                <div class="text-muted small"><?php echo e($row['station']['name'] ?? '-'); ?></div>
                                <div class="small mt-1">Integrity: <?php echo e($row['integrity']['overall'] ?? '-'); ?></div>
                            </a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <div class="text-center text-muted py-3">No stations in this corridor.</div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Current Data Preview</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle ops-table mb-0">
                        <thead class="table-light"><tr><th>Station</th><th>Parameter</th><th>Value</th><th>Freshness</th><th>Time</th></tr></thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $currentPreview; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($reading['station_code']); ?></td>
                                    <td><?php echo e($reading['parameter']); ?></td>
                                    <td><?php echo e($reading['value']); ?> <?php echo e($reading['unit']); ?></td>
                                    <td><?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $reading['data_freshness'], 'label' => ucfirst($reading['data_freshness'])], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></td>
                                    <td><?php echo e($reading['timestamp'] ? \Illuminate\Support\Carbon::parse($reading['timestamp'])->format('d M H:i') : '-'); ?></td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr><td colspan="5" class="text-center text-muted">No current readings.</td></tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Short Time-Series Preview</h4>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $seriesPreview; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $series): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $values = collect($series['points'])->pluck('value')->filter(fn ($value) => is_numeric($value))->map(fn ($value) => (float) $value);
                        $max = max($values->max() ?: 1, 1);
                    ?>
                    <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                        <div>
                            <strong><?php echo e($series['station_code']); ?></strong>
                            <div class="text-muted small"><?php echo e($series['sensor_code']); ?> / <?php echo e($series['parameter'] ?? 'Parameter'); ?></div>
                        </div>
                        <div class="ops-spark">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_2 = true; $__currentLoopData = $values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <span class="ops-spark-bar" style="height: <?php echo e(max(8, (int) round(($value / $max) * 44))); ?>px"></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <span class="text-muted small">No points</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="text-center text-muted py-3">No time-series preview available.</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="/build/libs/leaflet/leaflet.js"></script>
<?php echo $__env->make('modules.platform-operations.partials.map-script', ['mapId' => 'ops-corridor-map', 'points' => $mapStations ?? [], 'lines' => $mapLines ?? [], 'mapStyle' => 'terrain3d'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/platform-operations/corridor.blade.php ENDPATH**/ ?>