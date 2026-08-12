<?php
    $context = $context ?? [];
    $capabilities = collect($context['capabilities'] ?? []);
    $readingMethods = $context['reading_methods'] ?? [];
    $referenceRoutes = collect($context['reference_routes'] ?? []);
    $referencePoints = collect($context['reference_points'] ?? []);
?>

<div class="row">
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Station</p><h4><?php echo e($station->station_code); ?></h4><div class="ops-context-line"><?php echo e($station->name); ?></div></div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Project</p><h4><?php echo e($context['station']['project_code'] ?? '-'); ?></h4><div class="ops-context-line"><?php echo e($context['station']['workspace_code'] ?? '-'); ?></div></div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Configurable Functions</p><h4><?php echo e($capabilities->where('available', true)->count()); ?></h4><div class="ops-context-line">Derived from station instrumentation</div></div></div>
    </div>
</div>

<div class="row">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $capabilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $capability): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <?php if(! ($capability['available'] ?? false)) continue; ?>
        <?php
            $function = $capability['function'];
            $config = $capability['configuration']['configuration'] ?? [];
            $saved = $capability['configuration'] ?? null;
        ?>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1"><?php echo e($function); ?></h4>
                            <div class="text-muted small"><?php echo e($capability['basis']); ?></div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($saved): ?>
                            <?php echo $__env->make('modules.platform-operations.partials.status-badge', ['status' => $saved['status']], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <form method="POST" action="<?php echo e(route('client-operations.function-configuration.store', [$station, $function])); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reading Method</label>
                                <select name="reading_method" class="form-select" required>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $readingMethods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($method); ?>" <?php if(($saved['reading_method'] ?? 'Absolute') === $method): echo 'selected'; endif; ?>><?php echo e($method); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['draft', 'active', 'inactive']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($status); ?>" <?php if(($saved['status'] ?? 'draft') === $status): echo 'selected'; endif; ?>><?php echo e(ucfirst($status)); ?></option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($function === 'TDE'): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Data Window</label>
                                    <input name="data_window_value" type="number" min="1" max="10080" class="form-control" value="<?php echo e($config['data_window']['value'] ?? 60); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Window Unit</label>
                                    <select name="data_window_unit" class="form-select" required>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['minutes', 'hours', 'days']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <option value="<?php echo e($unit); ?>" <?php if(($config['data_window']['unit'] ?? 'minutes') === $unit): echo 'selected'; endif; ?>><?php echo e(ucfirst($unit)); ?></option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        <?php elseif($function === 'Discharge'): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cross-sectional Area</label>
                                    <input name="cross_sectional_area" type="number" min="0" step="0.0001" class="form-control" value="<?php echo e($config['cross_sectional_area'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Manning's n</label>
                                    <input name="manning_n" type="number" min="0" step="0.0001" class="form-control" value="<?php echo e($config['manning_n'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Coefficient Cd</label>
                                    <input name="coefficient_cd" type="number" min="0" step="0.0001" class="form-control" value="<?php echo e($config['coefficient_cd'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Unit</label>
                                    <input name="unit" type="text" maxlength="30" class="form-control" value="<?php echo e($config['unit'] ?? 'm3/s'); ?>" required>
                                </div>
                            </div>
                        <?php elseif($function === 'CFPE'): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reference Route</label>
                                    <select name="reference_route_id" class="form-select" required>
                                        <option value="">Select route</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $referenceRoutes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <option value="<?php echo e($route['id']); ?>" <?php if((int) ($config['reference_route_id'] ?? 0) === (int) $route['id']): echo 'selected'; endif; ?>><?php echo e($route['route_code']); ?> - <?php echo e($route['name']); ?></option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reference BM</label>
                                    <select name="reference_bm_id" class="form-select">
                                        <option value="">None</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $referencePoints; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $point): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <option value="<?php echo e($point['id']); ?>" <?php if((int) ($config['reference_bm_id'] ?? 0) === (int) $point['id']): echo 'selected'; endif; ?>><?php echo e($point['point_code']); ?> - <?php echo e($point['name']); ?></option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Offset Direction</label>
                                    <select name="offset_direction" class="form-select" required>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['left', 'right', 'upstream', 'downstream', 'centerline']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $direction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <option value="<?php echo e($direction); ?>" <?php if(($config['offset_direction'] ?? 'centerline') === $direction): echo 'selected'; endif; ?>><?php echo e(\Illuminate\Support\Str::headline($direction)); ?></option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Offset Distance</label>
                                    <input name="offset_distance" type="number" min="0" step="0.0001" class="form-control" value="<?php echo e($config['offset_distance'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Station Ground Zero / Chainage</label>
                                    <input name="station_ground_zero_chainage" type="number" min="0" step="0.0001" class="form-control" value="<?php echo e($config['station_ground_zero_chainage'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Uncertainty Factor</label>
                                    <input name="uncertainty_factor" type="number" min="0" step="0.0001" class="form-control" value="<?php echo e($config['uncertainty_factor'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="d-flex gap-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="validate" value="1" id="validate-<?php echo e($function); ?>">
                                    <label class="form-check-label" for="validate-<?php echo e($function); ?>">Validate</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="activate" value="1" id="activate-<?php echo e($function); ?>">
                                    <label class="form-check-label" for="activate-<?php echo e($function); ?>">Activate</label>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <div class="border-top pt-3 mt-1">
                            <div class="text-muted small mb-2"><?php echo e(collect($capability['unresolved_rules'] ?? [])->implode(' ')); ?></div>
                            <button type="submit" class="btn btn-primary">Save <?php echo e($function); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
</div>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($capabilities->where('available', true)->isEmpty()): ?>
    <div class="card"><div class="card-body text-center text-muted">No function configuration is available for this station instrumentation.</div></div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/client-operations/partials/function-configuration-panel.blade.php ENDPATH**/ ?>