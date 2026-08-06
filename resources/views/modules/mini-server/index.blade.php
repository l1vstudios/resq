@extends('layouts.master')

@section('title') Mini Server @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Device Setup @endslot
@slot('title') Mini Server @endslot
@endcomponent

@php
    $interfaces = collect($interfaces ?? []);
    $dataLoggers = collect($dataLoggers ?? []);
@endphp

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">LAN Scanner</h4>
                <form id="mini-server-scan-form">
                    <div class="mb-3">
                        <label class="form-label">Interface LAN</label>
                        <select class="form-select" id="mini-interface" name="interface">
                            @forelse ($interfaces as $interface)
                                <option value="{{ $interface['name'] }}" data-cidr="{{ $interface['cidr'] }}">
                                    {{ $interface['label'] }} | {{ $interface['cidr'] }}
                                </option>
                            @empty
                                <option value="">Interface IPv4 tidak ditemukan</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subnet Scan</label>
                        <input class="form-control" id="mini-cidr" name="cidr" value="{{ $interfaces->first()['cidr'] ?? '' }}" placeholder="192.168.3.0/24">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Timeout Ping</label>
                        <select class="form-select" id="mini-timeout" name="timeout_ms">
                            <option value="500">500 ms</option>
                            <option value="800" selected>800 ms</option>
                            <option value="1200">1200 ms</option>
                            <option value="2000">2000 ms</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" id="mini-scan-button" @disabled($interfaces->isEmpty())>
                        <i class="bx bx-search-alt-2 me-1"></i> Scan LAN
                    </button>
                    <a href="{{ route('data-loggers.index') }}" class="btn btn-outline-secondary ms-1">
                        <i class="bx bx-data me-1"></i> Data Loggers
                    </a>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Data Logger Hosts</h4>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Logger</th>
                                <th>Host</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dataLoggers as $logger)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $logger['logger_code'] }}</div>
                                        <small class="text-muted">{{ $logger['device_label'] ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $logger['remote_host'] ?: '-' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-muted text-center">Belum ada Data Logger.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h4 class="card-title mb-0">Detected IP Addresses</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark" id="mini-cidr-badge">Subnet: -</span>
                        <span class="badge bg-light text-dark" id="mini-count-badge">Active: 0</span>
                        <span class="badge bg-light text-dark" id="mini-duration-badge">Duration: -</span>
                    </div>
                </div>

                <div class="alert alert-info d-none" id="mini-message"></div>

                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>IP Address</th>
                                <th>MAC</th>
                                <th>Hostname</th>
                                <th>Data Logger Match</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="mini-hosts">
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada hasil scan.</td>
                            </tr>
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
        const form = document.getElementById('mini-server-scan-form');
        const interfaceSelect = document.getElementById('mini-interface');
        const cidrInput = document.getElementById('mini-cidr');
        const timeoutInput = document.getElementById('mini-timeout');
        const scanButton = document.getElementById('mini-scan-button');
        const message = document.getElementById('mini-message');
        const hostsBody = document.getElementById('mini-hosts');
        const cidrBadge = document.getElementById('mini-cidr-badge');
        const countBadge = document.getElementById('mini-count-badge');
        const durationBadge = document.getElementById('mini-duration-badge');

        if (!form) {
            return;
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character]));
        }

        function setMessage(text, type) {
            message.className = 'alert alert-' + (type || 'info');
            message.textContent = text || '';
            message.classList.toggle('d-none', !text);
        }

        function setLoading(loading) {
            scanButton.disabled = loading;
            scanButton.innerHTML = loading
                ? '<i class="bx bx-loader-alt bx-spin me-1"></i> Scanning...'
                : '<i class="bx bx-search-alt-2 me-1"></i> Scan LAN';
        }

        function renderHosts(hosts) {
            if (!hosts.length) {
                hostsBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada host aktif terdeteksi.</td></tr>';
                return;
            }

            hostsBody.innerHTML = hosts.map((host) => {
                const matches = (host.logger_matches || []).map((match) => (
                    '<span class="badge bg-success me-1">' + escapeHtml(match.logger_code) + '</span>' +
                    '<small class="text-muted">' + escapeHtml(match.source) + '</small>'
                )).join('<br>');

                return '<tr>' +
                    '<td class="fw-bold">' + escapeHtml(host.ip) + '</td>' +
                    '<td>' + escapeHtml(host.mac || '-') + '</td>' +
                    '<td>' + escapeHtml(host.hostname || '-') + '</td>' +
                    '<td>' + (matches || '<span class="text-muted">-</span>') + '</td>' +
                    '<td class="text-end"><button type="button" class="btn btn-outline-secondary btn-sm" data-copy-ip="' + escapeHtml(host.ip) + '"><i class="bx bx-copy me-1"></i>Copy</button></td>' +
                    '</tr>';
            }).join('');
        }

        interfaceSelect?.addEventListener('change', () => {
            const selected = interfaceSelect.options[interfaceSelect.selectedIndex];
            if (selected?.dataset.cidr) {
                cidrInput.value = selected.dataset.cidr;
            }
        });

        hostsBody.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-copy-ip]');
            if (!button) {
                return;
            }

            await navigator.clipboard?.writeText(button.dataset.copyIp);
            setMessage('IP ' + button.dataset.copyIp + ' disalin.', 'success');
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setLoading(true);
            setMessage('Scan LAN sedang berjalan.', 'info');

            try {
                const response = await fetch(@json(route('mini-server.scan')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify({
                        interface: interfaceSelect?.value || '',
                        cidr: cidrInput.value.trim(),
                        timeout_ms: Number(timeoutInput.value || 800),
                    }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok || data.ok === false) {
                    throw new Error(data.message || 'Scan LAN gagal.');
                }

                cidrBadge.textContent = 'Subnet: ' + data.cidr;
                countBadge.textContent = 'Active: ' + data.active_count + '/' + data.host_count;
                durationBadge.textContent = 'Duration: ' + data.duration_ms + ' ms';
                renderHosts(data.hosts || []);
                setMessage('Scan selesai. Host aktif: ' + data.active_count + '.', 'success');
            } catch (error) {
                setMessage(error.message, 'warning');
            } finally {
                setLoading(false);
            }
        });
    })();
</script>
@endsection
