const { test } = require('node:test');
const assert = require('node:assert/strict');
const { ProjectMapMonitoring, sensorState, sortSensorsByPriority, tdePresentation, tdeSummaryHtml, mapViewportPadding } = require('../../public/build/js/pages/project-map-monitoring.js');

function setup(options = {}) {
    const elements = {};
    for (const name of ['sensor-rows', 'sensor-count', 'sensor-update', 'tde-results', 'tde-update', 'siren-toggle', 'siren-status', 'panel-toggle', 'panel-open', 'panel-body', 'station-details', 'station-content', 'station-close', 'sensor-dialog', 'sensor-detail-title', 'sensor-detail-content', 'sensor-detail-close']) {
        elements[name] = {
            textContent: '', innerHTML: '', attributes: {}, hidden: false, listeners: {},
            addEventListener(event, callback) { this.listeners[event] = callback; if (event !== 'close') this[event] = callback; }, setAttribute(key, value) { this.attributes[key] = value; },
            querySelector() { return null; }, focus() { this.focused = true; },
            contains() { return true; }, querySelectorAll() { return []; },
            classList: { toggle() {} }
        };
    }
    elements['sensor-dialog'].open = false;
    elements['sensor-dialog'].showModal = function () { this.open = true; };
    elements['sensor-dialog'].close = function () { this.open = false; this.listeners.close(); };
    elements['panel-open'].hidden = true;
    const audio = { loop: false, pause() { this.paused = true; }, play() { this.paused = false; return Promise.resolve(); }, currentTime: 0 };
    const panelRoot = { hidden: false };
    const panel = new ProjectMapMonitoring({ panel: panelRoot, elements, audio, ...options });
    return { panel, panelRoot, elements, audio };
}

test('map framing reserves the right sidebar while retaining usable space on small screens', () => {
    assert.deepEqual(mapViewportPadding(
        { left: 0, top: 0, right: 1440, bottom: 900, width: 1440 },
        { left: 964, top: 16, width: 460, height: 858 }, { bottom: 80 }
    ), { paddingTopLeft: [40, 96], paddingBottomRight: [500, 40] });
    assert.deepEqual(mapViewportPadding(
        { left: 0, top: 0, right: 390, bottom: 844, width: 390 },
        { left: 72, top: 80, width: 310, height: 740 }, { bottom: 64 }
    ), { paddingTopLeft: [40, 80], paddingBottomRight: [230, 40] });
    assert.deepEqual(mapViewportPadding(
        { left: 0, top: 0, right: 1440, bottom: 900, width: 1440 },
        { left: 0, top: 0, width: 0, height: 0 }, { bottom: 80 }
    ), { paddingTopLeft: [40, 96], paddingBottomRight: [40, 40] });
});

test('the card arrow opens only its sensor readings, stays current after sorting, and returns focus on close', () => {
    const { panel, elements } = setup();
    const sensorA = { name: 'SENSOR-A', status: 'Normal', station: 'MON-A', parameter_values: [
        { label: '<Temperature>', value: 0, unit: '°C' }, { parameter: 'Humidity', value_text: '55 %RH' }
    ] };
    const sensorB = { name: 'SENSOR-B', status: 'Awas', value: '999 m' };
    panel.updateSensors({ sensors: [sensorA, sensorB] });
    assert.match(elements['sensor-rows'].innerHTML, /monitor-sensor-detail-button/);
    assert.match(elements['sensor-rows'].innerHTML, /data-sensor-detail="SENSOR-A"/);
    assert.doesNotMatch(elements['sensor-rows'].innerHTML, /55 %RH|999 m/);
    const opener = { getAttribute: () => 'SENSOR-A', focus() { this.focused = true; } };
    elements['sensor-rows'].querySelectorAll = () => [opener];
    elements['sensor-rows'].click({ target: { closest: () => opener } });
    assert.equal(elements['sensor-dialog'].open, true);
    assert.equal(elements['sensor-detail-title'].textContent, 'SENSOR-A');
    assert.match(elements['sensor-detail-content'].innerHTML, /0 °C/);
    assert.match(elements['sensor-detail-content'].innerHTML, /55 %RH/);
    assert.match(elements['sensor-detail-content'].innerHTML, /&lt;Temperature&gt;/);
    assert.doesNotMatch(elements['sensor-detail-content'].innerHTML, /999 m|<Temperature>/);
    sensorA.parameter_values[0].value = 34;
    sensorA.threshold_exceeded = true;
    panel.updateSensors({ sensors: [sensorB, sensorA] });
    assert.equal(elements['sensor-detail-title'].textContent, 'SENSOR-A');
    assert.match(elements['sensor-detail-content'].innerHTML, /34 °C/);
    assert.match(elements['sensor-detail-content'].innerHTML, /Awas/);
    elements['sensor-detail-close'].click();
    assert.equal(elements['sensor-dialog'].open, false);
    assert.equal(panel.selectedSensorName, null);
    assert.equal(opener.focused, true);
    panel.selectSensor('SENSOR-B');
    panel.updateSensors({ sensors: [sensorA] });
    assert.match(elements['sensor-detail-content'].innerHTML, /tidak lagi tersedia/);
    assert.doesNotMatch(elements['sensor-detail-content'].innerHTML, /999 m/);
});

test('minimizing hides the whole sidebar, retains only its opener, and restores scroll position', () => {
    const { panel, panelRoot, elements } = setup();
    elements['panel-body'].scrollTop = 215;
    elements['panel-toggle'].click();
    assert.equal(panelRoot.hidden, true);
    assert.equal(elements['panel-body'].hidden, true);
    assert.equal(elements['panel-open'].hidden, false);
    assert.equal(elements['panel-open'].attributes['aria-expanded'], 'false');
    assert.equal(elements['panel-open'].focused, true);
    panel.updateSensors({ sensors: [{ name: 'CRITICAL', status: 'Awas' }] });
    panel.renderTde({ stations: [], generated_at: new Date().toISOString() });
    assert.equal(panelRoot.hidden, true);
    elements['panel-open'].click();
    assert.equal(panelRoot.hidden, false);
    assert.equal(elements['panel-open'].hidden, true);
    assert.equal(elements['panel-body'].scrollTop, 215);
    assert.equal(elements['panel-toggle'].attributes['aria-expanded'], 'true');
    assert.equal(elements['panel-toggle'].focused, true);
});

test('station selection opens the matching details, survives updates, and closes without moving the map', () => {
    const { panel, panelRoot, elements } = setup();
    const data = { warningStations: [
        { id: 1, station_code: 'WARN-A', name: '<Station A>', lat: -6.2, lng: 106.8 },
        { id: 2, station_code: 'WARN-B', name: 'Station B', lat: -6.3, lng: 106.9 }
    ], sensors: [
        { name: 'SENSOR-A', warning_station: 'WARN-A', value: 0, status: 'Normal' },
        { name: 'SENSOR-B', warning_station: 'WARN-B', value: 8, status: 'Awas' }
    ] };
    panel.updateSensors(data);
    panel.setPanelExpanded(false);
    panel.selectWarningStation(1);
    assert.equal(panelRoot.hidden, false);
    assert.equal(elements['panel-open'].hidden, true);
    assert.equal(elements['panel-body'].hidden, false);
    assert.equal(elements['panel-toggle'].attributes['aria-expanded'], 'true');
    assert.match(elements['station-content'].innerHTML, /&lt;Station A&gt;/);
    assert.match(elements['station-content'].innerHTML, /SENSOR-A/);
    assert.doesNotMatch(elements['station-content'].innerHTML, /SENSOR-B/);
    assert.match(elements['station-content'].innerHTML, /<div>0<\/div>/);
    data.sensors[0].value = '12 m';
    panel.updateSensors(data);
    assert.match(elements['station-content'].innerHTML, /12 m/);
    assert.equal(panel.selectedWarningStationId, '1');
    panel.selectWarningStation(2);
    assert.match(elements['station-content'].innerHTML, /SENSOR-B/);
    assert.doesNotMatch(elements['station-content'].innerHTML, /SENSOR-A/);
    panel.updateSensors({ sensors: [], warningStations: [] });
    assert.match(elements['station-content'].innerHTML, /tidak lagi tersedia/);
    elements['station-close'].click();
    assert.equal(elements['station-details'].hidden, true);
    assert.equal(panel.selectedWarningStationId, null);
    assert.equal(elements['panel-toggle'].focused, true);
});

test('sensor status uses the highest warning and preserves unknown status', () => {
    assert.deepEqual(sensorState({ status: 'Normal', alert_level: 'Waspada' }), { label: 'Waspada', tone: 'waspada' });
    assert.equal(sensorState({ status: 'Siaga', alert_level: 'Normal' }).tone, 'siaga');
    assert.equal(sensorState({ status: 'Normal', threshold_exceeded: true }).label, 'Awas');
    assert.equal(sensorState({}).tone, 'unknown');
    assert.equal(sensorState({ status: 'Offline', alert_level: 'Awas' }).tone, 'awas');
    assert.equal(sensorState({ status: 'Offline', alert_level: 'Normal' }).label, 'Offline');
});

test('sensor sorting prioritizes severity over raw readings, with stable names for equal statuses', () => {
    const sensors = [
        { name: 'NORMAL', status: 'Normal', value: 9999 },
        { name: 'SIAGA', status: 'Normal', alert_level: ' siaga ', value: 1 },
        { name: 'UNKNOWN', status: 'Offline' },
        { name: 'WASPADA', status: 'Waspada' },
        { name: 'AWAS-10', status: 'Normal', threshold_exceeded: true },
        { name: 'AWAS-2', status: 'Danger' }
    ];
    assert.deepEqual(sortSensorsByPriority(sensors).map(sensor => sensor.name), ['AWAS-2', 'AWAS-10', 'SIAGA', 'WASPADA', 'NORMAL', 'UNKNOWN']);
    assert.equal(sensors[0].name, 'NORMAL');
});

test('both live sensor rows and selected station details reorder after status changes', () => {
    const { panel, elements } = setup();
    const data = { sensors: [
        { name: 'LOW', warning_station: 'WARN', status: 'Normal', value: 1000 },
        { name: 'HIGH', warning_station: 'WARN', status: 'Siaga', value: 1 }
    ], warningStations: [{ id: 1, station_code: 'WARN', name: 'Warning Station' }] };
    panel.updateSensors(data);
    panel.selectWarningStation(1);
    for (const name of ['sensor-rows', 'station-content']) {
        assert.ok(elements[name].innerHTML.indexOf('HIGH') < elements[name].innerHTML.indexOf('LOW'));
    }
    data.sensors[0].threshold_exceeded = true;
    panel.updateSensors(data);
    for (const name of ['sensor-rows', 'station-content']) {
        assert.ok(elements[name].innerHTML.indexOf('LOW') < elements[name].innerHTML.indexOf('HIGH'));
        assert.match(elements[name].innerHTML, /Awas/);
    }
});

test('partial TDE never presents a matrix diagnosis or arrival estimate as ready', () => {
    const result = tdePresentation({ execution_state: 'partial', output: {
        combination_pattern: { key: '<>??=' },
        diagnosis: { matrix_match: { summary: 'Ready forecast', estimated_time: '20 menit' } }
    } });
    assert.equal(result.summary, 'Data TDE belum cukup');
    assert.equal(result.estimate, 'Belum tersedia');
    assert.equal(result.label, 'Menunggu data');
});

test('evaluated TDE uses matrix output and identifies a fallback interpretation', () => {
    const tde = { execution_state: 'evaluated', output: { combination_pattern: { key: '<>><=' }, diagnosis: {
        matrix_match: { summary: 'Basah', estimated_time: '30 menit' }
    } } };
    assert.equal(tdePresentation(tde).estimate, '30 menit');
    tde.output.diagnosis = { fallback_interpretation: { summary: 'Arah Kering -> Basah' } };
    assert.equal(tdePresentation(tde).label, 'Interpretasi awal');
    assert.equal(tdePresentation(tde).estimate, 'Belum tersedia');
});

test('sensor cards show only escaped names and status while readings stay available in station details', () => {
    const { panel, elements } = setup();
    panel.updateSensors({ warningStations: [{ id: 1, station_code: 'WARN', name: 'Station' }], sensors: [{ name: '<img onerror=alert(1)>', warning_station: 'WARN', value: '0 m', status: 'Normal', parameter_values: [
        { parameter: 'Rainfall', value: 0, value_text: '0 mm' }
    ] }], generated_at: new Date().toISOString() });
    assert.doesNotMatch(elements['sensor-rows'].innerHTML, /0 mm|Rainfall|0 m/);
    assert.match(elements['sensor-rows'].innerHTML, /Normal/);
    assert.match(elements['sensor-rows'].innerHTML, /&lt;img/);
    assert.doesNotMatch(elements['sensor-rows'].innerHTML, /<img/);
    assert.equal(elements['sensor-count'].textContent, '1 sensor');
    panel.selectWarningStation(1);
    assert.match(elements['station-content'].innerHTML, /0 mm/);
    panel.showSensorError();
    assert.match(elements['sensor-update'].textContent, /data terakhir/);
    assert.match(elements['station-content'].innerHTML, /0 mm/);
});

test('sidebar TDE contains only the three requested metrics and keeps insufficient estimates unavailable', () => {
    const html = tdeSummaryHtml({ station_code: 'MON-1', project_name: 'Hidden project title', tde: {
        execution_state: 'partial', output: {
            condition_current: { state: 'UNKNOWN' }, combination_pattern: { key: '?????' },
            diagnosis: { matrix_match: { summary: 'Old summary', estimated_time: '20 menit' } }
        }
    } });
    assert.match(html, /Kondisi/);
    assert.match(html, /UNKNOWN/);
    assert.match(html, /Pola/);
    assert.match(html, /\?\?\?\?\?/);
    assert.match(html, /Perkiraan waktu/);
    assert.match(html, /Belum tersedia/);
    assert.doesNotMatch(html, /Hidden project title|Old summary|20 menit|Matriks|Detail TDE/);
});

test('siren starts only on request and can be stopped independently of sensor refresh', async () => {
    const { panel, audio, elements } = setup();
    assert.equal(panel.sirenRequested, false);
    panel.updateSensors({ sensors: [{ status: 'Awas' }] });
    assert.equal(panel.sirenRequested, false);
    await panel.setSiren(true);
    assert.equal(audio.paused, false);
    panel.updateSensors({ sensors: [{ status: 'Normal' }] });
    assert.equal(panel.sirenRequested, true);
    await panel.setSiren(false);
    assert.equal(audio.paused, true);
    assert.equal(elements['siren-toggle'].attributes['aria-pressed'], 'false');
});

test('stopping a siren while playback is pending keeps it stopped', async () => {
    const { panel, audio } = setup();
    let resolvePlay;
    audio.play = () => new Promise(resolve => { resolvePlay = resolve; });
    const playing = panel.setSiren(true);
    await Promise.resolve();
    await panel.setSiren(false);
    resolvePlay();
    await playing;
    assert.equal(panel.sirenRequested, false);
    assert.equal(audio.paused, true);
});

test('blocked audio resets its button and provides a retry message', async () => {
    const { panel, audio, elements } = setup();
    audio.play = () => Promise.reject(new Error('NotAllowedError'));
    await panel.setSiren(true);
    assert.equal(panel.sirenRequested, false);
    assert.equal(elements['siren-toggle'].textContent, 'Aktifkan sirene');
    assert.match(elements['siren-status'].textContent, /mencoba lagi/);
});

test('TDE polling does not overlap, retains results on failure, and stops after disposal', async () => {
    let resolveResponse;
    let requests = 0;
    const { panel, elements } = setup({ fetch: () => {
        requests++;
        return new Promise(resolve => { resolveResponse = resolve; });
    } });
    const first = panel.refreshTde();
    await panel.refreshTde();
    assert.equal(requests, 1);
    resolveResponse({ ok: true, json: async () => ({ stations: [{ station_code: 'MON-1', name: 'Station', tde: null }], generated_at: new Date().toISOString() }) });
    await first;
    assert.match(elements['tde-results'].innerHTML, /Belum tersedia/);
    panel.options.fetch = async () => ({ ok: false });
    await panel.refreshTde();
    assert.match(elements['tde-update'].textContent, /terputus/);
    assert.match(elements['tde-results'].innerHTML, /MON-1/);
    panel.destroy();
    await panel.refreshTde();
    assert.equal(requests, 1);
});
