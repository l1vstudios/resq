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
            OFFLINE: [0.5, 'Offline', 'unknown'], UNKNOWN: [0.5, 'Belum diketahui', 'unknown']
        };
        var values = [sensor.status, sensor.alert_level].filter(Boolean);
        if (sensor.threshold_exceeded) values.push('AWAS');
        if (!values.length) return { label: 'Belum diketahui', tone: 'unknown' };
        var state = values.map(function (value) {
            return states[String(value).trim().toUpperCase()] || [-1, String(value), 'unknown'];
        }).sort(function (a, b) { return b[0] - a[0]; })[0];
        return { label: state[1], tone: state[2] };
    }

    function sortSensorsByPriority(sensors) {
        var priorities = { awas: 3, siaga: 2, waspada: 1, normal: 0, unknown: -1 };
        return sensors.slice().sort(function (a, b) {
            return priorities[sensorState(b).tone] - priorities[sensorState(a).tone] ||
                String(a.name || '').localeCompare(String(b.name || ''), 'id', { numeric: true });
        });
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

    function tdeCardHtml(station) {
        var result = tdePresentation(station.tde);
        return '<article class="monitor-tde-card">' +
            (station.project_name ? '<div class="monitor-tde-project"><strong>' + escapeHtml(station.project_name) + '</strong><small>' + escapeHtml(station.project_code) + '</small></div>' : '') +
            '<div class="monitor-tde-heading"><div><small class="monitor-muted">ID Monitoring Station</small><strong>' + escapeHtml(station.station_code) + '</strong></div>' +
            '<span class="monitor-badge tone-' + result.tone + '">' + escapeHtml(result.label) + '</span></div>' +
            '<small class="monitor-muted">' + escapeHtml(station.name) + '</small>' +
            '<h3>' + escapeHtml(result.summary) + '</h3>' +
            '<p>' + escapeHtml(result.basis) + '</p>' +
            '<dl><div><dt>Kondisi</dt><dd>' + escapeHtml(result.condition) + '</dd></div><div><dt>Pola</dt><dd>' + escapeHtml(result.pattern) + '</dd></div>' +
            '<div><dt>Perkiraan waktu</dt><dd>' + escapeHtml(result.estimate) + '</dd></div></dl>' +
            (result.missing ? '<small class="monitor-muted">Parameter belum cukup: ' + escapeHtml(result.missing) + '</small>' : '') +
            (result.matrixStatus === 'matrix_not_configured' ? '<small class="monitor-muted">Matriks TDE belum dikonfigurasi.</small>' : '') +
            (result.window ? '<small class="monitor-muted">Analisis ' + escapeHtml(result.window) + ' menit terakhir.</small>' : '') +
            (station.forecast_url && /^\/(?!\/)/.test(station.forecast_url) ? '<a class="monitor-tde-detail" href="' + escapeHtml(station.forecast_url) + '">Detail TDE →</a>' : '') + '</article>';
    }

    function tdeSummaryHtml(station) {
        var result = tdePresentation(station.tde);
        return '<article class="monitor-tde-summary" aria-label="Hasil TDE ' + escapeHtml(station.station_code) + '">' +
            '<h4>' + escapeHtml(station.station_code) + '</h4>' +
            '<dl class="monitor-tde-metrics"><div><dt>Kondisi</dt><dd>' + escapeHtml(result.condition) + '</dd></div>' +
            '<div><dt>Pola</dt><dd>' + escapeHtml(result.pattern) + '</dd></div>' +
            '<div><dt>Perkiraan waktu</dt><dd>' + escapeHtml(result.estimate) + '</dd></div></dl></article>';
    }

    function mapViewportPadding(mapRect, panelRect, headerRect) {
        var padding = { paddingTopLeft: [40, Math.max(40, headerRect.bottom - mapRect.top + 16)], paddingBottomRight: [40, 40] };
        if (panelRect.width === 0 && panelRect.height === 0) return padding;
        // Keep a usable map area even when the sidebar fills most of a small screen.
        padding.paddingBottomRight[0] = Math.max(40, Math.min(
            Math.ceil(mapRect.right - panelRect.left + 24), mapRect.width - padding.paddingTopLeft[0] - 120
        ));
        return padding;
    }

    function ProjectMapMonitoring(options) {
        this.options = options;
        this.panel = options.panel;
        this.panelScrollTop = 0;
        this.elements = options.elements || {};
        if (!options.elements) {
            var panel = options.panel;
            ['sensor-rows', 'sensor-count', 'sensor-update', 'tde-results', 'tde-update', 'siren-toggle', 'siren-status', 'panel-toggle', 'panel-body', 'station-details', 'station-content', 'station-close'].forEach(function (name) {
                this.elements[name] = panel.querySelector('[data-monitor="' + name + '"]');
            }, this);
        }
        this.elements['panel-open'] = options.openButton || this.elements['panel-open'];
        if (options.sensorDialog) {
            this.elements['sensor-dialog'] = options.sensorDialog;
            ['sensor-detail-title', 'sensor-detail-content', 'sensor-detail-close'].forEach(function (name) {
                this.elements[name] = options.sensorDialog.querySelector('[data-monitor="' + name + '"]');
            }, this);
        }
        this.audio = options.audio || new Audio(options.sirenUrl);
        this.audio.loop = true;
        this.audio.preload = 'none';
        this.sirenRequested = false;
        this.sirenGeneration = 0;
        this.tdeInFlight = false;
        this.destroyed = false;
        this.selectedWarningStationId = null;
        this.selectedSensorName = null;
        this.warningStations = [];
        this.sensors = [];
        this.elements['sensor-rows'].addEventListener('click', function (event) {
            var button = event.target.closest('[data-sensor-detail]');
            if (button && this.elements['sensor-rows'].contains(button)) this.selectSensor(button.getAttribute('data-sensor-detail'));
        }.bind(this));
        if (this.elements['sensor-dialog']) {
            this.elements['sensor-detail-close'].addEventListener('click', function () {
                this.elements['sensor-dialog'].close();
            }.bind(this));
            this.elements['sensor-dialog'].addEventListener('close', function () {
                var name = this.selectedSensorName;
                this.selectedSensorName = null;
                var button = Array.from(this.elements['sensor-rows'].querySelectorAll('[data-sensor-detail]')).find(function (item) {
                    return item.getAttribute('data-sensor-detail') === name;
                });
                (button || this.elements['panel-toggle']).focus({ preventScroll: true });
            }.bind(this));
        }
        this.elements['siren-toggle'].addEventListener('click', function () {
            this.setSiren(!this.sirenRequested);
        }.bind(this));
        this.elements['panel-toggle'].addEventListener('click', function () {
            this.setPanelExpanded(this.elements['panel-body'].hidden);
        }.bind(this));
        if (this.elements['panel-open']) this.elements['panel-open'].addEventListener('click', function () {
            this.setPanelExpanded(true);
            this.elements['panel-toggle'].focus();
        }.bind(this));
        if (this.elements['station-close']) this.elements['station-close'].addEventListener('click', function () {
            this.selectedWarningStationId = null;
            this.elements['station-details'].hidden = true;
            this.elements['panel-toggle'].focus();
        }.bind(this));
    }

    ProjectMapMonitoring.prototype.setPanelExpanded = function (expanded) {
        if (!expanded) this.panelScrollTop = this.elements['panel-body'].scrollTop || 0;
        this.elements['panel-body'].hidden = !expanded;
        if (this.panel) this.panel.hidden = !expanded;
        this.elements['panel-toggle'].setAttribute('aria-expanded', String(expanded));
        if (expanded) this.elements['panel-body'].scrollTop = this.panelScrollTop;
        if (this.elements['panel-open']) {
            this.elements['panel-open'].hidden = expanded;
            this.elements['panel-open'].setAttribute('aria-expanded', String(expanded));
            if (!expanded) this.elements['panel-open'].focus();
        }
    };

    ProjectMapMonitoring.prototype.selectSensor = function (name) {
        var dialog = this.elements['sensor-dialog'];
        if (!dialog || !this.sensors.some(function (sensor) { return String(sensor.name) === name; })) return;
        this.selectedSensorName = name;
        this.renderSensorDetail();
        if (!dialog.open) dialog.showModal();
        this.elements['sensor-detail-title'].focus({ preventScroll: true });
    };

    ProjectMapMonitoring.prototype.renderSensorDetail = function () {
        if (this.selectedSensorName === null || !this.elements['sensor-dialog']) return;
        var sensor = this.sensors.find(function (item) { return String(item.name) === this.selectedSensorName; }, this);
        this.elements['sensor-detail-title'].textContent = this.selectedSensorName;
        if (!sensor) {
            this.elements['sensor-detail-content'].innerHTML = '<p>Sensor ini tidak lagi tersedia pada project.</p>';
            return;
        }
        var state = sensorState(sensor);
        var parameters = Array.isArray(sensor.parameter_values) ? sensor.parameter_values : [];
        var readings = parameters.length ? parameters : [{ label: 'Nilai terakhir', value_text: sensor.value }];
        var html = '<span class="monitor-badge tone-' + state.tone + '">' + escapeHtml(state.label) + '</span>' +
            '<div class="sensor-detail-parameters">' + readings.map(function (item) {
                var value = item.value_text == null ? item.value : item.value_text;
                var unit = item.value_text == null && item.unit ? ' ' + escapeHtml(item.unit) : '';
                return '<div class="sensor-detail-reading"><small>' + escapeHtml(item.label || item.parameter || item.name || 'Parameter') +
                    '</small><strong>' + escapeHtml(value) + unit + '</strong></div>';
            }).join('') + '</div>' +
            '<dl class="sensor-detail-meta">' + [
                ['Monitoring station', sensor.station], ['Parameter / jenis', sensor.parameter || sensor.type],
                ['Warning station', [sensor.warning_station, sensor.warning_station_name].filter(Boolean).join(' · ')],
                ['Threshold', sensor.threshold], ['Pembaruan terakhir', sensor.last_seen || 'Belum ada pembacaan']
            ].map(function (row) { return '<dt>' + escapeHtml(row[0]) + '</dt><dd>' + escapeHtml(row[1] == null || row[1] === '' ? '—' : row[1]) + '</dd>'; }).join('') + '</dl>';
        if (this.elements['sensor-detail-content'].innerHTML !== html) this.elements['sensor-detail-content'].innerHTML = html;
    };

    ProjectMapMonitoring.prototype.selectWarningStation = function (id) {
        this.selectedWarningStationId = String(id);
        this.setPanelExpanded(true);
        this.renderWarningStation();
        this.elements['panel-body'].scrollTop = 0;
        var details = this.elements['station-details'];
        var body = this.elements['panel-body'];
        if (details.getBoundingClientRect && body.getBoundingClientRect) {
            body.scrollTop = Math.max(0, details.getBoundingClientRect().top - body.getBoundingClientRect().top - 12);
        }
        var heading = this.elements['station-content'].querySelector('h4');
        if (heading) heading.focus({ preventScroll: true });
    };

    ProjectMapMonitoring.prototype.renderWarningStation = function () {
        var details = this.elements['station-details'];
        if (!details || this.selectedWarningStationId === null) return;
        details.hidden = false;
        var station = this.warningStations.find(function (item) {
            return String(item.id) === this.selectedWarningStationId;
        }, this);
        if (!station) {
            this.elements['station-content'].innerHTML = '<p class="monitor-empty">Station ini tidak lagi tersedia di peta.</p>';
            return;
        }
        var sensors = this.sensors.filter(function (sensor) { return sensor.warning_station === station.station_code; });
        var rows = [
            ['Kode station', station.station_code], ['Status', station.status],
            ['Workspace', station.workspace], ['Monitoring station', station.monitoring_station],
            ['Zona', station.zone_id], ['Lokasi', station.administrative_location || [station.city, station.province].filter(Boolean).join(', ')],
            ['Koordinat', station.lat + ', ' + station.lng], ['Controller', station.controller_id],
            ['Status controller', station.controller_status],
            ['Public warning', station.public_warning_enabled ? 'Aktif' : 'Standby'], ['ACK', station.ack_response]
        ];
        var html = '<h4 tabindex="-1">' + escapeHtml(station.name) + '</h4><dl>' + rows.map(function (row) {
            return '<dt>' + escapeHtml(row[0]) + '</dt><dd>' + escapeHtml(row[1] == null || row[1] === '' ? '—' : row[1]) + '</dd>';
        }).join('') + '</dl><strong>Sensor terhubung · ' + sensors.length + '</strong>' + (sensors.length ? sensors.map(function (sensor) {
            var state = sensorState(sensor);
            var parameters = Array.isArray(sensor.parameter_values) ? sensor.parameter_values : [];
            var value = parameters.length ? parameters.map(function (item) {
                return escapeHtml(item.label || item.parameter || item.name) + ': ' + escapeHtml(item.value_text == null ? item.value : item.value_text);
            }).join('<br>') : escapeHtml(sensor.value);
            return '<div class="monitor-station-sensor"><strong>' + escapeHtml(sensor.name) + '</strong> ' +
                '<span class="monitor-badge tone-' + state.tone + '">' + escapeHtml(state.label) + '</span><div>' + value + '</div>' +
                '<small class="monitor-muted">Threshold: ' + escapeHtml(sensor.threshold) + ' · ' + escapeHtml(sensor.last_seen || 'Belum ada pembacaan') + '</small></div>';
        }).join('') : '<p class="monitor-muted">Belum ada sensor terhubung.</p>');
        // Avoid replacing the focused heading when a poll returns unchanged data.
        if (this.elements['station-content'].innerHTML !== html) this.elements['station-content'].innerHTML = html;
    };

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
        var sensors = sortSensorsByPriority(Array.isArray(data.sensors) ? data.sensors : []);
        this.sensors = sensors;
        this.warningStations = Array.isArray(data.warningStations) ? data.warningStations : [];
        this.renderWarningStation();
        this.renderSensorDetail();
        this.elements['sensor-count'].textContent = sensors.length + ' sensor';
        var sensorHtml = sensors.length ? sensors.map(function (sensor) {
            var state = sensorState(sensor);
            return '<article class="monitor-sensor-card" role="listitem">' +
                '<i class="bx bxs-flame monitor-sensor-icon" aria-hidden="true"></i>' +
                '<button type="button" class="monitor-sensor-detail-button" data-sensor-detail="' + escapeHtml(sensor.name) + '" aria-label="Lihat detail nilai sensor ' + escapeHtml(sensor.name) + '" title="Lihat detail nilai sensor ' + escapeHtml(sensor.name) + '"><i class="bx bx-up-arrow-alt" aria-hidden="true"></i></button>' +
                '<h4>' + escapeHtml(sensor.name) + '</h4>' +
                '<span class="monitor-badge tone-' + state.tone + '">' + escapeHtml(state.label) + '</span></article>';
        }).join('') : '<p class="monitor-empty">Belum ada sensor pada project ini.</p>';
        if (this.elements['sensor-rows'].innerHTML !== sensorHtml) {
            var active = this.panel && this.panel.ownerDocument && this.panel.ownerDocument.activeElement;
            var focusedName = active && this.elements['sensor-rows'].contains(active) && active.matches('[data-sensor-detail]')
                ? active.getAttribute('data-sensor-detail') : null;
            this.elements['sensor-rows'].innerHTML = sensorHtml;
            if (focusedName !== null) {
                var replacement = Array.from(this.elements['sensor-rows'].querySelectorAll('[data-sensor-detail]')).find(function (item) {
                    return item.getAttribute('data-sensor-detail') === focusedName;
                });
                if (replacement) replacement.focus({ preventScroll: true });
            }
        }
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
        this.elements['tde-results'].innerHTML = stations.length ? stations.map(tdeSummaryHtml).join('') : '<p class="monitor-empty">Belum ada monitoring station untuk evaluasi TDE.</p>';
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
        module.exports = { ProjectMapMonitoring: ProjectMapMonitoring, sensorState: sensorState, tdeCardHtml: tdeCardHtml, tdeSummaryHtml: tdeSummaryHtml, sortSensorsByPriority: sortSensorsByPriority, tdePresentation: tdePresentation, mapViewportPadding: mapViewportPadding };
    } else {
        root.ProjectMapMonitoring = ProjectMapMonitoring;
        root.projectMapViewportPadding = mapViewportPadding;
        root.projectMapTdeCard = tdeCardHtml;
    }
}(globalThis));
