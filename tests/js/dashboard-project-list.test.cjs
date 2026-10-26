const { test } = require('node:test');
const assert = require('node:assert/strict');
const { DashboardProjectList } = require('../../public/build/js/pages/dashboard-project-list.js');

function setup(fetch) {
    const document = { hidden: false, activeElement: null };
    const scroll = { scrollLeft: 80 };
    const container = { innerHTML: 'Normal first', contains: element => element != null, querySelector: () => scroll };
    const count = { textContent: '1 Project' };
    const status = { textContent: '' };
    const list = new DashboardProjectList({ fetch, document, container, count, status, url: '/dashboard?q=river&per_page=10&page=2' });
    return { list, document, container, count, status, scroll };
}

test('live refresh keeps filters and pagination while replacing the sorted list', async () => {
    let requested;
    const { list, container, count, scroll } = setup(async (url, options) => {
        requested = { url, options };
        return { ok: true, json: async () => ({ html: 'Awas first', count_label: '2 dari 3 Project' }) };
    });
    await list.refresh();
    assert.equal(requested.url, '/dashboard?q=river&per_page=10&page=2');
    assert.equal(requested.options.cache, 'no-store');
    assert.equal(container.innerHTML, 'Awas first');
    assert.equal(count.textContent, '2 dari 3 Project');
    assert.equal(scroll.scrollLeft, 80);
});

test('failed refresh retains the last list and reports that data is stale', async () => {
    const { list, container, status } = setup(async () => ({ ok: false }));
    await list.refresh();
    assert.equal(container.innerHTML, 'Normal first');
    assert.match(status.textContent, /data terakhir/);
});

test('refresh neither overlaps nor replaces a control the user focused while waiting', async () => {
    let resolve;
    let calls = 0;
    const { list, document, container } = setup(() => {
        calls++;
        return new Promise(done => { resolve = done; });
    });
    const pending = list.refresh();
    await list.refresh();
    assert.equal(calls, 1);
    document.activeElement = { matches: () => true };
    resolve({ ok: true, json: async () => ({ html: 'Awas first' }) });
    await pending;
    assert.equal(container.innerHTML, 'Normal first');
    await list.refresh();
    assert.equal(calls, 1);
    document.activeElement = null;
    document.hidden = true;
    await list.refresh();
    assert.equal(calls, 1);
});

test('disposing the listing prevents an in-flight response from changing the page', async () => {
    let resolve;
    const { list, container } = setup(() => new Promise(done => { resolve = done; }));
    const pending = list.refresh();
    list.destroy();
    resolve({ ok: true, json: async () => ({ html: 'Awas first' }) });
    await pending;
    assert.equal(container.innerHTML, 'Normal first');
});
