<?php $__env->startSection('title'); ?> <?php echo app('translator')->get('translation.E_Charts'); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?> Charts <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?> ECharts <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Line Chart</h4>
                    <div id="line-chart" data-colors='["--bs-success"]' class="e-charts"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Mix Line-Bar</h4>
                    <div id="mix-line-bar" data-colors='["--bs-success","--bs-primary", "--bs-danger"]' class="e-charts"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Doughnut Chart</h4>
                    <div id="doughnut-chart" data-colors='["--bs-primary","--bs-warning", "--bs-danger","--bs-info", "--bs-success"]' class="e-charts"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Pie Chart</h4>
                    <div id="pie-chart" data-colors='["--bs-primary","--bs-warning", "--bs-danger","--bs-info", "--bs-success"]' class="e-charts"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Scatter Chart</h4>
                    <div id="scatter-chart" data-colors='["--bs-success"]' class="e-charts"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Bubble Chart</h4>
                    <div id="bubble-chart" data-colors='["--bs-primary-rgb", "--bs-success-rgb"]' class="e-charts"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Candlestick Chart</h4>
                    <div id="candlestick-chart" data-colors='["--bs-primary","--bs-success"]' class="e-charts"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Gauge Chart</h4>
                    <div id="gauge-chart" data-colors='["--bs-success","--bs-primary","--bs-danger"]' class="e-charts"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <!-- echarts js -->
    <script src="<?php echo e(URL::asset('build/libs/echarts/echarts.min.js')); ?>"></script>
    <!-- echarts init -->
    <script src="<?php echo e(URL::asset('build/js/pages/echarts.init.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/charts-echart.blade.php ENDPATH**/ ?>