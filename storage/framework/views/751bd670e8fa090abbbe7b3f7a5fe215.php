<?php $__env->startSection('title'); ?> Data Loggers <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Device Setup <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Data Loggers <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<?php
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
    $dataLoggerDiscoveries = collect($dataLoggerDiscoveries ?? []);
    $monitoringStations = collect($monitoringStations ?? []);
?>

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Data Logger Setup</h4>
                <form method="POST" action="<?php echo e(route('data-loggers.store')); ?>" id="data-logger-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="discovery_id">
                    <div class="mb-3">
                        <label class="form-label">Monitoring Station</label>
                        <select name="monitoring_station_id" class="form-select">
                            <option value="">-</option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $monitoringStations->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <option value="<?php echo e($station['db_id']); ?>"><?php echo e($station['id']); ?> - <?php echo e($station['name']); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logger ID</label>
                        <input name="logger_code" class="form-control" placeholder="DL-PDG-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Serial Number</label>
                        <input name="serial_number" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input name="logger_model" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vendor</label>
                            <input name="vendor" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Firmware</label>
                            <input name="firmware_version" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Device Label / QR</label>
                            <input name="device_label" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IP / Host Remote</label>
                        <input name="remote_host" class="form-control" placeholder="192.168.3.1">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">SSH Port</label>
                            <input type="number" name="remote_ssh_port" class="form-control" value="22" min="1" max="65535">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">SSH User</label>
                            <input name="remote_ssh_user" class="form-control" placeholder="root">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SSH Password</label>
                        <input type="password" name="remote_ssh_password" class="form-control" placeholder="Biarkan kosong kalau sudah pernah disimpan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gateway Path</label>
                        <input name="remote_gateway_path" class="form-control" placeholder="/root/rednode-gateway">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="logger_status" class="form-select" required>
                            <option>Active</option>
                            <option>Inactive</option>
                            <option>Maintenance</option>
                            <option>Fault</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save / Update Logger</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Detected Gateway Devices</h4>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Device</th>
                                <th>Firmware</th>
                                <th>IP</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $dataLoggerDiscoveries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $discovery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <?php
                                    $claimCode = $discovery['logger_code']
                                        ?: ($discovery['serial_number'] ?: ($discovery['hostname'] ?: 'DL-' . str_pad((string) $discovery['db_id'], 3, '0', STR_PAD_LEFT)));
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo e($discovery['device_label'] ?: $discovery['hostname'] ?: $claimCode); ?></div>
                                        <small class="text-muted d-block"><?php echo e($discovery['serial_number'] ?: $discovery['device_uid'] ?: '-'); ?></small>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($discovery['matched_logger_code'])): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><?php echo e($discovery['matched_logger_code']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?php echo e($discovery['status'] ?? 'Detected'); ?></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo e($discovery['firmware_version'] ?: '-'); ?></div>
                                        <small class="text-muted"><?php echo e($discovery['logger_model'] ?: '-'); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo e($discovery['request_ip'] ?: '-'); ?></div>
                                        <small class="text-muted"><?php echo e($discovery['last_seen_at'] ?: '-'); ?></small>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            data-edit-form="#data-logger-form"
                                            data-edit-fields="<?php echo e(base64_encode(json_encode([
                                                'discovery_id' => $discovery['db_id'] ?? '',
                                                'logger_code' => $claimCode,
                                                'serial_number' => $discovery['serial_number'] ?? '',
                                                'logger_model' => $discovery['logger_model'] ?? '',
                                                'vendor' => $discovery['vendor'] ?? '',
                                                'firmware_version' => $discovery['firmware_version'] ?? '',
                                                'device_label' => $discovery['device_label'] ?: ($discovery['hostname'] ?? ''),
                                                'remote_host' => $discovery['request_ip'] ?? '',
                                                'remote_ssh_port' => 22,
                                                'remote_ssh_user' => 'root',
                                                'remote_gateway_path' => '/root/rednode-gateway',
                                                'logger_status' => 'Active',
                                            ]))); ?>">Use</button>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada gateway yang terdeteksi.</td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Test Remote Logger</h4>
                <div class="mb-3">
                    <label class="form-label">Data Logger</label>
                    <select class="form-select" id="remote-test-logger" <?php if($dataLoggers->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $dataLoggers->whereNotNull('db_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $logger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($logger['db_id']); ?>"><?php echo e($logger['id']); ?> - <?php echo e($logger['remote_host'] ?? 'IP belum diisi'); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-outline-primary" id="remote-test-run" <?php if($dataLoggers->whereNotNull('db_id')->isEmpty()): echo 'disabled'; endif; ?>>
                    <i class="bx bx-wifi me-1"></i> Ping IP Logger
                </button>
                <div class="form-text">Ping hanya cek IP bisa dijangkau. Scan gateway tetap butuh SSH, Node.js, folder gateway, dan file script di logger.</div>
                <div class="alert alert-info mt-3 mb-0 d-none" id="remote-test-message"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Data Logger List</h4>
                <div class="alert alert-info d-none" id="gateway-mode-message"></div>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Logger ID</th>
                                <th>Monitoring Station</th>
                                <th>Serial Number</th>
                                <th>Remote IP</th>
                                <th>Model</th>
                                <th>Vendor</th>
                                <th>Firmware</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $dataLoggers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $logger): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($logger['id'] ?? '-'); ?></td>
                                    <td><?php echo e($logger['monitoring_station_id'] ?? '-'); ?></td>
                                    <td><?php echo e($logger['serial_number'] ?? '-'); ?></td>
                                    <td>
                                        <div><?php echo e($logger['remote_host'] ?? '-'); ?></div>
                                        <small class="text-muted">
                                            <?php echo e($logger['remote_ssh_user'] ?? '-'); ?>{{ $logger['remote_ssh_port'] ?? 22 }}
                                        </small>
                                    </td>
                                    <td><?php echo e($logger['logger_model'] ?? '-'); ?></td>
                                    <td><?php echo e($logger['vendor'] ?? '-'); ?></td>
                                    <td><?php echo e($logger['firmware_version'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo e(($logger['logger_status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary'); ?>"><?php echo e($logger['logger_status'] ?? '-'); ?></span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($logger['remote_last_status'])): ?>
                                            <div><small class="<?php echo e($logger['remote_last_status'] === 'Success' ? 'text-success' : 'text-danger'); ?>"><?php echo e($logger['remote_last_status']); ?></small></div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($logger['db_id'])): ?>
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                                    data-logger-metadata="<?php echo e(base64_encode(json_encode($logger['metadata'] ?? []))); ?>">
                                                    Metadata
                                                </button>
                                                <button type="button" class="btn btn-outline-warning btn-sm"
                                                    data-gateway-mode="development"
                                                    data-logger-id="<?php echo e($logger['db_id']); ?>">
                                                    Start Development
                                                </button>
                                                <button type="button" class="btn btn-outline-success btn-sm"
                                                    data-gateway-mode="production"
                                                    data-logger-id="<?php echo e($logger['db_id']); ?>">
                                                    Start Production
                                                </button>
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                    data-edit-form="#data-logger-form"
                                                    data-edit-fields="<?php echo e(base64_encode(json_encode([
                                                        'discovery_id' => '',
                                                        'monitoring_station_id' => $logger['monitoring_station_db_id'] ?? '',
                                                        'logger_code' => $logger['id'] ?? '',
                                                        'serial_number' => $logger['serial_number'] ?? '',
                                                        'logger_model' => $logger['logger_model'] ?? '',
                                                        'vendor' => $logger['vendor'] ?? '',
                                                        'firmware_version' => $logger['firmware_version'] ?? '',
                                                        'device_label' => $logger['device_label'] ?? '',
                                                        'remote_host' => $logger['remote_host'] ?? '',
                                                        'remote_ssh_port' => $logger['remote_ssh_port'] ?? 22,
                                                        'remote_ssh_user' => $logger['remote_ssh_user'] ?? '',
                                                        'remote_gateway_path' => $logger['remote_gateway_path'] ?? '',
                                                        'logger_status' => $logger['logger_status'] ?? 'Active',
                                                    ]))); ?>">Edit</button>
                                                <form method="POST" action="<?php echo e(route('device-setup.destroy', ['type' => 'data-logger', 'id' => $logger['db_id']])); ?>">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button class="btn btn-outline-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Belum ada data logger.</td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="logger-metadata-modal" tabindex="-1" aria-labelledby="logger-metadata-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logger-metadata-title">Logger Metadata</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <tbody id="logger-metadata-rows">
                            <tr>
                                <td class="text-muted">Metadata</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
    (function () {
        const modalElement = document.getElementById('logger-metadata-modal');
        const title = document.getElementById('logger-metadata-title');
        const rows = document.getElementById('logger-metadata-rows');

        if (!modalElement || !title || !rows) {
            return;
        }

        const labels = {
            unique_device_key: 'Unique Device Key',
            unique_device_key_source: 'Unique Key Source',
            logger_code: 'Logger ID',
            monitoring_station: 'Monitoring Station',
            serial_number: 'Serial Number',
            logger_model: 'Model',
            vendor: 'Vendor',
            firmware_version: 'Firmware',
            device_label: 'Device Label / QR',
            remote_host: 'Remote IP / Host',
            remote_ssh_port: 'SSH Port',
            remote_ssh_user: 'SSH User',
            remote_gateway_path: 'Gateway Path',
            remote_last_tested_at: 'Last Connection Time',
            remote_last_status: 'Last Connection Status',
            remote_last_message: 'Last Connection Message',
            detected_device_uid: 'Detected Device UID',
            detected_device_uid_source: 'Detected UID Source',
            detected_logger_code: 'Detected Logger ID',
            detected_serial_number: 'Detected Serial',
            detected_hostname: 'Detected Hostname',
            detected_ip: 'Detected IP',
            detected_mac_addresses: 'Detected MAC',
            detected_last_seen_at: 'Detected Last Seen',
            detected_status: 'Detected Status',
            gateway_version: 'Gateway Version',
            platform: 'Platform',
        };

        function parseMetadata(encoded) {
            try {
                return JSON.parse(atob(encoded || 'e30='));
            } catch (error) {
                return {};
            }
        }

        function formatValue(value) {
            if (Array.isArray(value)) {
                return value.length ? value.join(', ') : '-';
            }

            if (value && typeof value === 'object') {
                return JSON.stringify(value, null, 2);
            }

            return value === null || value === undefined || value === '' ? '-' : String(value);
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        document.querySelectorAll('[data-logger-metadata]').forEach((button) => {
            button.addEventListener('click', function () {
                const metadata = parseMetadata(button.dataset.loggerMetadata);
                const loggerCode = metadata.logger_code || 'Logger';
                title.textContent = loggerCode + ' Metadata';

                rows.innerHTML = Object.keys(labels).map((key) => {
                    const value = formatValue(metadata[key]);
                    const safeValue = escapeHtml(value);
                    const isLong = value.length > 80 || value.includes('{') || value.includes('[');

                    return '<tr>' +
                        '<td class="text-muted" style="width: 34%;">' + escapeHtml(labels[key]) + '</td>' +
                        '<td>' + (isLong
                            ? '<pre class="mb-0 small text-wrap">' + safeValue + '</pre>'
                            : safeValue) + '</td>' +
                    '</tr>';
                }).join('');

                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            });
        });
    })();

    (function () {
        const message = document.getElementById('gateway-mode-message');
        const buttons = document.querySelectorAll('[data-gateway-mode][data-logger-id]');

        if (!message || !buttons.length) {
            return;
        }

        function setMessage(text, type) {
            message.className = 'alert alert-' + (type || 'info');
            message.textContent = text || '';
            message.classList.toggle('d-none', !text);
        }

        buttons.forEach((button) => {
            button.addEventListener('click', async function () {
                const originalText = button.textContent;
                const mode = button.dataset.gatewayMode;
                const loggerId = button.dataset.loggerId;
                const modeLabel = mode === 'production' ? 'Production' : 'Development';

                button.disabled = true;
                button.textContent = 'Starting...';
                setMessage('Mengubah .env logger ke mode ' + modeLabel + ' dan restart gateway via SSH...', 'info');

                try {
                    const response = await fetch(<?php echo json_encode(route('data-loggers.gateway-mode'), 15, 512) ?>, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': <?php echo json_encode(csrf_token(), 15, 512) ?>,
                        },
                        body: JSON.stringify({
                            data_logger_id: loggerId,
                            mode: mode,
                        }),
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok || data.ok === false) {
                        throw new Error(data.message || 'Gagal menjalankan gateway mode ' + modeLabel + '.');
                    }

                    setMessage(data.message + ' APP_URL=' + data.app_url, 'success');
                } catch (error) {
                    setMessage(error.message, 'warning');
                } finally {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });
        });
    })();

    (function () {
        const button = document.getElementById('remote-test-run');
        const select = document.getElementById('remote-test-logger');
        const message = document.getElementById('remote-test-message');

        if (!button || !select || !message) {
            return;
        }

        function setMessage(text, type) {
            message.className = 'alert mt-3 mb-0 alert-' + (type || 'info');
            message.textContent = text || '';
            message.classList.toggle('d-none', !text);
        }

        button.addEventListener('click', async function () {
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...';
            setMessage('Sedang ping IP logger dari server. Ini belum mengecek login SSH.', 'info');

            try {
                const response = await fetch(<?php echo json_encode(route('data-loggers.test-remote'), 15, 512) ?>, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': <?php echo json_encode(csrf_token(), 15, 512) ?>,
                    },
                    body: JSON.stringify({
                        data_logger_id: select.value,
                    }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    throw new Error(data.message || 'Ping logger gagal.');
                }

                setMessage(data.message + ' Host: ' + data.host, 'success');
            } catch (error) {
                setMessage(error.message, 'warning');
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        });
    })();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/data-loggers/index.blade.php ENDPATH**/ ?>