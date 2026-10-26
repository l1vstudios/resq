const { test } = require('node:test');
const assert = require('node:assert/strict');
const { DashboardWeatherWidget, nearestWeatherCity, projectWeatherCity } = require('../../public/build/js/pages/dashboard-weather-widget.js');

function element() {
    return {
        attributes: {}, children: [], value: '', textContent: '', disabled: false,
        setAttribute(name, value) { this.attributes[name] = value; },
        appendChild(child) { this.children.push(child); },
        replaceChildren() { this.children = []; },
        addEventListener(name, listener) { this[name] = listener; },
        querySelector() { return null; }
    };
}

function setup(projectLocations = [{ city: 'Lumajang', lat: -8.1724, lng: 112.9716 }]) {
    const elements = Object.fromEntries(['anchor', 'citySelect', 'projectLocationButton', 'location', 'status', 'viewport', 'forecastNavigation', 'previousButton', 'nextButton'].map(name => [name, element()]));
    elements.viewport.clientWidth = 320;
    elements.viewport.scrollLeft = 0;
    elements.viewport.hidden = elements.forecastNavigation.hidden = true;
    elements.viewport.scrollCalls = [];
    elements.viewport.scrollBy = function (options) { this.scrollCalls.push(options); this.scrollLeft += options.left; };
    const window = {
        navigator: { geolocation: { getCurrentPosition() { assert.fail('Project weather must not request browser location'); } } },
        __weatherwidget_init() {}
    };
    const document = {
        head: element(), createElement: element,
        getElementById(id) { return this.head.children.find(child => child.id === id); }
    };
    const widget = new DashboardWeatherWidget({ ...elements, window, document, projectLocations });
    return { widget, window, elements, document };
}

test('Semeru uses its project city Lumajang with seven days, Celsius and no embedded city label', () => {
    const { widget, elements } = setup();
    widget.start();
    assert.equal(elements.citySelect.value, 'lumajang');
    assert.equal(elements.anchor.href, 'https://forecast7.com/en/n8d13113d22/lumajang/');
    assert.equal(elements.anchor.attributes['data-label_1'], '');
    assert.equal(elements.anchor.attributes['data-label_2'], '');
    assert.equal(elements.anchor.attributes['data-mode'], 'Forecast');
    assert.equal(elements.anchor.attributes['data-days'], '7');
    assert.doesNotMatch(elements.anchor.href, /unit=us|jakarta/);
    assert.match(elements.status.textContent, /wilayah project: Lumajang/);
    assert.equal(elements.viewport.hidden, false);
    assert.equal(elements.forecastNavigation.hidden, false);
});

test('administrative city takes priority over coordinates and accepts common prefixes', () => {
    for (const city of ['Kabupaten Lumajang', 'Kab. Lumajang', 'lumajang']) {
        assert.equal(projectWeatherCity([{ city, lat: -6.21, lng: 106.85 }]).city.id, 'lumajang');
    }
    const { widget, elements } = setup([{ city: 'Kota Malang' }]);
    widget.start();
    assert.equal(elements.anchor.href, 'https://forecast7.com/en/n7d97112d63/malang/');
});

test('coordinates select the nearest supported city when the project city is absent or unknown', () => {
    const { widget, elements } = setup([{ city: 'Kecamatan tidak terdaftar', lat: '-8.1724', lng: '112.9716' }]);
    widget.start();
    assert.equal(elements.citySelect.value, 'lumajang');
    assert.match(elements.status.textContent, /terdekat dari koordinat project/);
    assert.match(elements.status.textContent, /Prakiraan berlaku untuk kota tersebut/);
    assert.equal(projectWeatherCity([{ city: null, lat: null, lng: null }, { lat: '-7.97', lng: '112.63' }]).city.id, 'malang');
});

test('missing or invalid locations show an empty state until a city is selected manually', () => {
    const invalid = [{ lat: null, lng: null }, { lat: '', lng: '' }, { lat: 91, lng: 106.8 }, { lat: 'invalid', lng: 112.6 }];
    assert.equal(projectWeatherCity(invalid), null);
    const { widget, elements, document } = setup(invalid);
    widget.start();
    assert.equal(elements.anchor.href, undefined);
    assert.equal(elements.viewport.hidden, true);
    assert.equal(elements.forecastNavigation.hidden, true);
    assert.equal(document.head.children.length, 0);
    assert.equal(elements.citySelect.value, '');
    assert.equal(elements.projectLocationButton.disabled, true);
    assert.match(elements.status.textContent, /Lengkapi kota atau koordinat di Geospatial/);
    elements.citySelect.value = 'malang';
    elements.citySelect.change();
    assert.match(elements.anchor.href, /malang/);
    assert.equal(elements.viewport.hidden, false);
    assert.equal(document.head.children.length, 1);
});

test('manual city selection can be reset to the project location', () => {
    const { widget, elements } = setup();
    widget.start();
    elements.citySelect.value = 'bandung';
    elements.citySelect.change();
    assert.match(elements.status.textContent, /dipilih secara manual/);
    elements.projectLocationButton.click();
    assert.equal(elements.citySelect.value, 'lumajang');
    assert.match(elements.location.textContent, /Lumajang/);
});

test('forecast navigation moves a full visible page and returns to today when the city changes', () => {
    const { widget, elements, window } = setup();
    widget.start();
    elements.nextButton.click();
    assert.equal(elements.viewport.scrollLeft, elements.viewport.clientWidth);
    assert.equal(elements.viewport.scrollCalls[0].left, elements.viewport.clientWidth);
    assert.equal(elements.viewport.scrollCalls[0].behavior, 'smooth');
    elements.previousButton.click();
    assert.equal(elements.viewport.scrollLeft, 0);
    assert.equal(elements.viewport.scrollCalls[1].left, -elements.viewport.clientWidth);
    window.matchMedia = () => ({ matches: true });
    elements.nextButton.click();
    assert.equal(elements.viewport.scrollCalls.at(-1).behavior, 'auto');
    elements.citySelect.value = 'malang';
    elements.citySelect.change();
    assert.equal(elements.viewport.scrollLeft, 0);
});

test('the supplied provider script loads once and a blocked script retains a usable project forecast link', () => {
    const { widget, document, elements } = setup();
    widget.start();
    widget.start();
    elements.projectLocationButton.click();
    assert.equal(document.head.children.length, 1);
    const script = document.head.children[0];
    assert.equal(script.src, 'https://weatherwidget.io/js/widget.min.js');
    script.error();
    assert.match(elements.status.textContent, /Widget cuaca belum dapat dimuat/);
    assert.match(elements.anchor.href, /lumajang/);
});

test('nearest city rejects out of range coordinates and supports valid zero coordinates', () => {
    assert.equal(nearestWeatherCity(NaN, 106.8), null);
    assert.equal(nearestWeatherCity(91, 106.8), null);
    assert.equal(nearestWeatherCity(-8, 181), null);
    assert.equal(nearestWeatherCity(-8.67, 115.21).city.name, 'Denpasar');
    assert.ok(projectWeatherCity([{ lat: 0, lng: 109.34 }]));
});
