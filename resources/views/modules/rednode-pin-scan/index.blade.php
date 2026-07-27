@extends('layouts.master')

@section('title') RedNode Pin Scan @endsection

@section('css')
<style>
    .pin-scan-panel {
        border: 1px solid #eff2f7;
        border-radius: .25rem;
        background: #fff;
    }

    .pin-scan-title {
        color: #343a40;
        font-weight: 700;
    }

    .pin-scan-label {
        color: #495057;
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .pin-scan-accent {
        color: #34c38f;
    }

    .pin-scan-btn {
        background: #34c38f;
        border-color: #34c38f;
        color: #fff;
        font-weight: 800;
    }

    .pin-scan-outline {
        border-color: #34c38f;
        color: #34c38f;
        font-weight: 700;
    }

    .pin-scan-outline:hover {
        background: #34c38f;
        color: #fff;
    }

    .pin-scan-log {
        background: #212529;
        border-radius: .25rem;
        color: #d8dee9;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-size: 12px;
        max-height: 320px;
        overflow: auto;
        white-space: pre-wrap;
    }

    .pin-scan-port-option {
        border: 1px solid #eff2f7;
        border-radius: .25rem;
        padding: 10px 12px;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Device Setup @endslot
@slot('title') RedNode Pin Scan @endslot
@endcomponent

@php
    $dataLoggers = collect($dataLoggers ?? []);
    $connectivity = collect($connectivity ?? []);
    $rednodeLoggerCode = env('REDNODE_LOGGER_CODE', 'REDNODE-BLIIOT-01');
    $rednodeSerialConfig = $connectivity
        ->first(fn ($item) => ($item['logger_id'] ?? null) === $rednodeLoggerCode && (($item['protocol'] ?? null) === 'Modbus RTU' || ! empty($item['serial_port'])))
        ?? $connectivity->first(fn ($item) => ($item['protocol'] ?? null) === 'Modbus RTU' || ! empty($item['serial_port']))
        ?? [];
    $rednodeLoggerCode = $rednodeSerialConfig['logger_id'] ?? $rednodeLoggerCode;
    $ttyOptions = [
        ['pins' => 'PIN 1-2', 'mapping' => 'Pin 1 = B, Pin 2 = A', 'port' => '/dev/ttyAS4'],
        ['pins' => 'PIN 3-4', 'mapping' => 'Pin 3 = B, Pin 4 = A', 'port' => '/dev/ttyAS5'],
        ['pins' => 'PIN 5-6', 'mapping' => 'Pin 5 = B, Pin 6 = A', 'port' => '/dev/ttyAS2'],
        ['pins' => 'PIN 7-8', 'mapping' => 'Pin 7 = B, Pin 8 = A', 'port' => '/dev/ttyAS3'],
    ];
@endphp

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="bx bx-search-alt-2 pin-scan-accent fs-4"></i>
                    <h4 class="card-title pin-scan-title mb-0">RedNode Pin Scan</h4>
                </div>

                <form id="rednode-pin-scan-form" action="{{ route('rednode-pin-scan.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label pin-scan-label">Data Logger</label>
                        <select class="form-select" id="pin-scan-logger-select">
                            <option value="{{ $rednodeLoggerCode }}">Pakai kode aktif</option>
                            @foreach ($dataLoggers->whereNotNull('id') as $logger)
                                <option value="{{ $logger['id'] }}" @selected(($logger['id'] ?? null) === $rednodeLoggerCode)>
                                    {{ $logger['id'] }} - {{ $logger['device_label'] ?? 'RedNode' }}{{ ! empty($logger['remote_host']) ? ' / ' . $logger['remote_host'] : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label pin-scan-label">Logger Code</label>
                        <input type="text" class="form-control" id="pin-scan-logger-code" value="{{ $rednodeLoggerCode }}" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label pin-scan-label">Slave Awal</label>
                            <input type="number" class="form-control" id="pin-scan-start-slave" value="1" min="1" max="247" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label pin-scan-label">Slave Akhir</label>
                            <input type="number" class="form-control" id="pin-scan-end-slave" value="10" min="1" max="247" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label pin-scan-label">Baudrate</label>
                            <input type="number" class="form-control" id="pin-scan-baud-rate" value="{{ $rednodeSerialConfig['baud_rate'] ?? env('REDNODE_BAUD_RATE', 9600) }}" min="300" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label pin-scan-label">Timeout RX</label>
                            <input type="number" class="form-control" id="pin-scan-response-timeout" value="{{ env('REDNODE_SCAN_RESPONSE_TIMEOUT_MS', 300) }}" min="100" max="5000" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label pin-scan-label">Delay Slave</label>
                        <input type="number" class="form-control" id="pin-scan-delay" value="{{ env('REDNODE_SCAN_DELAY_MS', 80) }}" min="0" max="5000" required>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="pin-scan-stop-gateway">
                        <label class="form-check-label fw-bold text-muted" for="pin-scan-stop-gateway">Hentikan gateway sementara saat scan</label>
                        <div class="form-text">Aktifkan hanya kalau port serial sedang dipakai gateway. Setelah scan, gateway akan dicoba start lagi otomatis.</div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label pin-scan-label mb-0">Port / Pin</label>
                            <button type="button" class="btn btn-sm pin-scan-outline" id="pin-scan-toggle-ports">Select All</button>
                        </div>
                        <div class="d-grid gap-2">
                            @foreach ($ttyOptions as $option)
                                <label class="pin-scan-port-option d-flex align-items-start gap-2 mb-0" for="pin-scan-port-{{ $loop->index }}">
                                    <input
                                        class="form-check-input mt-1"
                                        type="checkbox"
                                        id="pin-scan-port-{{ $loop->index }}"
                                        value="{{ $option['port'] }}"
                                        data-pin-scan-port
                                        checked
                                    >
                                    <span>
                                        <span class="fw-bold d-block">{{ $option['pins'] }} - {{ $option['port'] }}</span>
                                        <span class="text-muted small">{{ $option['mapping'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="btn pin-scan-btn w-100" id="pin-scan-run">
                        <i class="bx bx-play me-1"></i> Run Scan
                    </button>
                </form>

                <div class="alert alert-warning mt-3 mb-0 d-none" id="pin-scan-message"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">Port</p>
                        <h4 class="mb-0" id="pin-scan-total-ports">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">TX</p>
                        <h4 class="mb-0" id="pin-scan-total-tx">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <p class="text-muted mb-1">RX Valid</p>
                        <h4 class="mb-0" id="pin-scan-total-rx">0</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h4 class="card-title pin-scan-title mb-0">Scan Result</h4>
                    <button type="button" class="btn btn-sm pin-scan-outline" id="pin-scan-clear">
                        <i class="bx bx-trash me-1"></i> Clear
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Port / Pin</th>
                                <th>Slave</th>
                                <th>RX</th>
                                <th>Register</th>
                                <th style="width:120px;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="pin-scan-result-rows">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada hasil scan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title pin-scan-title mb-3">Script Log</h4>
                <pre class="pin-scan-log p-3 mb-0" id="pin-scan-log">-</pre>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        const form = document.getElementById('rednode-pin-scan-form');
        if (!form) {
            return;
        }

        const csrfToken = @json(csrf_token());
        const el = (id) => document.getElementById(id);
        const portInputs = Array.from(document.querySelectorAll('[data-pin-scan-port]'));

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function selectedPorts() {
            return portInputs
                .filter((input) => input.checked)
                .map((input) => input.value);
        }

        function setMessage(message, type) {
            const box = el('pin-scan-message');
            box.className = 'alert mt-3 mb-0 alert-' + (type || 'warning');
            box.textContent = message || '';
            box.classList.toggle('d-none', !message);
        }

        function setTerminalLog(lines) {
            const log = el('pin-scan-log');
            const value = Array.isArray(lines) ? lines.join("\n") : String(lines || '-');

            log.textContent = value.trim() ? value : '-';
            log.scrollTop = log.scrollHeight;
        }

        function initialTerminalLog(ports) {
            return [
                '$ run rednode pin scan',
                '[web] Logger: ' + (el('pin-scan-logger-code').value.trim() || '-'),
                '[web] Slave: ' + el('pin-scan-start-slave').value + '-' + el('pin-scan-end-slave').value,
                '[web] Baudrate: ' + el('pin-scan-baud-rate').value,
                '[web] Port: ' + ports.join(', '),
                '[web] Mengirim perintah ke server aplikasi...',
                '[web] Server akan login SSH, masuk folder gateway, cek node, lalu menjalankan test-pin-led.js.',
            ];
        }

        function badgeFor(status) {
            const value = String(status || '').toLowerCase();

            if (value === 'valid') {
                return 'bg-success';
            }

            if (value === 'no-response') {
                return 'bg-secondary';
            }

            if (value.includes('crc') || value.includes('exception')) {
                return 'bg-warning';
            }

            return 'bg-danger';
        }

        function renderEmpty() {
            el('pin-scan-total-ports').textContent = '0';
            el('pin-scan-total-tx').textContent = '0';
            el('pin-scan-total-rx').textContent = '0';
            el('pin-scan-result-rows').innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada hasil scan.</td></tr>';
            setTerminalLog('-');
        }

        function renderResult(result, terminalLog) {
            const ports = Array.isArray(result?.ports) ? result.ports : [];
            let txTotal = 0;
            let rxValid = 0;
            const rows = [];

            ports.forEach((port) => {
                txTotal += Number(port.tx_total || 0);

                if (!port.ok) {
                    rows.push({
                        port: [port.pins, port.port].filter(Boolean).join(' | '),
                        mapping: port.mapping || '',
                        slave: '-',
                        rx: port.error || '-',
                        registers: '-',
                        status: 'port-error',
                    });
                    return;
                }

                const slaves = Array.isArray(port.slaves) ? port.slaves : [];

                if (!slaves.length) {
                    rows.push({
                        port: [port.pins, port.port].filter(Boolean).join(' | '),
                        mapping: port.mapping || '',
                        slave: '-',
                        rx: '-',
                        registers: '-',
                        status: 'no-response',
                    });
                    return;
                }

                slaves.forEach((slave) => {
                    if (slave.status === 'valid') {
                        rxValid += 1;
                    }

                    rows.push({
                        port: [port.pins, port.port].filter(Boolean).join(' | '),
                        mapping: port.mapping || '',
                        slave: slave.slave_id || '-',
                        rx: slave.raw || slave.rx || slave.error || '-',
                        registers: Array.isArray(slave.registers) && slave.registers.length ? slave.registers.join(', ') : '-',
                        status: slave.status || '-',
                    });
                });
            });

            el('pin-scan-total-ports').textContent = String(ports.length);
            el('pin-scan-total-tx').textContent = String(txTotal);
            el('pin-scan-total-rx').textContent = String(rxValid);
            el('pin-scan-result-rows').innerHTML = rows.length
                ? rows.map((row) => '<tr>' +
                    '<td><div class="fw-bold">' + escapeHtml(row.port) + '</div><small class="text-muted">' + escapeHtml(row.mapping) + '</small></td>' +
                    '<td>' + escapeHtml(row.slave) + '</td>' +
                    '<td><code>' + escapeHtml(row.rx) + '</code></td>' +
                    '<td>' + escapeHtml(row.registers) + '</td>' +
                    '<td><span class="badge ' + badgeFor(row.status) + '">' + escapeHtml(row.status) + '</span></td>' +
                '</tr>').join('')
                : '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada hasil scan.</td></tr>';
            setTerminalLog(Array.isArray(terminalLog) && terminalLog.length ? terminalLog : result.logs);
        }

        async function runScan(event) {
            event.preventDefault();

            const ports = selectedPorts();

            if (!ports.length) {
                setMessage('Pilih minimal satu port untuk discan.', 'warning');
                return;
            }

            const button = el('pin-scan-run');
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Scanning...';
            setTerminalLog(initialTerminalLog(ports));
            setMessage(el('pin-scan-stop-gateway').checked
                ? 'Scan sedang berjalan. Gateway dihentikan sementara dan akan dicoba start lagi setelah scan.'
                : 'Scan sedang berjalan tanpa menghentikan gateway. Kalau port sedang sibuk, centang opsi hentikan gateway sementara.',
                'info');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        logger_code: el('pin-scan-logger-code').value.trim(),
                        start_slave_id: Number(el('pin-scan-start-slave').value),
                        end_slave_id: Number(el('pin-scan-end-slave').value),
                        baud_rate: Number(el('pin-scan-baud-rate').value),
                        response_timeout_ms: Number(el('pin-scan-response-timeout').value),
                        delay_between_slaves_ms: Number(el('pin-scan-delay').value),
                        stop_gateway: el('pin-scan-stop-gateway').checked,
                        ports: ports,
                    }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                    setTerminalLog(data.terminal_log || data.output || data.result?.logs || data.message || 'Scan pin RedNode gagal.');
                    throw new Error(errors || data.message || 'Scan pin RedNode gagal.');
                }

                renderResult(data.result || {}, data.terminal_log);
                setMessage(data.gateway_restarted
                    ? data.message + ' Gateway sudah dicoba start lagi otomatis.'
                    : data.message,
                    'success');
            } catch (error) {
                setMessage(error.message, 'warning');
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }

        el('pin-scan-logger-select').addEventListener('change', (event) => {
            el('pin-scan-logger-code').value = event.target.value;
        });
        el('pin-scan-toggle-ports').addEventListener('click', () => {
            const allChecked = portInputs.every((input) => input.checked);
            portInputs.forEach((input) => {
                input.checked = !allChecked;
            });
        });
        el('pin-scan-clear').addEventListener('click', () => {
            renderEmpty();
            setMessage('');
        });
        form.addEventListener('submit', runScan);
    })();
</script>
@endsection
