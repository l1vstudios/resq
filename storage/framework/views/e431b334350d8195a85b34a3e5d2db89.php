<?php $__env->startSection('title'); ?> Function Configuration <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<?php echo $__env->make('modules.platform-operations.partials.styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Client Operations <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> <?php echo e($station->station_code); ?> Function Configuration <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="emp-ui">
<?php echo $__env->make('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Client UI',
    'title' => $station->station_code.' Function Configuration',
    'subtitle' => 'Configure only Client-authorized function settings. Analytical internals and formulas remain backend/system-owned.',
    'steps' => [
        ['label' => 'Select Monitoring Station', 'icon' => 'bx bx-station'],
        ['label' => 'Capability', 'icon' => 'bx bx-check-shield'],
        ['label' => 'Function Configuration', 'icon' => 'bx bx-cog', 'active' => true],
    ],
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php echo $__env->make('modules.client-operations.partials.function-configuration-panel', [
    'station' => $station,
    'context' => $context,
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/client-operations/function-configuration.blade.php ENDPATH**/ ?>