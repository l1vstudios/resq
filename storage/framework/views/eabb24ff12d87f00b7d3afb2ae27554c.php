<?php $__env->startSection('title'); ?> Geospatial Workspace <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Registered <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Geospatial Workspace <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $clusters = collect($clusters ?? config('resq_dummy.clusters'));
    $totalWorkspaces = $clusters->count();
    $totalProvinces = $clusters->pluck('province')->filter()->unique()->count();
    $totalBeneficiaries = $clusters->sum(fn ($cluster) => (int) ($cluster['beneficiaries'] ?? 0));
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Workspace</p>
                <h4 class="mb-0"><?php echo e($totalWorkspaces); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Provinsi Terdaftar</p>
                <h4 class="mb-0"><?php echo e($totalProvinces); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Penerima Manfaat</p>
                <h4 class="mb-0"><?php echo e(number_format($totalBeneficiaries)); ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Geospatial Workspace Registry</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Workspace ID</th>
                                <th>Project ID</th>
                                <th>Workspace Name</th>
                                <th>Hazard</th>
                                <th>Location</th>
                                <th>Beneficiaries</th>
                                <th>Monitoring</th>
                                <th>Warning</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $clusters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cluster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($cluster['id'] ?? '-'); ?></td>
                                    <td><?php echo e($cluster['project_id'] ?? '-'); ?></td>
                                    <td><?php echo e($cluster['name'] ?? '-'); ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo e($cluster['hazard'] ?? '-'); ?></span></td>
                                    <td><?php echo e($cluster['province'] ?? '-'); ?> / <?php echo e($cluster['city'] ?? '-'); ?></td>
                                    <td><?php echo e(number_format((int) ($cluster['beneficiaries'] ?? 0))); ?></td>
                                    <td><?php echo e($cluster['monitoring_station_id'] ?? '-'); ?></td>
                                    <td><?php echo e($cluster['warning_station_id'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo e(in_array($cluster['status'] ?? '', ['Danger', 'Bahaya', 'Awas']) ? 'bg-danger' : (($cluster['status'] ?? '') === 'Waspada' ? 'bg-warning' : 'bg-success')); ?>">
                                            <?php echo e($cluster['status'] ?? '-'); ?>

                                        </span>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Belum ada geospatial workspace.</td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/clusters/index.blade.php ENDPATH**/ ?>