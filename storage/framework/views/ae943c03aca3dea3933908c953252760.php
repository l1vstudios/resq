<style>
    #vertical-menu-btn.sidebar-toggle-btn {
        align-items: center;
        display: inline-flex !important;
        justify-content: center;
        min-width: 48px;
        opacity: 1 !important;
        visibility: visible !important;
    }

    .topbar-logo-toggle {
        align-items: center;
        display: flex;
        gap: 0;
        min-width: 0;
    }

    .topbar-logo-toggle .navbar-brand-box {
        align-items: center;
        display: flex;
        height: 70px;
        justify-content: center;
        margin-left: 0;
        overflow: visible;
        padding-left: 0 !important;
        padding-right: 0 !important;
        width: 260px;
    }

    .topbar-logo-toggle .logo {
        align-items: center;
        line-height: 1;
    }

    .topbar-logo-toggle .logo-lg img {
        height: 180px;
        margin-top: 20px !important;
        max-width: 300px;
        object-fit: contain;
        width: auto;
    }

    .topbar-logo-mobile {
        align-items: center;
        display: inline-flex;
        height: 42px;
        justify-content: center;
        width: 42px;
    }

    .topbar-logo-mobile img {
        max-height: 42px;
        max-width: 42px;
        object-fit: contain;
        width: auto;
    }

    #vertical-menu-btn.sidebar-toggle-btn i {
        font-size: 18px;
        line-height: 1;
    }

    .topbar-logo-toggle #vertical-menu-btn.sidebar-toggle-btn {
        margin-left: 0;
    }

    .vertical-collpsed .topbar-logo-toggle .navbar-brand-box {
        justify-content: center;
        margin-left: 0;
        width: 70px !important;
    }

    .vertical-collpsed .topbar-logo-mobile {
        margin: 0 auto;
    }

    .vertical-collpsed .navbar-brand-box .logo-sm > img,
    .navbar-brand-box .logo-sm > img {
        max-height: 24px;
        width: auto;
    }

    .vertical-collpsed .vertical-menu #sidebar-menu > ul > li > a > span,
    .vertical-collpsed .vertical-menu #sidebar-menu .menu-title {
        display: none !important;
    }

    @media (min-width: 992px) {
        #vertical-menu-btn.sidebar-toggle-btn {
            display: inline-flex !important;
        }
    }
</style>

<header id="page-topbar">
    <div class="navbar-header">
        <div class="topbar-logo-toggle">
            <div class="navbar-brand-box">
                <a href="<?php echo e(route('dashboard')); ?>" class="logo logo-dark">
                    <span class="logo-sm">
                        <span class="topbar-logo-mobile">
                            <img src="<?php echo e(URL::asset('build/images/logomobile.png')); ?>" alt="RESQ">
                        </span>
                    </span>
                    <span class="logo-lg">
                        <img src="<?php echo e(URL::asset('build/images/logos44.png')); ?>" alt="RESQ">
                    </span>
                </a>
                <a href="<?php echo e(route('dashboard')); ?>" class="logo logo-light">
                    <span class="logo-sm">
                        <span class="topbar-logo-mobile">
                            <img src="<?php echo e(URL::asset('build/images/logomobile.png')); ?>" alt="RESQ">
                        </span>
                    </span>
                    <span class="logo-lg">
                        <img src="<?php echo e(URL::asset('build/images/logos44.png')); ?>" alt="RESQ">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect sidebar-toggle-btn" id="vertical-menu-btn" aria-label="Toggle sidebar">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <form class="app-search d-none d-lg-block">
                <div class="position-relative">
                    <input type="text" class="form-control" placeholder="Search">
                    <span class="bx bx-search-alt"></span>
                </div>
            </form>
        </div>

        <div class="d-flex">
            <div class="dropdown d-inline-block d-lg-none ms-2">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="mdi mdi-magnify"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-search-dropdown">
                    <form class="p-3">
                        <div class="form-group m-0">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search" aria-label="Search input">
                                <button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dropdown d-inline-block">
                <?php ($alertNotifications = collect($alertNotifications ?? [])); ?>
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-notifications-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="bx bx-bell bx-tada"></i>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($alertNotifications->isNotEmpty()): ?>
                        <span class="badge bg-danger rounded-pill"><?php echo e($alertNotifications->count()); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                    aria-labelledby="page-header-notifications-dropdown">
                    <div class="p-3 border-bottom">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0">Awas Sensors</h6>
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-danger-subtle text-danger">Realtime DB</span>
                            </div>
                        </div>
                    </div>

                    <div data-simplebar style="max-height: 260px;">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $alertNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <a href="<?php echo e(route('telemetry.index')); ?>" class="text-reset notification-item">
                                <div class="d-flex">
                                    <div class="avatar-xs me-3">
                                        <span class="avatar-title bg-danger rounded-circle font-size-16">
                                            <i class="bx bx-error-circle"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mt-0 mb-1"><?php echo e($alert['sensor_id']); ?> - <?php echo e($alert['parameter'] ?? $alert['type']); ?></h6>
                                        <div class="font-size-12 text-muted">
                                            <p class="mb-1"><?php echo e($alert['city'] ?? '-'); ?>, <?php echo e($alert['province'] ?? '-'); ?>. Level: <?php echo e($alert['alert_level']); ?>. Value: <?php echo e($alert['value'] ?? '-'); ?></p>
                                            <p class="mb-0"><i class="mdi mdi-clock-outline"></i> <?php echo e($alert['last_seen']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <div class="p-3 text-center text-muted">
                                Tidak ada sensor level Awas.
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="p-2 border-top d-grid">
                        <a class="btn btn-sm btn-link font-size-14 text-center text-danger" href="<?php echo e(route('telemetry.index')); ?>">
                            <i class="mdi mdi-map-marker-alert-outline me-1"></i> Open telemetry
                        </a>
                    </div>
                </div>
            </div>

            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user"
                        src="<?php echo e(isset(Auth::user()->avatar) ? asset(Auth::user()->avatar) : asset('build/images/users/avatar-1.jpg')); ?>"
                        alt="Header Avatar">
                    <span class="d-none d-xl-inline-block ms-1"><?php echo e(ucfirst(Auth::user()->name)); ?></span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="contacts-profile">
                        <i class="bx bx-user font-size-16 align-middle me-1"></i> Profile
                    </a>
                    <a class="dropdown-item d-block" href="#" data-bs-toggle="modal" data-bs-target=".change-password">
                        <i class="bx bx-wrench font-size-16 align-middle me-1"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="javascript:void();"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> Logout
                    </a>
                    <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" style="display: none;">
                        <?php echo csrf_field(); ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="modal fade change-password" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myLargeModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="change-password">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" value="<?php echo e(Auth::user()->id); ?>" id="data_id">

                    <div class="mb-3">
                        <label for="current_password">Current Password <span class="text-danger">*</span></label>
                        <input id="current-password" type="password"
                            class="form-control <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            name="current_password" autocomplete="current_password"
                            placeholder="Enter Current Password" value="<?php echo e(old('current_password')); ?>">
                        <div class="text-danger" id="current_passwordError" data-ajax-feedback="current_password"></div>
                    </div>

                    <div class="mb-3">
                        <label for="newpassword">New Password <span class="text-danger">*</span></label>
                        <input id="password" type="password"
                            class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="password"
                            autocomplete="new_password" placeholder="Enter New Password">
                        <div class="text-danger" id="passwordError" data-ajax-feedback="password"></div>
                    </div>

                    <div class="mb-3">
                        <label for="userpassword">Confirm Password <span class="text-danger">*</span></label>
                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation"
                            autocomplete="new_password" placeholder="Enter New Confirm Password">
                        <div class="text-danger" id="password_confirmError" data-ajax-feedback="password-confirm"></div>
                    </div>

                    <div class="mt-3 d-grid">
                        <button class="btn btn-primary waves-effect waves-light UpdatePassword" data-id="<?php echo e(Auth::user()->id); ?>"
                            type="submit">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/layouts/topbar.blade.php ENDPATH**/ ?>