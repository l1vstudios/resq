(function (root) {
    'use strict';

    function escapeHtml(value) {
        return String(value == null ? '—' : value).replace(/[&<>"']/g, function (character) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
        });
    }

    function sensorState(sensor) {
        var states = {
            NORMAL: [0, 'Normal', 'normal'], SIAP: [0, 'Normal', 'normal'],
            WASPADA: [1, 'Waspada', 'waspada'], WARNING: [1, 'Waspada', 'waspada'],
            SIAGA: [2, 'Siaga', 'siaga'],
            AWAS: [3, 'Awas', 'awas'], BAHAYA: [3, 'Bahaya', 'awas'],
            DANGER: [3, 'Bahaya', 'awas'], CRITICAL: [3, 'Kritis', 'awas'],
            OFFLINE: [4, 'Offline', 'unknown'], UNKNOWN: [4, 'Belum diketahui', 'unknown']
        };
        var values = [sensor.status, sensor.alert_level].filter(Boolean);
        if (sensor.threshold_exceeded) values.push('AWAS');
        if (!values.length) return { label: 'Belum diketahui', tone: 'unknown' };
        var state = values.map(function (value) {
            return states[String(value).trim().toUpperCase()] || [-1, String(value), 'unknown'];
        }).sort(function (a, b) { return b[0] - a[0]; })[0];
        return { label: state[1], tone: state[2] };
    }

    function tdePresentation(tde) {
        var output = (tde && tde.output) || {};
        var diagnosis = output.diagnosis || {};
        var pattern = output.combination_pattern || {};
        var state = (tde && tde.execution_state) || 'insufficient_data';
        var ready = state === 'evaluated' && String(pattern.key || '').indexOf('?') === -1;
        var match = diagnosis.matrix_match;
        var forecast = match || diagnosis.fallback_interpretation || {};
        return {
            summary: state === 'error' ? 'Evaluasi TDE gagal' : (ready ? forecast.summary || 'Belum ada diagnosis' : 'Data TDE belum cukup'),
            basis: ready ? forecast.basis || '' : 'Menunggu pembacaan parameter AT, RH, DP, DPS, dan AP yang cukup.',
            condition: (output.condition_current || {}).state || 'Belum diketahui',
            pattern: pattern.key || '—',
            estimate: ready && match ? match.estimated_time || match.perkiraan_waktu || match.time_estimate || 'Belum tersedia' : 'Belum tersedia',
            label: state === 'error' ? 'Gagal' : (!ready ? 'Menunggu data' : (match ? 'Sesuai matriks' : 'Interpretasi awal')),
            tone: state === 'error' ? 'unknown' : (!ready ? 'waspada' : 'info'),
            missing: Array.isArray(diagnosis.missing_parameters) ? diagnosis.missing_parameters.join(', ') : '',
            window: (output.window || {}).minutes,
            matrixStatus: diagnosis.matrix_status || 'matrix_not_configured'
        };
    }

    function ProjectMapMonitoring(options) {
        this.options = options;
        this.elements = options.elements || {};
        if (!options.elements) {
            var panel = options.panel;
            ['sensor-rows', 'sensor-count', 'sensor-update', 'tde-results', 'tde-update', 'siren-toggle', 'siren-status', 'panel-toggle', 'panel-body'].forEach(function (name) {
                this.elements[name] = panel.querySelector('[data-monitor="' + name + '"]');
            }, this);
        }
        this.audio = options.audio || new Audio(options.sirenUrl);
        this.audio.loop = true;
        this.audio.preload = 'none';
        this.sirenRequested = false;
        this.sirenGeneration = 0;
        this.tdeInFlight = false;
        this.destroyed = false;
        this.elements['siren-toggle'].addEventListener('click', function () {
            this.setSiren(!this.sirenRequested);
        }.bind(this));
        this.elements['panel-toggle'].addEventListener('click', function () {
            var body = this.elements['panel-body'];
            body.hidden = !body.hidden;
            this.elements['panel-toggle'].setAttribute('aria-expanded', String(!body.hidden));
            this.elements['panel-toggle'].textContent = body.hidden ? 'Tampilkan' : 'Ciutkan';
        }.bind(this));
    }

    ProjectMapMonitoring.prototype.setSiren = function (enabled) {
        var generation = ++this.sirenGeneration;
        this.sirenRequested = enabled;
        var button = this.elements['siren-toggle'];
        button.setAttribute('aria-pressed', String(enabled));
        button.classList.toggle('siren-active', enabled);
        button.textContent = enabled ? 'Matikan sirene' : 'Aktifkan sirene';
        this.elements['siren-status'].textContent = enabled ? 'Menyalakan suara…' : 'Suara di perangkat ini nonaktif';
        if (!enabled) {
            this.audio.pause();
            this.audio.currentTime = 0;
            return Promise.resolve();
        }
        return Promise.resolve().then(function () {
            if (!this.sirenRequested || this.sirenGeneration !== generation) return;
            return this.audio.play();
        }.bind(this)).then(function () {
            if (!this.sirenRequested) {
                this.audio.pause();
                this.audio.currentTime = 0;
            } else if (this.sirenGeneration === generation) {
                this.elements['siren-status'].textContent = 'Sirene berbunyi di perangkat ini';
            }
        }.bind(this)).catch(function () {
            if (this.sirenGeneration !== generation) return;
            this.setSiren(false);
            this.elements['siren-status'].textContent = 'Suara gagal diputar. Klik untuk mencoba lagi.';
        }.bind(this));
    };

    ProjectMapMonitoring.prototype.updateSensors = function (data) {
        var sensors = Array.isArray(data.sensors) ? data.sensors : [];
        this.elements['sensor-count'].textContent = sensors.length + ' sensor';
        this.elements['sensor-rows'].innerHTML = sensors.length ? sensors.map(function (sensor) {
            var state = sensorState(sensor);
            var parameters = Array.isArray(sensor.parameter_values) ? sensor.parameter_values : [];
            var values = parameters.length ? parameters.map(function (parameter) {
                return '<div><span class="monitor-muted">' + escapeHtml(parameter.parameter || parameter.name) + ':</span> <strong>' + escapeHtml(parameter.value_text == null ? parameter.value : parameter.value_text) + '</strong></div>';
            }).join('') : '<strong class="monitor-value">' + escapeHtml(sensor.value) + '</strong>';
            return '<tr><td><strong>' + escapeHtml(sensor.name) + '</strong><small>' + escapeHtml(sensor.station || 'Tanpa station') + ' · ' + escapeHtml(sensor.parameter || sensor.type) + '</small></td>' +
                '<td>' + values + '<small>' + escapeHtml(sensor.last_seen || 'Belum ada waktu pembacaan') + '</small></td>' +
                '<td><span class="monitor-badge tone-' + state.tone + '">' + escapeHtml(state.label) + '</span></td></tr>';
        }).join('') : '<tr><td colspan="3" class="monitor-empty">Belum ada sensor pada project ini.</td></tr>';
        this.elements['sensor-update'].textContent = data.generated_at ? 'Diperbarui ' + this.formatTime(data.generated_at) : 'Menunggu pembaruan sensor';
    };

    ProjectMapMonitoring.prototype.formatTime = function (value) {
        var date = new Date(value);
        return Number.isNaN(date.getTime()) ? '—' : date.toLocaleTimeString('id-ID');
    };

    ProjectMapMonitoring.prototype.showSensorError = function () {
        this.elements['sensor-update'].textContent = 'Pembaruan sensor terputus · menampilkan data terakhir';
    };

    ProjectMapMonitoring.prototype.renderTde = function (data) {
        var stations = Array.isArray(data.stations) ? data.stations : [];
        this.elements['tde-results'].innerHTML = stations.length ? stations.map(function (station) {
            var result = tdePresentation(station.tde);
            return '<article class="monitor-tde-card"><div class="monitor-tde-heading"><strong>' + escapeHtml(station.station_code) + '</strong>' +
                '<span class="monitor-badge tone-' + result.tone + '">' + escapeHtml(result.label) + '</span></div>' +
                '<small class="monitor-muted">' + escapeHtml(station.name) + '</small>' +
                '<h3>' + escapeHtml(result.summary) + '</h3>' +
                '<p>' + escapeHtml(result.basis) + '</p>' +
                '<dl><div><dt>Kondisi</dt><dd>' + escapeHtml(result.condition) + '</dd></div><div><dt>Pola</dt><dd>' + escapeHtml(result.pattern) + '</dd></div>' +
                '<div><dt>Perkiraan waktu</dt><dd>' + escapeHtml(result.estimate) + '</dd></div></dl>' +
                (result.missing ? '<small class="monitor-muted">Parameter belum cukup: ' + escapeHtml(result.missing) + '</small>' : '') +
                (result.matrixStatus === 'matrix_not_configured' ? '<small class="monitor-muted">Matriks TDE belum dikonfigurasi.</small>' : '') +
                (result.window ? '<small class="monitor-muted">Analisis ' + escapeHtml(result.window) + ' menit terakhir.</small>' : '') + '</article>';
        }).join('') : '<p class="monitor-empty">Belum ada monitoring station untuk evaluasi TDE.</p>';
        this.elements['tde-update'].textContent = 'Diperbarui ' + this.formatTime(data.generated_at);
        if (this.options.onTdeData) this.options.onTdeData(stations, tdePresentation);
    };

    ProjectMapMonitoring.prototype.refreshTde = function () {
        if (this.tdeInFlight || this.destroyed) return Promise.resolve();
        this.tdeInFlight = true;
        return (this.options.fetch || root.fetch)(this.options.tdeUrl, {
            headers: { Accept: 'application/json' }, cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) throw new Error('TDE unavailable');
            return response.json();
        }).then(function (data) {
            if (!this.destroyed) this.renderTde(data);
        }.bind(this)).catch(function () {
            if (!this.destroyed) this.elements['tde-update'].textContent = 'Pembaruan TDE terputus · menunggu koneksi';
        }.bind(this)).finally(function () {
            this.tdeInFlight = false;
        }.bind(this));
    };

    ProjectMapMonitoring.prototype.start = function () {
        this.refreshTde();
        this.timer = root.setInterval(this.refreshTde.bind(this), 15000);
    };

    ProjectMapMonitoring.prototype.destroy = function () {
        this.destroyed = true;
        root.clearInterval(this.timer);
        this.setSiren(false);
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { ProjectMapMonitoring: ProjectMapMonitoring, sensorState: sensorState, tdePresentation: tdePresentation };
    } else {
        root.ProjectMapMonitoring = ProjectMapMonitoring;
    }
}(globalThis));
