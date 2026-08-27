@extends('layouts.master')

@section('title') Project Monitoring @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Project Configuration @endslot
@slot('title') Project Monitoring @endslot
@endcomponent

@php
    $projects = collect($projects ?? []);
    $databaseReady = $databaseReady ?? false;
    $projectMonitoringLiveUrl = route('projects.live-monitoring', [], false);
@endphp

@unless ($databaseReady)
    <div class="alert alert-warning">
        Database setup belum dimigrate. Jalankan <code>php artisan migrate</code>.
    </div>
@endunless

<div class="row" id="project-monitoring-runtime" data-live-url="{{ $projectMonitoringLiveUrl }}">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
                    <div>
                        <h4 class="card-title mb-1">Project Live Monitoring</h4>
                        <p class="text-muted mb-0">Pantau nilai sensor terbaru. Start/Stop gateway dari halaman Data Loggers.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-end gap-2">
                        <div>
                            <label class="form-label mb-1">Project</label>
                            <select class="form-select" id="project-monitor-select" @disabled(! $databaseReady || $projects->whereNotNull('db_id')->isEmpty())>
                                @foreach ($projects->whereNotNull('db_id') as $item)
                                    <option value="{{ $item['db_id'] }}">{{ $item['id'] }} - {{ $item['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Logger Online</div><div class="fs-5 fw-bold text-success" id="project-monitor-online">0 / 0</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Parameter Realtime</div><div class="fs-5 fw-bold text-primary" id="project-monitor-fresh">0 / 0</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Refresh</div><div class="fs-5 fw-bold" id="project-monitor-refresh">-</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Status</div><div class="fs-5 fw-bold" id="project-monitor-state">Idle</div></div></div>
                </div>

                <div class="alert alert-info py-2 d-none" id="project-monitor-message"></div>

                <div class="mb-3" id="project-monitor-infrastructure"></div>

                <div class="btn-group mb-3" role="group" aria-label="Monitoring views">
                    <button type="button" class="btn btn-primary" data-monitor-view="realtime">Realtime Data</button>
                    <button type="button" class="btn btn-outline-primary" data-monitor-view="audit">Mapping Audit</button>
                </div>

                <div id="project-monitor-realtime-view">
                    <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Logger / Station</th>
                                <th>Sensor</th>
                                <th>Value</th>
                                <th>Multi Parameter</th>
                                <th style="width:130px;">Update</th>
                                <th style="width:110px;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="project-monitor-rows">
                            <tr><td colspan="6" class="text-center text-muted py-3">Pilih project lalu klik Start Monitoring.</td></tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <div id="project-monitor-audit-view" class="d-none">
                    <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-2">
                        <div>
                            <h5 class="mb-1">Sensor Mapping Audit</h5>
                            <div class="text-muted small">Audit raw register, decode rule, scale/offset, mapped parameter, and status.</div>
                        </div>
                        <select class="form-select form-select-sm" id="project-monitor-audit-filter" style="max-width:220px;">
                            <option value="">All Parameters</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Sensor</th>
                                    <th>Parameter</th>
                                    <th>TX / RX / Register</th>
                                    <th>Decode</th>
                                    <th>Mapped Value</th>
                                    <th>Acuan Status</th>
                                </tr>
                            </thead>
                            <tbody id="project-monitor-audit-rows">
                                <tr><td colspan="6" class="text-center text-muted py-3">Belum ada audit mapping.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th style="width:90px;">Time</th><th>Logger</th><th>Message</th><th style="width:90px;">Result</th></tr></thead>
                        <tbody id="project-monitor-log-rows">
                            <tr><td colspan="4" class="text-center text-muted py-2">Belum ada log monitoring.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        const root = document.getElementById('project-monitoring-runtime');

        if (!root) {
            return;
        }

        const csrfToken = @json(csrf_token());
        const liveUrl = root.dataset.liveUrl;
        const select = document.getElementById('project-monitor-select');
        const rowsEl = document.getElementById('project-monitor-rows');
        const logRowsEl = document.getElementById('project-monitor-log-rows');
        const messageEl = document.getElementById('project-monitor-message');
        const onlineEl = document.getElementById('project-monitor-online');
        const freshEl = document.getElementById('project-monitor-fresh');
        const refreshEl = document.getElementById('project-monitor-refresh');
        const stateEl = document.getElementById('project-monitor-state');
        const infrastructureEl = document.getElementById('project-monitor-infrastructure');
        const realtimeViewEl = document.getElementById('project-monitor-realtime-view');
        const auditViewEl = document.getElementById('project-monitor-audit-view');
        const auditRowsEl = document.getElementById('project-monitor-audit-rows');
        const auditFilterEl = document.getElementById('project-monitor-audit-filter');
        const viewButtons = document.querySelectorAll('[data-monitor-view]');
        const logs = [];
        const previousValues = new Map();
        let lastAuditRows = [];
        let timer = null;
        let liveRequestInFlight = false;
        let liveRequestSeq = 0;

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showMonitorMessage(message, type = 'info') {
            messageEl.className = 'alert py-2 alert-' + type;
            messageEl.textContent = message || '';
            messageEl.classList.toggle('d-none', !message);
        }

        function appendMonitorLog(logger, message, result) {
            logs.unshift({
                time: new Date().toLocaleTimeString(),
                logger,
                message,
                result,
            });

            logRowsEl.innerHTML = logs.slice(0, 30).map((row) => {
                const ok = String(row.result || '').toLowerCase().includes('sukses');
                return '<tr>' +
                    '<td>' + escapeHtml(row.time) + '</td>' +
                    '<td class="fw-bold">' + escapeHtml(row.logger || '-') + '</td>' +
                    '<td>' + escapeHtml(row.message || '-') + '</td>' +
                    '<td><span class="badge ' + (ok ? 'bg-success' : 'bg-danger') + '">' + escapeHtml(row.result || '-') + '</span></td>' +
                '</tr>';
            }).join('');
        }

        function appendMonitorLogLines(logger) {
            const lines = Array.isArray(logger.terminal_log) && logger.terminal_log.length
                ? logger.terminal_log
                : [logger.message];

            lines.slice().reverse().forEach((line) => {
                appendMonitorLog(logger.logger_code, line, logger.ok ? 'Sukses' : 'Gagal');
            });
        }

        function formatTime(value) {
            return value ? new Date(value).toLocaleTimeString() : '-';
        }

        function renderParameterValues(values) {
            if (!Array.isArray(values) || !values.length) {
                return '<span class="text-muted">-</span>';
            }

            return values.map((item) => {
                const label = item.label || item.parameter || '-';
                const value = item.value_text || item.value || '-';

                return '<span class="badge bg-info-subtle text-info border border-info-subtle me-1 mb-1">' +
                    escapeHtml(label) + ': ' + escapeHtml(value) +
                '</span>';
            }).join('');
        }

        function renderAuditRows(rows) {
            lastAuditRows = Array.isArray(rows) ? rows : [];

            if (!auditRowsEl) {
                return;
            }

            const selected = auditFilterEl ? auditFilterEl.value : '';
            const parameters = Array.from(new Set(lastAuditRows.map((row) => row.parameter).filter(Boolean)));

            if (auditFilterEl) {
                const current = auditFilterEl.value;
                auditFilterEl.innerHTML = '<option value="">All Parameters</option>' + parameters.map((parameter) => (
                    '<option value="' + escapeHtml(parameter) + '"' + (parameter === current ? ' selected' : '') + '>' + escapeHtml(parameter) + '</option>'
                )).join('');
            }

            const visibleRows = selected ? lastAuditRows.filter((row) => row.parameter === selected) : lastAuditRows;

            auditRowsEl.innerHTML = visibleRows.length
                ? visibleRows.map((row) => {
                    const rawRegisters = Array.isArray(row.raw_registers) ? row.raw_registers.join(', ') : (row.raw ?? '-');
                    const frame = row.modbus_frame || {};
                    const decode = [row.value_type || '-', row.byte_order || ''].join(' ').trim();
                    const basis = row.threshold
                        ? 'Threshold ' + row.threshold + (row.rule ? ' / ' + row.rule : '')
                        : (row.rule || 'Status from latest telemetry');
                    const danger = String(row.alert_level || row.status || '').toLowerCase().includes('awas');

                    return '<tr>' +
                        '<td><div class="fw-bold">' + escapeHtml(row.sensor_code || '-') + '</div><small class="text-muted">' + escapeHtml(row.logger_code || '-') + ' / ' + escapeHtml(row.station || '-') + '</small></td>' +
                        '<td><div class="fw-bold">' + escapeHtml(row.parameter || '-') + '</div><small class="text-muted">' + escapeHtml(row.source_parameter || '-') + '</small></td>' +
                        '<td><div>Address ' + escapeHtml(row.register_address ?? '-') + ' / index ' + escapeHtml(row.register_index ?? '-') + '</div><small class="text-muted d-block">TX <code>' + escapeHtml(row.tx_frame || frame.tx || '-') + '</code></small><small class="text-muted d-block">RX <code>' + escapeHtml(row.rx_frame || frame.rx || '-') + '</code></small><small class="text-muted d-block">Words ' + escapeHtml(rawRegisters) + '</small></td>' +
                        '<td><div>' + escapeHtml(decode) + '</div><small class="text-muted">scale ' + escapeHtml(row.scale_factor ?? '-') + ' + offset ' + escapeHtml(row.offset ?? '-') + '</small></td>' +
                        '<td class="fw-bold">' + escapeHtml(row.mapped_text || row.mapped_value || '-') + '</td>' +
                        '<td><span class="badge ' + (danger ? 'bg-danger' : 'bg-success') + '">' + escapeHtml(row.alert_level || row.status || '-') + '</span><div class="text-muted small">' + escapeHtml(basis) + '</div></td>' +
                    '</tr>';
                }).join('')
                : '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada audit mapping.</td></tr>';
        }

        function setMonitorView(view) {
            const isAudit = view === 'audit';

            if (realtimeViewEl) {
                realtimeViewEl.classList.toggle('d-none', isAudit);
            }

            if (auditViewEl) {
                auditViewEl.classList.toggle('d-none', !isAudit);
            }

            viewButtons.forEach((button) => {
                const active = button.dataset.monitorView === view;
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-outline-primary', !active);
            });
        }

        function renderInfrastructure(stations) {
            if (!infrastructureEl || !stations.length) {
                if (infrastructureEl) infrastructureEl.innerHTML = '';
                return;
            }

            infrastructureEl.innerHTML = '<div class="border rounded p-3 bg-light" style="font-size:0.85rem">' +
                '<div class="fw-bold mb-2"><i class="bx bx-sitemap me-1"></i>Project Infrastructure</div>' +
                stations.map(function (station) {
                    const stationBadge = '<span class="badge bg-primary-subtle text-primary me-1">' + escapeHtml(station.station_type || 'station') + '</span>';
                    const loggers = (station.loggers || []).map(function (logger) {
                        const onlineBadge = logger.online
                            ? '<span class="badge bg-success ms-1">Online</span>'
                            : '<span class="badge bg-secondary ms-1">' + escapeHtml(logger.status || 'Offline') + '</span>';
                        const sensors = (logger.sensors || []).map(function (sensor) {
                            const paramCount = sensor.parameter_count > 1 ? ' <span class="text-muted">(' + sensor.parameter_count + ' param)</span>' : '';
                            return '<div class="ms-4 text-muted"><i class="bx bx-chip me-1"></i>' + escapeHtml(sensor.sensor_code) + ' — ' + escapeHtml(sensor.type || sensor.parameter || '-') + paramCount + '</div>';
                        }).join('');
                        return '<div class="ms-3 mb-1"><i class="bx bx-server me-1 text-info"></i><strong>' + escapeHtml(logger.logger_code) + '</strong> <small class="text-muted">' + escapeHtml(logger.model || '') + '</small>' + onlineBadge + '</div>' + sensors;
                    }).join('');

                    const warnings = (station.warning_stations || []).map(function (ws) {
                        return '<div class="ms-3 text-warning"><i class="bx bx-bell me-1"></i>' + escapeHtml(ws.station_code) + ' — ' + escapeHtml(ws.name || '-') + ' <span class="badge bg-warning-subtle text-warning">' + escapeHtml(ws.status || '-') + '</span></div>';
                    }).join('');

                    return '<div class="mb-2"><div><i class="bx bx-map-pin me-1 text-primary"></i><strong>' + escapeHtml(station.station_code) + '</strong> — ' + escapeHtml(station.station_name || '-') + ' ' + stationBadge + '</div>' + loggers + (warnings || '') + '</div>';
                }).join('<hr class="my-2">') +
            '</div>';
        }

        function renderLiveData(data) {
            const summary = data.summary || {};
            const sensors = Array.isArray(data.sensors) ? data.sensors : [];

            onlineEl.textContent = (summary.online_loggers || 0) + ' / ' + (summary.loggers || 0);
            freshEl.textContent = (summary.fresh_parameters ?? summary.fresh_sensors ?? 0) + ' / ' + (summary.parameters ?? summary.sensors ?? 0);
            refreshEl.textContent = formatTime(data.generated_at);
            stateEl.textContent = (summary.online_loggers || 0) > 0 ? 'Running' : 'Idle';
            stateEl.className = 'fs-5 fw-bold ' + ((summary.online_loggers || 0) > 0 ? 'text-success' : 'text-muted');

            // Update polling interval if changed (1:1 with logger setting, max 5 min)
            const serverInterval = Math.min(Math.max(summary.poll_interval_ms || 2000, 500), 300000);
            if (serverInterval !== pollIntervalMs) {
                pollIntervalMs = serverInterval;
                if (timer) {
                    clearInterval(timer);
                    timer = setInterval(() => {
                        loadLiveData().catch((error) => showMonitorMessage(error.message, 'warning'));
                    }, pollIntervalMs);
                }
            }

            rowsEl.innerHTML = sensors.length
                ? sensors.map((sensor) => {
                    const key = String(sensor.id);
                    const currentValue = JSON.stringify([sensor.value, sensor.parameter_values, sensor.received_at]);
                    const changed = previousValues.has(key) && previousValues.get(key) !== currentValue;
                    previousValues.set(key, currentValue);

                    const statusLower = String(sensor.status || '').toLowerCase();
                    const badge = sensor.fresh
                        ? (statusLower.includes('awas') ? 'bg-danger' : 'bg-success')
                        : (sensor.online ? 'bg-info' : 'bg-secondary');
                    const valueClass = sensor.fresh ? (changed ? 'text-primary fw-bold' : 'fw-bold') : 'text-muted fw-bold';

                    return '<tr>' +
                        '<td><div class="fw-bold">' + escapeHtml(sensor.logger_code || '-') + '</div><small class="text-muted">' + escapeHtml(sensor.station || '-') + '</small></td>' +
                        '<td><div class="fw-bold">' + escapeHtml(sensor.sensor_code || '-') + '</div><small class="text-muted">' + escapeHtml(sensor.sensor_label || sensor.sensor_type || '-') + '</small></td>' +
                        '<td><span class="' + valueClass + '">' + escapeHtml(sensor.value ?? '-') + '</span></td>' +
                        '<td>' + renderParameterValues(sensor.parameter_values) + '</td>' +
                        '<td>' + escapeHtml(formatTime(sensor.received_at)) + '</td>' +
                        '<td><span class="badge ' + badge + '">' + escapeHtml(sensor.status || '-') + '</span></td>' +
                    '</tr>';
                }).join('')
                : '<tr><td colspan="6" class="text-center text-muted py-3">Belum ada sensor pada project ini.</td></tr>';

            renderAuditRows(data.mapping_audit || []);
            renderInfrastructure(data.infrastructure || []);
        }

        async function loadLiveData() {
            if (!select || !select.value) {
                return;
            }

            if (liveRequestInFlight) {
                return;
            }

            liveRequestInFlight = true;
            const requestSeq = ++liveRequestSeq;
            const selectedProjectId = select.value;
            const url = new URL(liveUrl, window.location.origin);
            url.searchParams.set('project_id', selectedProjectId);
            url.searchParams.set('_', Date.now());

            try {
                const response = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'Cache-Control': 'no-store' },
                    cache: 'no-store',
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    if (requestSeq !== liveRequestSeq || select.value !== selectedProjectId) {
                        return;
                    }

                    throw new Error(data.message || 'Live monitoring gagal dibaca.');
                }

                if (requestSeq === liveRequestSeq && select.value === selectedProjectId) {
                    renderLiveData(data);
                    if (messageEl && messageEl.classList.contains('alert-warning')) {
                        showMonitorMessage('', 'info');
                    }
                }
            } catch (error) {
                if (requestSeq === liveRequestSeq && select.value === selectedProjectId) {
                    throw error;
                }
            } finally {
                liveRequestInFlight = false;
            }
        }

        let pollIntervalMs = 2000;

        function startPolling() {
            clearInterval(timer);
            loadLiveData().catch((error) => showMonitorMessage(error.message, 'warning'));
            timer = setInterval(() => {
                loadLiveData().catch((error) => showMonitorMessage(error.message, 'warning'));
            }, pollIntervalMs);
        }

        function stopPolling() {
            clearInterval(timer);
            timer = null;
        }

        viewButtons.forEach((button) => {
            button.addEventListener('click', () => setMonitorView(button.dataset.monitorView));
        });

        if (auditFilterEl) {
            auditFilterEl.addEventListener('change', () => renderAuditRows(lastAuditRows));
        }

        if (select) {
            select.addEventListener('change', () => {
                previousValues.clear();
                startPolling();
            });
            startPolling();
        }
    })();
</script>
@endsection
