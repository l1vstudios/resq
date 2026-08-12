<?php $__env->startSection('title'); ?>
<?php echo app('translator')->get('translation.Login'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<style>
    .auth-body-bg {
        min-height: 100vh;
        background: linear-gradient(135deg, #f5f7fb 0%, #eef1f8 100%);
    }

    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .auth-card {
        width: 100%;
        max-width: 440px;
        border: none;
        border-radius: 1rem;
        box-shadow: 0 1.5rem 4rem rgba(18, 38, 63, 0.12);
        overflow: hidden;
    }

    .auth-card .card-body {
        padding: 2.5rem 2.5rem 2rem;
    }

    .auth-card .form-control,
    .auth-card .btn {
        border-radius: 0.6rem;
    }

    .auth-card .form-control {
        padding: 0.65rem 0.9rem;
    }

    .auth-card .btn-primary {
        padding: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('body'); ?>

<body class="auth-body-bg">
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="auth-wrapper">
    <div class="card auth-card">
        <div class="card-body">

            <!-- LOGO: mb-0 untuk menghilangkan jarak bawah -->
            <div class="text-center mb-0">
                <a href="index" class="d-inline-block auth-logo">
                    <img src="<?php echo e(URL::asset('build/images/logos44.png')); ?>" alt="Resq" height="200">
                </a>
            </div>

            <!-- TULISAN: Menggunakan style margin-top negatif untuk ditarik ke atas -->
            <div class="text-center mb-4" style="margin-top: -60px; position: relative; z-index: 10;">
                
                <p class="text-muted mb-0">Sign in to continue to Sentinal Platform</p>
            </div>

            <form class="form-horizontal" method="POST" action="<?php echo e(route('login')); ?>">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Email <span class="text-danger">*</span></label>
                    <input name="email" type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="" id="username" placeholder="Enter Email" autocomplete="email" autofocus>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="invalid-feedback" role="alert">
                        <strong><?php echo e($message); ?></strong>
                    </span>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="mb-3">
                    <div class="float-end">
                        
                    </div>
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group auth-pass-inputgroup <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <input type="password" name="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="userpassword" value="" placeholder="Enter password" aria-label="Password" aria-describedby="password-addon">
                        <button class="btn btn-light" type="button" id="password-addon"><i class="mdi mdi-eye-outline"></i></button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <span class="invalid-feedback" role="alert">
                            <strong><?php echo e($message); ?></strong>
                        </span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                
                    
                

                <div class="mt-4 d-grid">
                    <button class="btn btn-primary waves-effect waves-light" type="submit">Log in</button>
                </div>
            </form>

            <div class="mt-4 text-center">
                <p class="text-muted mb-0">© <script>
                        document.write(new Date().getFullYear())
                    </script> sentinalplatform</p>
            </div>

        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.master-without-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/auth/login.blade.php ENDPATH**/ ?>