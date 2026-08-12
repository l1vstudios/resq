<?php
    $permissions = $permissions ?? [];
    $activeTab = $activeTab ?? 'state';
    $stationRouteName = $stationRouteName ?? 'platform-operations.stations.show';
    $showFutureTabs = $showFutureTabs ?? false;
    $functionConfigUrl = $functionConfigUrl ?? null;
    $functionConfigEnabled = $functionConfigEnabled ?? false;
    $reportingUrl = $reportingUrl ?? null;
    $reportingEnabled = $reportingEnabled ?? false;
    $showReportingFutureTab = $showReportingFutureTab ?? false;
?>
<ul class="nav ops-station-tabs" role="tablist">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($permissions['state'] ?? false): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo e($activeTab === 'state' ? 'active' : ''); ?>" href="<?php echo e(route($stationRouteName, [$station, 'tab' => 'state'])); ?>">
                <i class="bx bx-line-chart"></i>
                <span>Operational State</span>
            </a>
        </li>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($permissions['integrity'] ?? false): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo e($activeTab === 'integrity' ? 'active' : ''); ?>" href="<?php echo e(route($stationRouteName, [$station, 'tab' => 'integrity'])); ?>">
                <i class="bx bx-shield-quarter"></i>
                <span>Operational Integrity</span>
            </a>
        </li>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($permissions['administrative'] ?? false): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo e($activeTab === 'administrative' ? 'active' : ''); ?>" href="<?php echo e(route($stationRouteName, [$station, 'tab' => 'administrative'])); ?>">
                <i class="bx bx-id-card"></i>
                <span>Administrative Monitoring</span>
            </a>
        </li>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showFutureTabs): ?>
        <li class="nav-item">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($functionConfigEnabled && $functionConfigUrl): ?>
                <a class="nav-link" href="<?php echo e($functionConfigUrl); ?>"><i class="bx bx-cog"></i><span>Function Configuration</span></a>
            <?php else: ?>
                <span class="nav-link disabled" aria-disabled="true"><i class="bx bx-cog"></i><span>Function Configuration</span></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </li>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showFutureTabs && $showReportingFutureTab): ?>
        <li class="nav-item">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($reportingEnabled && $reportingUrl): ?>
                <a class="nav-link" href="<?php echo e($reportingUrl); ?>"><i class="bx bx-file"></i><span>Reporting &amp; Export</span></a>
            <?php else: ?>
                <span class="nav-link disabled" aria-disabled="true"><i class="bx bx-file"></i><span>Reporting &amp; Export</span></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </li>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</ul>
<?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/platform-operations/partials/station-tabs.blade.php ENDPATH**/ ?>