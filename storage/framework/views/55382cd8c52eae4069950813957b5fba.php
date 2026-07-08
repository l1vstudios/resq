<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Project Configuration</li>

                <li class="<?php echo e(request()->routeIs('root') || request()->routeIs('dashboard') || request()->routeIs('project-configuration') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('dashboard')); ?>" class="waves-effect <?php echo e(request()->routeIs('root') || request()->routeIs('dashboard') || request()->routeIs('project-configuration') ? 'active' : ''); ?>">
                        <i class="bx bx-home-circle"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="mm-active">
                    <a href="javascript: void(0);" class="has-arrow waves-effect" aria-expanded="true">
                        <i class="bx bx-cog"></i>
                        <span>Configuration</span>
                    </a>
                    <ul class="sub-menu mm-show" aria-expanded="true">
                        <li class="<?php echo e(request()->routeIs('projects.*') ? 'mm-active' : ''); ?>">
                            <a href="<?php echo e(route('projects.index')); ?>" class="<?php echo e(request()->routeIs('projects.*') ? 'active' : ''); ?>">
                                Project Setup
                            </a>
                        </li>

                    </ul>
                </li>

                <li class="mm-active">
                    <a href="javascript: void(0);" class="has-arrow waves-effect" aria-expanded="true">
                        <i class="bx bx-list-check"></i>
                        <span>Registered</span>
                    </a>
                    <ul class="sub-menu mm-show" aria-expanded="true">
                        <li>
                            <a href="<?php echo e(route('clusters.index')); ?>" class="<?php echo e(request()->routeIs('clusters.*') ? 'active' : ''); ?>">
                                Geospatial Workspace
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('monitoring-stations.index')); ?>" class="<?php echo e(request()->routeIs('monitoring-stations.*') ? 'active' : ''); ?>">
                                Monitoring Station
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(route('warning-stations.index')); ?>" class="<?php echo e(request()->routeIs('warning-stations.*') ? 'active' : ''); ?>">
                                Warning Station
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="menu-title">Device Setup</li>

                <li class="<?php echo e(request()->routeIs('mst-prefixes.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('mst-prefixes.index')); ?>" class="waves-effect <?php echo e(request()->routeIs('mst-prefixes.*') ? 'active' : ''); ?>">
                        <i class="bx bx-purchase-tag-alt"></i>
                        <span>Prefix Sensors</span>
                    </a>
                </li>

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

                <li class="<?php echo e(request()->routeIs('command-test.*') ? 'mm-active' : ''); ?>">
                    <a href="<?php echo e(route('command-test.index')); ?>#command-test" class="waves-effect <?php echo e(request()->routeIs('command-test.*') ? 'active' : ''); ?>">
                        <i class="bx bx-send"></i>
                        <span>Command Test</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</div>
<!-- Left Sidebar End -->
<?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/layouts/sidebar.blade.php ENDPATH**/ ?>