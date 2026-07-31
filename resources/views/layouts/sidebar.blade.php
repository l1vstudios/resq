<!-- ========== Left Sidebar Start ========== -->
<style>
    .vertical-collpsed .main-content {
        position: relative;
        z-index: 1;
    }

    .vertical-collpsed .vertical-menu {
        z-index: 1100 !important;
    }

    .vertical-collpsed .vertical-menu #sidebar-menu,
    .vertical-collpsed .vertical-menu #sidebar-menu > ul > li:hover {
        position: relative;
        z-index: 1101 !important;
    }

    .vertical-collpsed .vertical-menu #sidebar-menu > ul > li:hover > a {
        background-color: #f5f5f5;
        color: #556ee6;
        position: relative;
        width: 260px !important;
        z-index: 1102 !important;
    }

    .vertical-collpsed .vertical-menu #sidebar-menu > ul > li:hover > a span {
        display: inline !important;
    }

    .vertical-collpsed .vertical-menu #sidebar-menu > ul > li:hover > ul {
        display: block !important;
        z-index: 1102 !important;
    }

    .vertical-collpsed .vertical-menu #sidebar-menu > ul > li > a {
        overflow: visible;
    }
</style>

<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Project Configuration</li>

                <li class="{{ request()->routeIs('root') || request()->routeIs('dashboard') || request()->routeIs('project-configuration') ? 'mm-active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="waves-effect {{ request()->routeIs('root') || request()->routeIs('dashboard') || request()->routeIs('project-configuration') ? 'active' : '' }}">
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
                        <li class="{{ request()->routeIs('projects.*') ? 'mm-active' : '' }}">
                            <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">
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
                            <a href="{{ route('clusters.index') }}" class="{{ request()->routeIs('clusters.*') ? 'active' : '' }}">
                                Geospatial Workspace
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('monitoring-stations.index') }}" class="{{ request()->routeIs('monitoring-stations.*') ? 'active' : '' }}">
                                Monitoring Station
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('warning-stations.index') }}" class="{{ request()->routeIs('warning-stations.*') ? 'active' : '' }}">
                                Warning Station
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="menu-title">Device Setup</li>

                <li class="{{ request()->routeIs('mst-prefixes.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('mst-prefixes.index') }}" class="waves-effect {{ request()->routeIs('mst-prefixes.*') ? 'active' : '' }}">
                        <i class="bx bx-purchase-tag-alt"></i>
                        <span>Prefix Sensors</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('modbus-configuration.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('modbus-configuration.index') }}" class="waves-effect {{ request()->routeIs('modbus-configuration.*') ? 'active' : '' }}">
                        <i class="bx bx-cog"></i>
                        <span>Modbus Configuration</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('rednode-pin-scan.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('rednode-pin-scan.index') }}" class="waves-effect {{ request()->routeIs('rednode-pin-scan.*') ? 'active' : '' }}">
                        <i class="bx bx-search-alt-2"></i>
                        <span>RedNode Pin Scan</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('data-loggers.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('data-loggers.index') }}" class="waves-effect {{ request()->routeIs('data-loggers.*') ? 'active' : '' }}">
                        <i class="bx bx-data"></i>
                        <span>Data Loggers</span>
                    </a>
                </li>

                <li class="menu-title">Telemetry</li>

                <li class="{{ request()->routeIs('canonical-catalog.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('canonical-catalog.index') }}" class="waves-effect {{ request()->routeIs('canonical-catalog.*') ? 'active' : '' }}">
                        <i class="bx bx-book-content"></i>
                        <span>Canonical Catalog</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('mapping-workbench.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('mapping-workbench.index') }}" class="waves-effect {{ request()->routeIs('mapping-workbench.*') ? 'active' : '' }}">
                        <i class="bx bx-transfer-alt"></i>
                        <span>Mapping Workbench</span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('canonical-trace.*') ? 'mm-active' : '' }}"><a href="{{ route('canonical-trace.index') }}" class="waves-effect {{ request()->routeIs('canonical-trace.*') ? 'active' : '' }}"><i class="bx bx-git-branch"></i><span>Canonical Trace & Replay</span></a></li>

                <li class="{{ request()->routeIs('telemetry.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('telemetry.index') }}" class="waves-effect {{ request()->routeIs('telemetry.*') ? 'active' : '' }}">
                        <i class="bx bx-broadcast"></i>
                        <span>Telemetry Configuration</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('command-test.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('command-test.index') }}#command-test" class="waves-effect {{ request()->routeIs('command-test.*') ? 'active' : '' }}">
                        <i class="bx bx-send"></i>
                        <span>Command Test</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</div>
<!-- Left Sidebar End -->
