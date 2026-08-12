<?php
    $value = strtoupper((string) ($status ?? 'UNKNOWN'));
    $label = $label ?? ($status ?? 'Unknown');
    $class = match (true) {
        in_array($value, ['HEALTHY', 'NORMAL', 'ACTIVE', 'ONLINE', 'CONNECTED', 'FRESH', 'REGISTERED', 'OK', 'AVAILABLE'], true) => 'ops-status-success',
        in_array($value, ['WASPADA', 'WARNING', 'SIAGA', 'DEGRADED', 'PARTIAL', 'UNKNOWN', 'EXPIRING SOON', 'NOT_VALIDATED', 'DRAFT'], true) => 'ops-status-warning',
        in_array($value, ['AWAS', 'CRITICAL', 'DANGER', 'ATTENTION', 'NOT REGISTERED'], true) => 'ops-status-danger',
        in_array($value, ['OFFLINE', 'INACTIVE', 'EXPIRED', 'SUSPENDED', 'STALE'], true) => 'ops-status-muted',
        default => 'ops-status-info',
    };
?>
<span class="ops-status <?php echo e($class); ?>"><?php echo e($label); ?></span>
<?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/platform-operations/partials/status-badge.blade.php ENDPATH**/ ?>