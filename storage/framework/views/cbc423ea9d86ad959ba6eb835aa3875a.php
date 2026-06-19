<?php $__env->startSection('title'); ?> Admin Management <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Settings <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Admin Management <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Add Admin</h4>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" placeholder="Admin name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" placeholder="admin@resq.id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select">
                            <option>Super Admin</option>
                            <option>Project Admin</option>
                            <option>Operator</option>
                            <option>Viewer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assigned Project</label>
                        <select class="form-select">
                            <option>All Projects</option>
                            <option>Early Warning System</option>
                            <option>Flood Monitoring</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary">
                        <i class="bx bx-user-plus me-1"></i> Save Admin
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Admin List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Project</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Operations Admin</td>
                                <td>ops@resq.id</td>
                                <td>Project Admin</td>
                                <td>Early Warning System</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-light btn-sm">
                                        <i class="bx bx-edit me-1"></i> Edit
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Field Operator</td>
                                <td>field@resq.id</td>
                                <td>Operator</td>
                                <td>Cluster 1</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-light btn-sm">
                                        <i class="bx bx-edit me-1"></i> Edit
                                    </button>
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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/admins/index.blade.php ENDPATH**/ ?>