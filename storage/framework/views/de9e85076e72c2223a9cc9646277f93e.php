<!-- Bootstrap Css -->
<link href="/build/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="/build/css/icons.min.css" rel="stylesheet" type="text/css" />
<!-- App Css-->
<link href="/build/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(app()->environment('local')): ?>
    <?php
        $inlineCss = collect([
            public_path('build/css/bootstrap.min.css'),
            public_path('build/css/app.min.css'),
        ])->filter(fn ($path) => is_file($path))
            ->map(fn ($path) => file_get_contents($path))
            ->implode("\n");

        $inlineCss = str_replace([
            '@charset "UTF-8";',
            '@import"https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap";',
            '</style',
        ], [
            '',
            '',
            '<\/style',
        ], $inlineCss);
    ?>
    <style id="local-css-fallback"><?php echo $inlineCss; ?></style>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php echo $__env->yieldContent('css'); ?>

<!-- App js -->
<script src="/build/js/plugin.js"></script>
<?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/layouts/head-css.blade.php ENDPATH**/ ?>