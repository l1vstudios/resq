<?php $__env->startSection('title'); ?> Reporting & Export <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('modules.platform-operations.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Client Operations <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Reporting & Export <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $projects = collect($projects ?? []);
    $targetOptions = $targetOptions ?? ['corridors' => [], 'stations' => []];
?>

<div class="emp-ui">
<?php echo $__env->make('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Client UI',
    'title' => 'Reporting & Export',
    'subtitle' => 'Reusable report flow: select target, select data or parameter, select period, then generate printable, CSV, or Excel-compatible output.',
    'steps' => [
        ['label' => 'Select Target', 'icon' => 'bx bx-target-lock', 'active' => true],
        ['label' => 'Select Data', 'icon' => 'bx bx-data'],
        ['label' => 'Select Period', 'icon' => 'bx bx-calendar'],
        ['label' => 'Generate Output', 'icon' => 'bx bx-file'],
    ],
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">Generate Report</h4>
        <form method="GET" action="<?php echo e(route('client-operations.reporting.generate')); ?>">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Select Target</label>
                    <select name="target_type" class="form-select" required>
                        <option value="corridor" <?php if(($preselectedTargetType ?? '') === 'corridor'): echo 'selected'; endif; ?>>Corridor Monitoring</option>
                        <option value="station" <?php if(($preselectedTargetType ?? '') === 'station'): echo 'selected'; endif; ?>>Monitoring Station</option>
                    </select>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Target</label>
                    <select name="target_id" class="form-select" required>
                        <optgroup label="Corridor Monitoring">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $targetOptions['corridors']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $corridor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($corridor['id']); ?>" <?php if(($preselectedTargetType ?? '') === 'corridor' && (string) ($preselectedTargetId ?? '') === (string) $corridor['id']): echo 'selected'; endif; ?>>Corridor: <?php echo e($corridor['code']); ?> - <?php echo e($corridor['name']); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </optgroup>
                        <optgroup label="Monitoring Station">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $targetOptions['stations']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($station['id']); ?>" <?php if(($preselectedTargetType ?? '') === 'station' && (string) ($preselectedTargetId ?? '') === (string) $station['id']): echo 'selected'; endif; ?>>Station: <?php echo e($station['code']); ?> - <?php echo e($station['name']); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Data / Parameter</label>
                    <input name="parameter" type="text" class="form-control" placeholder="All parameters">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">From</label>
                    <input name="from" type="datetime-local" class="form-control" value="<?php echo e(now()->subDay()->format('Y-m-d\\TH:i')); ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">To</label>
                    <input name="to" type="datetime-local" class="form-control" value="<?php echo e(now()->format('Y-m-d\\TH:i')); ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Output</label>
                    <select name="output" class="form-select" required>
                        <option value="print">Printable Report</option>
                        <option value="csv">CSV</option>
                        <option value="excel">Excel-compatible Export</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Generate Output</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/client-operations/reporting.blade.php ENDPATH**/ ?>