<?php $__env->startSection('title'); ?> <?php echo app('translator')->get('translation.Sparkline_Charts'); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?> Charts <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?> Sparkline Charts <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-xl-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Pie Chart</h4>
                    <div id="sparkline1" data-colors='["--bs-success","--bs-primary", "--bs-light"]' class="text-center"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Bar Chart</h4>
                    <div id="sparkline2" data-colors='["--bs-success"]' class="text-center"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card">
                <div class="card-body analytics-info">
                    <h4 class="card-title">Line Chart</h4>
                    <div id="sparkline4" data-colors='["--bs-primary"]'></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Composite Bar Chart</h4>
                    <div id="sparkline3" data-colors='["--bs-primary","--bs-success"]' class="text-center"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Line Chart</h4>
                    <div id="sparkline5" data-colors='["--bs-primary","--bs-success"]' class="text-center"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card bg-primary">
                <div class="card-body">
                    <h4 class="card-title text-white">Discrete Chart</h4>
                    <div id="sparkline6" data-colors='["--bs-white"]' class="text-center"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Bullet Chart</h4>
                    <div id="sparkline7" data-colors='["--bs-primary", "--bs-danger"]'></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Box Plot Chart</h4>
                    <div id="sparkline8" data-colors='["--bs-success"]' class="text-center"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Tristate Chart</h4>
                    <div id="sparkline9" data-colors='["--bs-primary","--bs-success","--bs-danger"]' class="text-center"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <script src="<?php echo e(URL::asset('build/libs/jquery-sparkline/jquery.sparkline.min.js')); ?>"></script>

    <script src="<?php echo e(URL::asset('build/js/pages/sparklines.init.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/charts-sparkline.blade.php ENDPATH**/ ?>