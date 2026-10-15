const { test } = require('node:test');
const assert = require('node:assert/strict');
const { ProjectMapMonitoring, sensorState, tdePresentation } = require('../../public/build/js/pages/project-map-monitoring.js');

function setup(options = {}) {
    const elements = {};
    for (const name of ['sensor-rows', 'sensor-count', 'sensor-update', 'tde-results', 'tde-update', 'siren-toggle', 'siren-status', 'panel-toggle', 'panel-body']) {
        elements[name] = {
            textContent: '', innerHTML: '', attributes: {}, hidden: false,
            addEventListener() {}, setAttribute(key, value) { this.attributes[key] = value; },
            classList: { toggle() {} }
        };
    }
    const audio = { loop: false, pause() { this.paused = true; }, play() { this.paused = false; return Promise.resolve(); }, currentTime: 0 };
    const panel = new ProjectMapMonitoring({ elements, audio, ...options });
    return { panel, elements, audio };
}

test('sensor status uses the highest warning and preserves unknown status', () => {
    assert.deepEqual(sensorState({ status: 'Normal', alert_level: 'Waspada' }), { label: 'Waspada', tone: 'waspada' });
    assert.equal(sensorState({ status: 'Siaga', alert_level: 'Normal' }).tone, 'siaga');
    assert.equal(sensorState({ status: 'Normal', threshold_exceeded: true }).label, 'Awas');
    assert.equal(sensorState({}).tone, 'unknown');
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

test('sensor overlay displays zero readings, multi parameter values, and escapes text', () => {
    const { panel, elements } = setup();
    panel.updateSensors({ sensors: [{ name: '<img onerror=alert(1)>', value: '0 m', status: 'Normal', parameter_values: [
        { parameter: 'Rainfall', value: 0, value_text: '0 mm' }
    ] }], generated_at: new Date().toISOString() });
    assert.match(elements['sensor-rows'].innerHTML, /0 mm/);
    assert.match(elements['sensor-rows'].innerHTML, /&lt;img/);
    assert.doesNotMatch(elements['sensor-rows'].innerHTML, /<img/);
    assert.equal(elements['sensor-count'].textContent, '1 sensor');
    panel.showSensorError();
    assert.match(elements['sensor-update'].textContent, /data terakhir/);
    assert.match(elements['sensor-rows'].innerHTML, /0 mm/);
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
    assert.match(elements['tde-results'].innerHTML, /Data TDE belum cukup/);
    panel.options.fetch = async () => ({ ok: false });
    await panel.refreshTde();
    assert.match(elements['tde-update'].textContent, /terputus/);
    assert.match(elements['tde-results'].innerHTML, /MON-1/);
    panel.destroy();
    await panel.refreshTde();
    assert.equal(requests, 1);
});
