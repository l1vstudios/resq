const { test } = require('node:test');
const assert = require('node:assert/strict');
const { DashboardTdeCards } = require('../../public/build/js/pages/dashboard-tde-cards.js');
const { tdeCardHtml } = require('../../public/build/js/pages/project-map-monitoring.js');

function setup(fetch) {
    const results = { innerHTML: '' };
    const status = { textContent: '' };
    const cards = new DashboardTdeCards({ results, status, fetch, renderCard: tdeCardHtml, url: '/dashboard/tde-data', document: { hidden: false } });
    return { cards, results, status };
}

test('cards show escaped project and station identity without inventing a forecast from partial data', () => {
    const html = tdeCardHtml({ project_name: '<Project>', project_code: 'PRJ-1', station_code: 'MON-1', name: '<Station>', forecast_url: '/tde-forecast?station_id=1',
        tde: { execution_state: 'partial', output: { combination_pattern: { key: '??' }, diagnosis: { matrix_match: { summary: 'Old forecast', estimated_time: '20 menit' } } } } });
    assert.match(html, /&lt;Project&gt;/);
    assert.match(html, /ID Monitoring Station/);
    assert.match(html, /MON-1/);
    assert.match(html, /Data TDE belum cukup/);
    assert.doesNotMatch(html, /Old forecast|20 menit|<Station>/);
    assert.match(html, /href="\/tde-forecast\?station_id=1"/);
    assert.doesNotMatch(tdeCardHtml({ forecast_url: 'javascript:alert(1)' }), /href=/);
});

test('dashboard cards follow the critical project order and retain results when a refresh fails', async () => {
    let requestUrl;
    const { cards, results, status } = setup(async url => {
        requestUrl = url;
        return { ok: true, json: async () => ({ stations: [
            { project_id: 1, project_name: 'Normal project', station_code: 'MON-1' },
            { project_id: 2, project_name: 'Awas project', station_code: 'MON-2' }
        ], generated_at: new Date().toISOString() }) };
    });
    await cards.setProjects([2, 1]);
    assert.equal(requestUrl, '/dashboard/tde-data?project_ids[]=2&project_ids[]=1');
    assert.ok(results.innerHTML.indexOf('Awas project') < results.innerHTML.indexOf('Normal project'));
    const last = results.innerHTML;
    cards.options.fetch = async () => ({ ok: false });
    await cards.refresh();
    assert.equal(results.innerHTML, last);
    assert.match(status.textContent, /data terakhir/);
});

test('a project change discards an old response and requests the new scope without overlapping', async () => {
    const requests = [];
    const { cards, results } = setup(url => new Promise(resolve => requests.push({ url, resolve })));
    const first = cards.setProjects([1]);
    await cards.setProjects([2]);
    assert.equal(requests.length, 1);
    requests[0].resolve({ ok: true, json: async () => ({ stations: [{ project_id: 1, project_name: 'Old project' }] }) });
    await first;
    assert.equal(requests.length, 2);
    assert.doesNotMatch(results.innerHTML, /Old project/);
    assert.match(requests[1].url, /project_ids\[\]=2/);
    cards.destroy();
    requests[1].resolve({ ok: true, json: async () => ({ stations: [{ project_id: 2, project_name: 'Late result' }] }) });
    await new Promise(resolve => setImmediate(resolve));
    assert.doesNotMatch(results.innerHTML, /Late result/);
});
