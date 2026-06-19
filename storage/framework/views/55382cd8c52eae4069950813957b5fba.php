<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">RESQ</li>

                <li class="<?php echo e(request()->routeIs('root') || request()->routeIs('dashboard') || request()->routeIs('project-configuration') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('dashboard')); ?>" class="waves-effect <?php echo e(request()->routeIs('root') || request()->routeIs('dashboard') || request()->routeIs('project-configuration') ? 'active' : ''); ?>">
                        <i class="bx bx-home-circle"></i>
                        <span>Project Configuration</span>
                    </a>
                </li>

                <li class="menu-title">Project Setup</li>

                <li class="<?php echo e(request()->routeIs('projects.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('projects.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('projects.*') ? 'active' : ''); ?>">
                        <i class="bx bx-briefcase-alt-2"></i>
                        <span>Projects</span>
                    </a>
                </li>

                <li class="<?php echo e(request()->routeIs('clusters.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('clusters.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('clusters.*') ? 'active' : ''); ?>">
                        <i class="bx bx-map-alt"></i>
                        <span>Clusters</span>
                    </a>
                </li>

                <li class="menu-title">Stations</li>

                <li class="<?php echo e(request()->routeIs('monitoring-stations.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('monitoring-stations.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('monitoring-stations.*') ? 'active' : ''); ?>">
                        <i class="bx bx-map-pin"></i>
                        <span>Monitoring Stations</span>
                    </a>
                </li>

                <li class="<?php echo e(request()->routeIs('warning-stations.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('warning-stations.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('warning-stations.*') ? 'active' : ''); ?>">
                        <i class="bx bx-bell"></i>
                        <span>Warning Stations</span>
                    </a>
                </li>

                <li class="<?php echo e(request()->routeIs('sensors.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('sensors.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('sensors.*') ? 'active' : ''); ?>">
                        <i class="bx bx-slider-alt"></i>
                        <span>Sensor</span>
                    </a>
                </li>

                <li class="menu-title">Device Setup</li>

                <li class="<?php echo e(request()->routeIs('data-loggers.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('data-loggers.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('data-loggers.*') ? 'active' : ''); ?>">
                        <i class="bx bx-data"></i>
                        <span>Data Loggers</span>
                    </a>
                </li>

                <li class="<?php echo e(request()->routeIs('connectivity.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('connectivity.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('connectivity.*') ? 'active' : ''); ?>">
                        <i class="bx bx-wifi"></i>
                        <span>Connectivity</span>
                    </a>
                </li>

                <li class="<?php echo e(request()->routeIs('credentials.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('credentials.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('credentials.*') ? 'active' : ''); ?>">
                        <i class="bx bx-key"></i>
                        <span>Credentials</span>
                    </a>
                </li>

                <li class="menu-title">Telemetry</li>

                <li class="<?php echo e(request()->routeIs('telemetry.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('telemetry.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('telemetry.*') ? 'active' : ''); ?>">
                        <i class="bx bx-broadcast"></i>
                        <span>Telemetry Configuration</span>
                    </a>
                </li>

                <li class="<?php echo e(request()->routeIs('warning-stations.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('warning-stations.index')); ?>#command-test" class="waves-effect">
                        <i class="bx bx-send"></i>
                        <span>Command Test</span>
                    </a>
                </li>

                <li class="menu-title">Administration</li>

                <li class="<?php echo e(request()->routeIs('admins.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('admins.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('admins.*') ? 'active' : ''); ?>">
                        <i class="bx bx-user-plus"></i>
                        <span>Admins</span>
                    </a>
                </li>

                <li class="<?php echo e(request()->routeIs('customers.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('customers.list')); ?>" class="waves-effect <?php echo e(request()->routeIs('customers.*') ? 'active' : ''); ?>">
                        <i class="bx bx-group"></i>
                        <span>Customers</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- Left Sidebar End -->
<?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/layouts/sidebar.blade.php ENDPATH**/ ?>