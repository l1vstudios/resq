@extends('layouts.master')

@section('title') Detail Asset @endsection

@section('css')
<style>
    .asset-live .card {
        border: 1px solid #d9e4f2;
        border-radius: 8px;
        box-shadow: none;
    }

    .asset-live-header {
        align-items: flex-start;
        display: flex;
        gap: 16px;
        justify-content: space-between;
    }

    .asset-live-title {
        color: #102653;
        font-size: 23px;
        font-weight: 800;
        margin: 0;
    }

    .asset-live-meta {
        color: #74788d;
        font-size: 13px;
        margin-top: 6px;
    }

    .asset-live-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .asset-live-status {
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        padding: 6px 10px;
    }

    .asset-live-status.ok { background: #e9f7ef; color: #157347; }
    .asset-live-status.fail { background: #fdecec; color: #b42318; }
    .asset-live-status.idle { background: #eef4fb; color: #1d4d7d; }

    .asset-value-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .asset-value-card {
        background: #fff;
        border: 1px solid #d9e4f2;
        border-radius: 8px;
        min-height: 92px;
        padding: 14px;
    }

    .asset-value-card span {
        color: #74788d;
        display: block;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .03em;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .asset-value-card strong {
        color: #102653;
        display: block;
        font-size: 24px;
        font-weight: 900;
        line-height: 1.15;
        overflow-wrap: anywhere;
    }

    .asset-live-log {
        background: #f8fbfe;
        border: 1px solid #d9e4f2;
        border-radius: 8px;
        color: #495057;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-size: 12px;
        margin: 0;
        min-height: 88px;
        padding: 12px;
        white-space: pre-wrap;
    }

    @media (max-width: 1199px) {
        .asset-value-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 575px) {
        .asset-live-header { display: block; }
        .asset-live-actions { justify-content: flex-start; margin-top: 12px; }
        .asset-value-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Manajemen Assets @endslot
@slot('title') Detail Asset @endslot
@endcomponent

@php
    $payload = $asset->last_scan_payload ?? [];
    $values = $payload['values'] ?? [];
    $statusClass = $asset->last_scan_status === 'success' ? 'ok' : ($asset->last_scan_status === 'failed' ? 'fail' : 'idle');
@endphp

<div class="asset-live" id="asset-live-root" data-scan-url="{{ $scanUrl }}">
    <div class="card mb-3">
        <div class="card-body">
            <div class="asset-live-header">
                <div>
                    <h1 class="asset-live-title">{{ $asset->asset_code }} - {{ $asset->name }}</h1>
                    <div class="asset-live-meta">
                        {{ $asset->project?->project_code ?? '-' }}
                        / {{ $asset->monitoringStation?->station_code ?? 'Station belum dipilih' }}
                        / {{ $asset->serial_port ?: '-' }}
                        / Slave {{ $asset->slave_address }}
                        / {{ $asset->baud_rate }} bps
                    </div>
                </div>
                <div class="asset-live-actions">
                    <span class="asset-live-status {{ $statusClass }}" id="asset-live-status">{{ $asset->last_scan_status ?: 'belum discan' }}</span>
                    <button type="button" class="btn btn-primary" id="asset-scan-now">
                        <i class="bx bx-search-alt-2 me-1"></i> Scan Sekarang
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="asset-auto-toggle" data-active="1">
                        <i class="bx bx-pause me-1"></i> Auto
                    </button>
                    <a href="{{ route('asset-management.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="asset-value-grid mb-3">
        <div class="asset-value-card"><span>PV (Solar Panel) Voltage</span><strong data-value-key="pv_voltage" data-unit="V">{{ data_get($values, 'pv_voltage', '-') }} V</strong></div>
        <div class="asset-value-card"><span>PV (Solar Panel) Current</span><strong data-value-key="pv_current" data-unit="A">{{ data_get($values, 'pv_current', '-') }} A</strong></div>
        <div class="asset-value-card"><span>PV (Solar Panel) Power</span><strong data-value-key="pv_power" data-unit="W">{{ data_get($values, 'pv_power', '-') }} W</strong></div>
        <div class="asset-value-card"><span>Battery Voltage</span><strong data-value-key="battery_voltage" data-unit="V">{{ data_get($values, 'battery_voltage', '-') }} V</strong></div>
        <div class="asset-value-card"><span>Charge Voltage</span><strong data-value-key="charge_voltage" data-unit="V">{{ data_get($values, 'charge_voltage', '-') }} V</strong></div>
        <div class="asset-value-card"><span>Charge Current</span><strong data-value-key="charge_current" data-unit="A">{{ data_get($values, 'charge_current', '-') }} A</strong></div>
        <div class="asset-value-card"><span>Charge Power</span><strong data-value-key="charge_power" data-unit="W">{{ data_get($values, 'charge_power', '-') }} W</strong></div>
        <div class="asset-value-card"><span>Load Voltage</span><strong data-value-key="load_voltage" data-unit="V">{{ data_get($values, 'load_voltage', '-') }} V</strong></div>
        <div class="asset-value-card"><span>Load Current</span><strong data-value-key="load_current" data-unit="A">{{ data_get($values, 'load_current', '-') }} A</strong></div>
        <div class="asset-value-card"><span>Load Power</span><strong data-value-key="load_power" data-unit="W">{{ data_get($values, 'load_power', '-') }} W</strong></div>
        <div class="asset-value-card"><span>Battery SOC</span><strong data-value-key="battery_soc" data-unit="%">{{ data_get($values, 'battery_soc', '-') }} %</strong></div>
        <div class="asset-value-card"><span>Controller Temp</span><strong data-value-key="controller_temperature" data-unit="C">{{ data_get($values, 'controller_temperature', '-') }} C</strong></div>
    </div>

    <div class="row">
        <div class="col-xl-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title mb-3">Runtime</h4>
                    <div class="mb-2">
                        <div class="text-muted small">Last scan</div>
                        <div class="fw-semibold" id="asset-last-scan">{{ optional($asset->last_scanned_at)->format('Y-m-d H:i:s') ?: '-' }}</div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">Scanner</div>
                        <div class="fw-semibold" id="asset-scanner">{{ $payload['scanner'] ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Message</div>
                        <div class="fw-semibold" id="asset-message">{{ $asset->last_scan_message ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="card-title mb-3">Scan Log</h4>
                    <pre class="asset-live-log" id="asset-live-log">Menunggu scan...</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    (function () {
        const root = document.getElementById('asset-live-root');
        if (!root) return;

        const scanUrl = root.dataset.scanUrl;
        const csrf = @json(csrf_token());
        const statusEl = document.getElementById('asset-live-status');
        const messageEl = document.getElementById('asset-message');
        const lastScanEl = document.getElementById('asset-last-scan');
        const scannerEl = document.getElementById('asset-scanner');
        const logEl = document.getElementById('asset-live-log');
        const scanButton = document.getElementById('asset-scan-now');
        const autoButton = document.getElementById('asset-auto-toggle');
        let timer = null;
        let inFlight = false;

        function formatNumber(value) {
            if (value === null || value === undefined || value === '') return '-';
            const numeric = Number(value);
            if (!Number.isFinite(numeric)) return String(value);
            return numeric.toFixed(2).replace(/\.?0+$/, '');
        }

        function formatTime(value) {
            if (!value) return '-';
            return new Date(value).toLocaleString();
        }

        function setStatus(status) {
            const value = status || 'belum discan';
            statusEl.textContent = value;
            statusEl.classList.toggle('ok', value === 'success');
            statusEl.classList.toggle('fail', value === 'failed');
            statusEl.classList.toggle('idle', value !== 'success' && value !== 'failed');
        }

        function renderValues(values) {
            document.querySelectorAll('[data-value-key]').forEach((el) => {
                const key = el.dataset.valueKey;
                const unit = el.dataset.unit || '';
                const value = values && Object.prototype.hasOwnProperty.call(values, key) ? values[key] : null;
                el.textContent = formatNumber(value) + (unit ? ' ' + unit : '');
            });
        }

        function appendLog(message) {
            const line = '[' + new Date().toLocaleTimeString() + '] ' + message;
            const current = logEl.textContent === 'Menunggu scan...' ? '' : logEl.textContent;
            logEl.textContent = [line, current].filter(Boolean).join('\n').split('\n').slice(0, 12).join('\n');
        }

        async function scanAsset() {
            if (inFlight) return;
            inFlight = true;
            scanButton.disabled = true;
            appendLog('Scan dimulai...');

            try {
                const response = await fetch(scanUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });
                const data = await response.json();
                const asset = data.asset || {};
                const payload = data.payload || asset.last_scan_payload || {};
                const values = payload.values || {};

                setStatus(asset.last_scan_status || (data.ok ? 'success' : 'failed'));
                renderValues(values);
                messageEl.textContent = data.message || asset.last_scan_message || '-';
                lastScanEl.textContent = formatTime(asset.last_scanned_at);
                scannerEl.textContent = payload.scanner || '-';
                appendLog(data.message || (data.ok ? 'Scan berhasil.' : 'Scan gagal.'));
            } catch (error) {
                setStatus('failed');
                messageEl.textContent = error.message || 'Scan gagal.';
                appendLog(error.message || 'Scan gagal.');
            } finally {
                inFlight = false;
                scanButton.disabled = false;
            }
        }

        function setAuto(active) {
            autoButton.dataset.active = active ? '1' : '0';
            autoButton.innerHTML = active
                ? '<i class="bx bx-pause me-1"></i> Auto'
                : '<i class="bx bx-play me-1"></i> Auto';
            if (timer) clearInterval(timer);
            timer = active ? setInterval(scanAsset, 5000) : null;
        }

        scanButton.addEventListener('click', scanAsset);
        autoButton.addEventListener('click', () => setAuto(autoButton.dataset.active !== '1'));
        setAuto(true);
        scanAsset();
    })();
</script>
@endsection
