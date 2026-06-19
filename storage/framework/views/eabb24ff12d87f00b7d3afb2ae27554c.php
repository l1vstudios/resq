<?php $__env->startSection('title'); ?> Klaster <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Konfigurasi Proyek <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Klaster <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $project = config('resq_dummy.project');
    $clusters = config('resq_dummy.clusters');
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Tambah Klaster</h4>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Proyek</label>
                        <select class="form-select">
                            <option><?php echo e($project['id']); ?> - <?php echo e($project['name']); ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Project ID</label>
                        <input type="text" class="form-control" value="<?php echo e($project['id']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Klaster</label>
                        <input type="text" class="form-control" placeholder="Klaster 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ancaman Bencana</label>
                        <select class="form-select">
                            <option>Banjir</option>
                            <option>Longsor</option>
                            <option>Tsunami</option>
                            <option>Gempa Bumi</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Provinsi</label>
                            <input type="text" class="form-control" placeholder="Provinsi">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kab/Kota</label>
                            <input type="text" class="form-control" placeholder="Kab/Kota">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Penerima Manfaat</label>
                        <input type="number" class="form-control" placeholder="0">
                    </div>
                    <button type="button" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Simpan Klaster
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Daftar Klaster</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID Klaster</th>
                                <th>Project ID</th>
                                <th>Nama Klaster</th>
                                <th>Ancaman</th>
                                <th>Lokasi</th>
                                <th>Stasiun</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $clusters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cluster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($cluster['id']); ?></td>
                                    <td><?php echo e($cluster['project_id']); ?></td>
                                    <td><?php echo e($cluster['name']); ?></td>
                                    <td><span class="badge bg-light text-dark"><?php echo e($cluster['hazard']); ?></span></td>
                                    <td><?php echo e($cluster['province']); ?> / <?php echo e($cluster['city']); ?></td>
                                    <td>
                                        <div><?php echo e($cluster['monitoring_station_id']); ?></div>
                                        <div><?php echo e($cluster['warning_station_id']); ?></div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge <?php echo e($cluster['status'] === 'Danger' ? 'bg-danger' : 'bg-success'); ?> me-2">
                                            <?php echo e($cluster['status']); ?>

                                        </span>
                                        <a href="<?php echo e(route('monitoring-stations.index')); ?>" class="btn btn-info btn-sm">
                                            <i class="bx bx-map-pin me-1"></i> Monitoring
                                        </a>
                                        <a href="<?php echo e(route('warning-stations.index')); ?>" class="btn btn-warning btn-sm">
                                            <i class="bx bx-bell me-1"></i> Warning
                                        </a>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/clusters/index.blade.php ENDPATH**/ ?>