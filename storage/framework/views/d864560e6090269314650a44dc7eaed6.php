<?php $__env->startSection('title'); ?> Proyek <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Konfigurasi Proyek <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Proyek <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $project = config('resq_dummy.project');
    $clusters = config('resq_dummy.clusters');
    $dangerCount = collect($clusters)->where('status', 'Danger')->count();
    $normalCount = collect($clusters)->where('status', 'Normal')->count();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Tambah Proyek</h4>
                <form>
                    <div class="mb-3">
                        <label class="form-label">ID Proyek</label>
                        <input type="text" class="form-control" value="<?php echo e($project['id']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Proyek</label>
                        <input type="text" class="form-control" value="<?php echo e($project['name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pemilik Proyek</label>
                        <input type="text" class="form-control" value="<?php echo e($project['owner']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" value="<?php echo e($project['date']); ?>">
                    </div>
                    <button type="button" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Simpan Proyek
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Daftar Proyek</h4>
                    <button type="button" class="btn btn-light btn-sm">
                        <i class="bx bx-filter-alt me-1"></i> Saring
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Proyek</th>
                                <th>Pemilik</th>
                                <th>Tanggal</th>
                                <th>Klaster</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo e($project['id']); ?></td>
                                <td><?php echo e($project['name']); ?></td>
                                <td><?php echo e($project['owner']); ?></td>
                                <td><?php echo e($project['date']); ?></td>
                                <td>
                                    <span class="badge bg-danger-subtle text-danger"><?php echo e($dangerCount); ?> danger</span>
                                    <span class="badge bg-success-subtle text-success"><?php echo e($normalCount); ?> normal</span>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('clusters.index')); ?>" class="btn btn-primary btn-sm">
                                        <i class="bx bx-map-alt me-1"></i> Clusters
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/projects/index.blade.php ENDPATH**/ ?>