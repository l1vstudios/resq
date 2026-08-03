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
    $projectMonitoringStartUrl = route('projects.start-monitoring');
    $projectMonitoringStopUrl = route('projects.stop-monitoring');
    $projectMonitoringLiveUrl = route('projects.live-monitoring');
@endphp

@unless ($databaseReady)
    <div class="alert alert-warning">
        Database setup belum dimigrate. Jalankan <code>php artisan migrate</code>.
    </div>
@endunless

<div class="row" id="project-monitoring-runtime" data-start-url="{{ $projectMonitoringStartUrl }}" data-stop-url="{{ $projectMonitoringStopUrl }}" data-live-url="{{ $projectMonitoringLiveUrl }}">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
                    <div>
                        <h4 class="card-title mb-1">Project Live Monitoring</h4>
                        <p class="text-muted mb-0">Mulai koneksi logger project dan pantau nilai sensor terbaru.</p>
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
                        <button type="button" class="btn btn-success" id="project-monitor-start" @disabled(! $databaseReady || $projects->whereNotNull('db_id')->isEmpty())>
                            <i class="bx bx-play me-1"></i> Start Monitoring
                        </button>
                        <button type="button" class="btn btn-danger" id="project-monitor-stop" @disabled(! $databaseReady || $projects->whereNotNull('db_id')->isEmpty())>
                            <i class="bx bx-stop me-1"></i> Stop Monitoring
                        </button>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Logger Online</div><div class="fs-5 fw-bold text-success" id="project-monitor-online">0 / 0</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Sensor Realtime</div><div class="fs-5 fw-bold text-primary" id="project-monitor-fresh">0 / 0</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Refresh</div><div class="fs-5 fw-bold" id="project-monitor-refresh">-</div></div></div>
                    <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small fw-bold">Status</div><div class="fs-5 fw-bold" id="project-monitor-state">Idle</div></div></div>
                </div>

                <div class="alert alert-info py-2 d-none" id="project-monitor-message"></div>

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
        const startUrl = root.dataset.startUrl;
        const stopUrl = root.dataset.stopUrl;
        const liveUrl = root.dataset.liveUrl;
        const select = document.getElementById('project-monitor-select');
        const startButton = document.getElementById('project-monitor-start');
        const stopButton = document.getElementById('project-monitor-stop');
        const rowsEl = document.getElementById('project-monitor-rows');
        const logRowsEl = document.getElementById('project-monitor-log-rows');
        const messageEl = document.getElementById('project-monitor-message');
        const onlineEl = document.getElementById('project-monitor-online');
        const freshEl = document.getElementById('project-monitor-fresh');
        const refreshEl = document.getElementById('project-monitor-refresh');
        const stateEl = document.getElementById('project-monitor-state');
        const logs = [];
        const previousValues = new Map();
        let timer = null;
        let busy = false;

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

        function renderLiveData(data) {
            const summary = data.summary || {};
            const sensors = Array.isArray(data.sensors) ? data.sensors : [];

            onlineEl.textContent = (summary.online_loggers || 0) + ' / ' + (summary.loggers || 0);
            freshEl.textContent = (summary.fresh_sensors || 0) + ' / ' + (summary.sensors || 0);
            refreshEl.textContent = formatTime(data.generated_at);
            stateEl.textContent = (summary.online_loggers || 0) > 0 ? 'Running' : 'Menunggu';
            stateEl.className = 'fs-5 fw-bold ' + ((summary.online_loggers || 0) > 0 ? 'text-success' : 'text-muted');
            updateMonitorButtons();

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
        }

        async function loadLiveData() {
            if (!select || !select.value) {
                return;
            }

            const url = new URL(liveUrl, window.location.origin);
            url.searchParams.set('project_id', select.value);
            url.searchParams.set('_', Date.now());
            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'Cache-Control': 'no-store' },
                cache: 'no-store',
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.ok === false) {
                throw new Error(data.message || 'Live monitoring gagal dibaca.');
            }

            renderLiveData(data);
        }

        function startPolling() {
            clearInterval(timer);
            loadLiveData().catch((error) => showMonitorMessage(error.message, 'warning'));
            timer = setInterval(() => {
                loadLiveData().catch((error) => showMonitorMessage(error.message, 'warning'));
            }, 2000);
        }

        function stopPolling() {
            clearInterval(timer);
            timer = null;
        }

        function updateMonitorButtons() {
            if (startButton) {
                startButton.disabled = busy || !select || !select.value;
            }

            if (stopButton) {
                stopButton.disabled = busy || !select || !select.value;
            }
        }

        async function submitMonitoringAction(action) {
            if (!select || !select.value) {
                showMonitorMessage('Pilih project dulu.', 'warning');
                return;
            }

            const isStart = action === 'start';
            const button = isStart ? startButton : stopButton;
            const originalText = button.innerHTML;
            busy = true;
            updateMonitorButtons();
            button.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> ' + (isStart ? 'Starting...' : 'Stopping...');
            showMonitorMessage(isStart ? 'Menghubungkan semua logger pada project...' : 'Menghentikan monitoring semua logger pada project...', 'info');

            try {
                const response = await fetch(isStart ? startUrl : stopUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ project_id: select.value }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    (data.loggers || []).forEach((logger) => {
                        appendMonitorLogLines(logger);
                    });
                    throw new Error(data.message || (isStart ? 'Start monitoring gagal.' : 'Stop monitoring gagal.'));
                }

                (data.loggers || []).forEach((logger) => {
                    appendMonitorLogLines(logger);
                });
                showMonitorMessage(data.message + (isStart ? ' Live data akan refresh otomatis.' : ' Monitoring sudah berhenti.'), 'success');

                if (isStart) {
                    startPolling();
                } else {
                    stopPolling();
                    await loadLiveData().catch(() => {});
                    stateEl.textContent = 'Stopped';
                    stateEl.className = 'fs-5 fw-bold text-danger';
                }
            } catch (error) {
                appendMonitorLog('Project', error.message, 'Gagal');
                showMonitorMessage(error.message, 'warning');
            } finally {
                busy = false;
                button.innerHTML = originalText;
                updateMonitorButtons();
            }
        }

        if (startButton) {
            startButton.addEventListener('click', () => submitMonitoringAction('start'));
        }

        if (stopButton) {
            stopButton.addEventListener('click', () => submitMonitoringAction('stop'));
        }

        if (select) {
            select.addEventListener('change', () => {
                previousValues.clear();
                startPolling();
            });
            updateMonitorButtons();
            startPolling();
        }
    })();
</script>
@endsection
