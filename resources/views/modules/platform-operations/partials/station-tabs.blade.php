@php
    $permissions = $permissions ?? [];
    $activeTab = $activeTab ?? 'state';
    $stationRouteName = $stationRouteName ?? 'platform-operations.stations.show';
    $showFutureTabs = $showFutureTabs ?? false;
    $functionConfigUrl = $functionConfigUrl ?? null;
    $functionConfigEnabled = $functionConfigEnabled ?? false;
    $reportingUrl = $reportingUrl ?? null;
    $reportingEnabled = $reportingEnabled ?? false;
    $showReportingFutureTab = $showReportingFutureTab ?? false;
@endphp
<ul class="nav ops-station-tabs" role="tablist">
    @if($permissions['state'] ?? false)
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'state' ? 'active' : '' }}" href="{{ route($stationRouteName, [$station, 'tab' => 'state']) }}">
                <i class="bx bx-line-chart"></i>
                <span>Operational State</span>
            </a>
        </li>
    @endif
    @if($permissions['integrity'] ?? false)
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'integrity' ? 'active' : '' }}" href="{{ route($stationRouteName, [$station, 'tab' => 'integrity']) }}">
                <i class="bx bx-shield-quarter"></i>
                <span>Operational Integrity</span>
            </a>
        </li>
    @endif
    @if($permissions['administrative'] ?? false)
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'administrative' ? 'active' : '' }}" href="{{ route($stationRouteName, [$station, 'tab' => 'administrative']) }}">
                <i class="bx bx-id-card"></i>
                <span>Administrative Monitoring</span>
            </a>
        </li>
    @endif
    @if($showFutureTabs)
        <li class="nav-item">
            @if($functionConfigEnabled && $functionConfigUrl)
                <a class="nav-link" href="{{ $functionConfigUrl }}"><i class="bx bx-cog"></i><span>Function Configuration</span></a>
            @else
                <span class="nav-link disabled" aria-disabled="true"><i class="bx bx-cog"></i><span>Function Configuration</span></span>
            @endif
        </li>
    @endif
    @if($showFutureTabs && $showReportingFutureTab)
        <li class="nav-item">
            @if($reportingEnabled && $reportingUrl)
                <a class="nav-link" href="{{ $reportingUrl }}"><i class="bx bx-file"></i><span>Reporting &amp; Export</span></a>
            @else
                <span class="nav-link disabled" aria-disabled="true"><i class="bx bx-file"></i><span>Reporting &amp; Export</span></span>
            @endif
        </li>
    @endif
</ul>
