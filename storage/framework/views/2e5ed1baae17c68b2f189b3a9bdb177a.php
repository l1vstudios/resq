<?php $__env->startSection('title'); ?> Warning Stations <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Stations <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Warning Stations <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $clusters = collect(config('resq_dummy.clusters'))->keyBy('id');
    $monitoringStations = collect(config('resq_dummy.monitoring_stations'))->keyBy('id');
    $warningStations = config('resq_dummy.warning_stations');
    $wsControllers = collect(config('resq_dummy.ws_controllers'))->keyBy('warning_station_id');
    $dangerWarning = collect($warningStations)->firstWhere('status', 'Danger');
    $dangerController = $wsControllers[$dangerWarning['id']];
    $sourceMonitoringStation = $monitoringStations[$dangerWarning['source_monitoring_station_id']];
?>

<div class="row">
    <div class="col-xl-5">
        <div class="card" id="command-test">
            <div class="card-body">
                <h4 class="card-title mb-4">Warning Station Identity</h4>
                <form>
                    <div class="mb-3">
                        
                        
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cluster</label>
                        <select class="form-select">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $clusters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cluster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option <?php if($cluster['id'] === $sourceMonitoringStation['cluster_id']): echo 'selected'; endif; ?>><?php echo e($cluster['id']); ?> - <?php echo e($cluster['name']); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Warning Station ID</label>
                            <input type="text" class="form-control" value="<?php echo e($dangerWarning['id']); ?>" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Station Name</label>
                            <input type="text" class="form-control" value="<?php echo e($dangerWarning['name']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zone ID</label>
                        <input type="text" class="form-control" value="<?php echo e($dangerWarning['zone_id']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Coordinate</label>
                        <input type="text" class="form-control" value="<?php echo e($dangerWarning['coordinate']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Installation Date</label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Station Description</label>
                        <textarea class="form-control" rows="3" placeholder="Description"></textarea>
                    </div>
                    <button type="button" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Save Warning Station
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">WS Controller Registration</h4>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Controller ID</label>
                            <input type="text" class="form-control" value="<?php echo e($dangerController['id']); ?>" disabled>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Controller Model</label>
                        <input type="text" class="form-control" value="<?php echo e($dangerController['controller_model']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Vendor</label>
                        <input type="text" class="form-control" value="<?php echo e($dangerController['vendor']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Serial Number</label>
                        <input type="text" class="form-control" value="<?php echo e($dangerController['serial_number']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Firmware Version</label>
                        <input type="text" class="form-control" value="<?php echo e($dangerController['firmware_version']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Device Label / QR Code</label>
                        <input type="text" class="form-control" value="<?php echo e($dangerController['device_label']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Output Device Configuration</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="audioSystem" checked>
                            <label class="form-check-label" for="audioSystem">Audio System</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="siren" checked>
                            <label class="form-check-label" for="siren">Siren</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="lampBeacon">
                            <label class="form-check-label" for="lampBeacon">Lamp / Beacon</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="ledDisplay" checked>
                            <label class="form-check-label" for="ledDisplay">LED Display</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="externalRelay">
                            <label class="form-check-label" for="externalRelay">External Relay</label>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary">
                    <i class="bx bx-toggle-left me-1"></i> Save Output Device
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Command Test</h4>
                <div class="row align-items-end">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Command</label>
                        <select class="form-select">
                            <option>send_test_command</option>
                            <option>reset_command</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">ACK Response</label>
                        <input type="text" class="form-control" value="Waiting" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Command Latency</label>
                        <input type="text" class="form-control" value="0 ms" readonly>
                    </div>
                </div>
                <button type="button" class="btn btn-danger">
                    <i class="bx bx-broadcast me-1"></i> Kirim peringatan ke masyarakat
                </button>
            </div>
        </div>

    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Warning Station List</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Warning ID</th>
                                <th>Source MS</th>
                                <th>Cluster</th>
                                <th>WSC ID</th>
                                <th>WSC Model</th>
                                <th>WSC Status</th>
                                <th>Status</th>
                                <th>Public Warning</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $warningStations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <?php ($controller = $wsControllers[$station['id']]); ?>
                                <tr>
                                    <td><?php echo e($station['id']); ?></td>
                                    <td><?php echo e($station['source_monitoring_station_id']); ?></td>
                                    <td><?php echo e($station['cluster_id']); ?></td>
                                    <td><?php echo e($controller['id']); ?></td>
                                    <td><?php echo e($controller['controller_model']); ?></td>
                                    <td><?php echo e($controller['controller_status']); ?></td>
                                    <td>
                                        <span class="badge <?php echo e($station['status'] === 'Danger' ? 'bg-danger' : 'bg-success'); ?>">
                                            <?php echo e($station['status']); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($station['public_warning_enabled']): ?>
                                            <button type="button" class="btn btn-danger btn-sm">
                                                <i class="bx bx-broadcast me-1"></i> Kirim peringatan ke masyarakat
                                            </button>
                                            <span class="text-muted ms-2"><?php echo e($station['ack_response']); ?></span>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-light btn-sm" disabled>
                                                <i class="bx bx-time-five me-1"></i> Standby
                                            </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/warning-stations/index.blade.php ENDPATH**/ ?>