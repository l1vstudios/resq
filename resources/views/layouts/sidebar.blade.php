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

@php
    $sidebarUser = auth()->user();
    $sidebarIsClient = $sidebarUser?->isClientUser();
@endphp

<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">{{ $sidebarIsClient ? 'Client Operations' : 'Project Configuration' }}</li>

                <li class="{{ request()->routeIs('root') || request()->routeIs('dashboard') || request()->routeIs('project-configuration') ? 'mm-active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="waves-effect {{ request()->routeIs('root') || request()->routeIs('dashboard') || request()->routeIs('project-configuration') ? 'active' : '' }}">
                        <i class="bx bx-home-circle"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if($sidebarIsClient)
                    <li class="{{ request()->routeIs('client-operations.*') ? 'mm-active' : '' }}">
                        <a href="javascript: void(0);" class="has-arrow waves-effect" aria-expanded="{{ request()->routeIs('client-operations.*') ? 'true' : 'false' }}">
                            <i class="bx bx-radar"></i>
                            <span>Client Operations</span>
                        </a>
                        <ul class="sub-menu {{ request()->routeIs('client-operations.*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->routeIs('client-operations.*') ? 'true' : 'false' }}">
                            <li>
                                <a href="{{ route('client-operations.index') }}" class="{{ (request()->routeIs('client-operations.index') && ! request()->query('tab')) || request()->routeIs('client-operations.projects.show') || request()->routeIs('client-operations.corridors.*') || (request()->routeIs('client-operations.stations.show') && in_array(request()->query('tab'), [null, 'state'], true)) ? 'active' : '' }}">
                                    Operational State
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('client-operations.index', ['tab' => 'integrity']) }}" class="{{ request()->query('tab') === 'integrity' ? 'active' : '' }}">
                                    Operational Integrity
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('client-operations.index', ['tab' => 'administrative']) }}" class="{{ request()->query('tab') === 'administrative' ? 'active' : '' }}">
                                    Administrative Monitoring
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('client-operations.function-configuration.index') }}" class="{{ request()->routeIs('client-operations.function-configuration.*') ? 'active' : '' }}">
                                    Function Configuration
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('client-operations.reporting.index') }}" class="{{ request()->routeIs('client-operations.reporting.*') ? 'active' : '' }}">
                                    Reporting &amp; Export
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('client-operations.inbox.index') }}" class="{{ request()->routeIs('client-operations.inbox.*') ? 'active' : '' }}">
                                    Inbox
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('client-operations.users.index') }}" class="{{ request()->routeIs('client-operations.users.*') ? 'active' : '' }}">
                                    Client Users
                                </a>
                            </li>
                        </ul>
                    </li>
                @else
                <li class="{{ request()->routeIs('monitoring.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('monitoring.index') }}" class="waves-effect {{ request()->routeIs('monitoring.*') ? 'active' : '' }}">
                        <i class="bx bx-pulse"></i>
                        <span>Monitoring</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('platform-operations.*') ? 'mm-active' : '' }}">
                    <a href="javascript: void(0);" class="has-arrow waves-effect" aria-expanded="{{ request()->routeIs('platform-operations.*') ? 'true' : 'false' }}">
                        <i class="bx bx-radar"></i>
                        <span>Platform Operations</span>
                    </a>
                    <ul class="sub-menu {{ request()->routeIs('platform-operations.*') ? 'mm-show' : '' }}" aria-expanded="{{ request()->routeIs('platform-operations.*') ? 'true' : 'false' }}">
                        <li>
                            <a href="{{ route('platform-operations.index') }}" class="{{ request()->routeIs('platform-operations.index') || request()->routeIs('platform-operations.projects.*') || request()->routeIs('platform-operations.corridors.*') || (request()->routeIs('platform-operations.stations.show') && in_array(request()->query('tab'), [null, 'state'], true)) ? 'active' : '' }}">
                                Operational State
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('platform-operations.integrity.index') }}" class="{{ request()->routeIs('platform-operations.integrity.*') || (request()->routeIs('platform-operations.stations.show') && request()->query('tab') === 'integrity') ? 'active' : '' }}">
                                Operational Integrity
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('platform-operations.administrative.index') }}" class="{{ request()->routeIs('platform-operations.administrative.*') || (request()->routeIs('platform-operations.stations.show') && request()->query('tab') === 'administrative') ? 'active' : '' }}">
                                Administrative Monitoring
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="mm-active">
                    <a href="javascript: void(0);" class="has-arrow waves-effect" aria-expanded="true">
                        <i class="bx bx-cog"></i>
                        <span>Configuration</span>
                    </a>
                    <ul class="sub-menu mm-show" aria-expanded="true">
                        <li class="{{ request()->routeIs('projects.index') ? 'mm-active' : '' }}">
                            <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.index') ? 'active' : '' }}">
                                Project Setup
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('canonical-database.*') || request()->routeIs('canonical-mapping.*') ? 'mm-active' : '' }}">
                            <a href="{{ route('canonical-database.index') }}" class="{{ request()->routeIs('canonical-database.*') || request()->routeIs('canonical-mapping.*') ? 'active' : '' }}">
                                Canonical Database
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

                <li class="{{ request()->routeIs('mini-server.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('mini-server.index') }}" class="waves-effect {{ request()->routeIs('mini-server.*') ? 'active' : '' }}">
                        <i class="bx bx-server"></i>
                        <span>Mini Server</span>
                    </a>
                </li>

                <li class="menu-title">Telemetry</li>

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

                <li class="menu-title">Administration</li>

                <li class="{{ request()->routeIs('admins.*') ? 'mm-active' : '' }}">
                    <a href="{{ route('admins.index') }}" class="waves-effect {{ request()->routeIs('admins.*') ? 'active' : '' }}">
                        <i class="bx bx-user-circle"></i>
                        <span>Admin Management</span>
                    </a>
                </li>
                @endif

            </ul>
        </div>
    </div>
</div>
<!-- Left Sidebar End -->
