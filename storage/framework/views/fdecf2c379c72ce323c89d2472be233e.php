

<?php $__env->startSection('title'); ?>
    <?php echo app('translator')->get('translation.yajra-datatable'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    <!--datatable css-->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Backend
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Yajra Datatable
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <table class="table table-bordered yajra-datatable">
        <thead>
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Rating</th>
                <th>Balance</th>
                <th>Joining Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap4.min.js"></script>
    <script type="text/javascript">
        $(function() {

            var table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "<?php echo e(route('customers.list')); ?>",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'No',
                        searchable: false
                    },
                    {
                        data: 'username',
                        name: 'username',
                    },
                    {
                        data: 'email',
                        name: 'email',
                    },
                    {
                        data: 'phone',
                        name: 'phone',
                    },
                    {
                        data: 'address',
                        name: 'address',
                    },
                    {
                        data: 'rating',
                        name: 'rating',
                    },
                    {
                        data: 'balance',
                        name: 'balance'
                    },
                    {
                        data: 'joining_date',
                        name: 'joining_date'
                    },
                    {
                        data: 'action',
                        name: 'action',
                    },
                ]
            });

        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/yajra-datatable.blade.php ENDPATH**/ ?>