(function (root) {
    'use strict';

    // Forecast7 requires a registered city URL; arbitrary coordinate URLs return 404.
    // The provider currently has no Indonesian language code. Labels use Indonesian;
    // /en/ retains a supported forecast, with SI/Celsius as the provider default.
    var WEATHER_CITIES = [
        {"id": "banda-aceh", "name": "Banda Aceh", "lat": 5.55, "lng": 95.32, "path": "5d5595d32/banda-aceh"},
        {"id": "bandung", "name": "Bandung", "lat": -6.92, "lng": 107.62, "path": "n6d92107d62/bandung"},
        {"id": "banjarmasin", "name": "Banjarmasin", "lat": -3.32, "lng": 114.59, "path": "n3d32114d59/banjarmasin"},
        {"id": "denpasar", "name": "Denpasar", "lat": -8.67, "lng": 115.21, "path": "n8d67115d21/denpasar"},
        {"id": "jakarta", "name": "Jakarta", "lat": -6.21, "lng": 106.85, "path": "n6d21106d85/jakarta"},
        {"id": "jayapura", "name": "Jayapura", "lat": -2.59, "lng": 140.67, "path": "n2d59140d67/jayapura"},
        {"id": "kupang", "name": "Kupang", "lat": -10.18, "lng": 123.61, "path": "n10d18123d61/kupang"},
        {"id": "lumajang", "name": "Lumajang", "lat": -8.13, "lng": 113.22, "path": "n8d13113d22/lumajang"},
        {"id": "makassar", "name": "Makassar", "lat": -5.15, "lng": 119.43, "path": "n5d15119d43/makassar"},
        {"id": "malang", "name": "Malang", "lat": -7.97, "lng": 112.63, "path": "n7d97112d63/malang"},
        {"id": "manado", "name": "Manado", "lat": 1.47, "lng": 124.84, "path": "1d47124d84/manado"},
        {"id": "mataram", "name": "Mataram", "lat": -8.58, "lng": 116.1, "path": "n8d58116d10/mataram"},
        {"id": "medan", "name": "Medan", "lat": 3.6, "lng": 98.67, "path": "3d6098d67/medan"},
        {"id": "padang", "name": "Padang", "lat": -0.95, "lng": 100.42, "path": "n0d95100d42/padang"},
        {"id": "pekanbaru", "name": "Pekanbaru", "lat": 0.51, "lng": 101.45, "path": "0d51101d45/pekanbaru"},
        {"id": "pontianak", "name": "Pontianak", "lat": -0.03, "lng": 109.34, "path": "n0d03109d34/pontianak"},
        {"id": "semarang", "name": "Semarang", "lat": -7.01, "lng": 110.44, "path": "n7d01110d44/semarang"},
        {"id": "surabaya", "name": "Surabaya", "lat": -7.26, "lng": 112.75, "path": "n7d26112d75/surabaya"},
        {"id": "yogyakarta", "name": "Yogyakarta", "lat": -7.8, "lng": 110.37, "path": "n7d80110d37/yogyakarta"}
    ];

    function nearestWeatherCity(latitude, longitude, cities) {
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || Math.abs(latitude) > 90 || Math.abs(longitude) > 180) return null;
        var radians = Math.PI / 180;
        var closest = null;
        (cities || WEATHER_CITIES).forEach(function (city) {
            var latDiff = (city.lat - latitude) * radians;
            var lngDiff = (city.lng - longitude) * radians;
            var a = Math.sin(latDiff / 2) ** 2 + Math.cos(latitude * radians) * Math.cos(city.lat * radians) * Math.sin(lngDiff / 2) ** 2;
            var distance = 6371 * 2 * Math.asin(Math.sqrt(Math.min(1, a)));
            if (!closest || distance < closest.distanceKm) closest = { city: city, distanceKm: distance };
        });
        return closest;
    }

    function DashboardWeatherWidget(options) {
        this.options = options;
        this.window = options.window || root;
        this.document = options.document || this.window.document;
        this.cities = options.cities || WEATHER_CITIES;
        this.selectedCity = null;
        this.projectLocation = projectWeatherCity(options.projectLocations || [], this.cities);
        this.started = false;
    }

    function projectWeatherCity(locations, cities) {
        cities = cities || WEATHER_CITIES;
        function normalize(name) {
            return String(name || '').toLowerCase().replace(/^(kabupaten|kab\.?|kota)\s+/, '').trim();
        }
        // An administrative city takes priority over the nearest city centre.
        for (var location of locations) {
            var city = cities.find(function (city) { return normalize(city.name) === normalize(location.city); });
            if (city) return { city: city, distanceKm: null };
        }
        for (var point of locations) {
            if (point.lat === null || point.lat === undefined || String(point.lat).trim() === '' ||
                point.lng === null || point.lng === undefined || String(point.lng).trim() === '') continue;
            var nearest = nearestWeatherCity(Number(point.lat), Number(point.lng), cities);
            if (nearest) return nearest;
        }
        return null;
    }

    DashboardWeatherWidget.prototype.refreshEmbed = function () {
        if (typeof this.window.__weatherwidget_init === 'function') this.window.__weatherwidget_init();
    };

    DashboardWeatherWidget.prototype.selectCity = function (city) {
        this.selectedCity = city;
        var options = this.options;
        var href = 'https://forecast7.com/en/' + city.path + '/';
        options.anchor.href = href;
        options.anchor.setAttribute('data-label_1', '');
        options.anchor.setAttribute('data-label_2', '');
        options.anchor.setAttribute('data-mode', 'Forecast');
        options.anchor.setAttribute('data-days', '7');
        if (!options.anchor.querySelector('iframe')) options.anchor.textContent = 'Prakiraan tujuh hari';
        options.citySelect.value = city.id;
        options.location.textContent = city.name + ' · Indonesia';
        if (options.viewport) { options.viewport.hidden = false; options.viewport.scrollLeft = 0; }
        if (options.forecastNavigation) options.forecastNavigation.hidden = false;
        this.loadEmbed();
        this.refreshEmbed();
    };

    DashboardWeatherWidget.prototype.scrollForecast = function (direction) {
        var viewport = this.options.viewport;
        if (!viewport) return;
        var reducedMotion = this.window.matchMedia && this.window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        viewport.scrollBy({ left: direction * viewport.clientWidth, behavior: reducedMotion ? 'auto' : 'smooth' });
    };

    DashboardWeatherWidget.prototype.useProjectLocation = function () {
        if (!this.projectLocation) {
            this.options.status.textContent = 'Lokasi project belum tersedia. Lengkapi kota atau koordinat di Geospatial, atau pilih kota secara manual.';
            return;
        }
        this.selectCity(this.projectLocation.city);
        this.options.status.textContent = this.projectLocation.distanceKm === null
            ? 'Prakiraan mengikuti wilayah project: ' + this.selectedCity.name + '.'
            : 'Kota tersedia terdekat dari koordinat project: ' + this.selectedCity.name +
                ' (sekitar ' + Math.round(this.projectLocation.distanceKm).toLocaleString('id-ID') + ' km). Prakiraan berlaku untuk kota tersebut.';
    };

    DashboardWeatherWidget.prototype.start = function () {
        if (this.started) return;
        this.started = true;
        var options = this.options;
        options.citySelect.replaceChildren();
        var placeholder = this.document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Pilih kota';
        placeholder.disabled = true;
        options.citySelect.appendChild(placeholder);
        this.cities.forEach(function (city) {
            var option = this.document.createElement('option');
            option.value = city.id;
            option.textContent = city.name;
            options.citySelect.appendChild(option);
        }, this);
        options.citySelect.disabled = false;
        options.citySelect.value = '';
        options.citySelect.addEventListener('change', function () {
            var city = this.cities.find(function (city) { return city.id === options.citySelect.value; });
            if (!city) return;
            this.selectCity(city);
            options.status.textContent = 'Cuaca ' + city.name + ' dipilih secara manual.';
        }.bind(this));
        options.projectLocationButton.disabled = !this.projectLocation;
        options.projectLocationButton.addEventListener('click', this.useProjectLocation.bind(this));
        if (options.previousButton) options.previousButton.addEventListener('click', this.scrollForecast.bind(this, -1));
        if (options.nextButton) options.nextButton.addEventListener('click', this.scrollForecast.bind(this, 1));
        this.useProjectLocation();
    };

    DashboardWeatherWidget.prototype.loadEmbed = function () {
        var options = this.options;
        // Keep the supplied embed loader and provider script unchanged, loading once.
        if (!this.document.getElementById('weatherwidget-io-js')) {
            var script = this.document.createElement('script');
            script.id = 'weatherwidget-io-js';
            script.src = 'https://weatherwidget.io/js/widget.min.js';
            script.async = true;
            script.addEventListener('error', function () {
                options.status.textContent = 'Widget cuaca belum dapat dimuat. Klik tautan pada widget untuk melihat prakiraan ' + this.selectedCity.name + '.';
            }.bind(this));
            this.document.head.appendChild(script);
        }
    };

    DashboardWeatherWidget.mount = function (element, options) {
        if (!element) return null;
        var widget = new DashboardWeatherWidget({
            projectLocations: options && options.projectLocations,
            anchor: element.querySelector('.weatherwidget-io'),
            citySelect: element.querySelector('#dashboard-weather-city'),
            projectLocationButton: element.querySelector('#dashboard-weather-project-location'),
            location: element.querySelector('#dashboard-weather-location'),
            status: element.querySelector('#dashboard-weather-status'),
            viewport: element.querySelector('#dashboard-weather-viewport'),
            forecastNavigation: element.querySelector('#dashboard-weather-navigation'),
            previousButton: element.querySelector('#dashboard-weather-previous'),
            nextButton: element.querySelector('#dashboard-weather-next')
        });
        widget.start();
        return widget;
    };

    if (typeof module !== 'undefined' && module.exports) module.exports = { DashboardWeatherWidget: DashboardWeatherWidget, nearestWeatherCity: nearestWeatherCity, projectWeatherCity: projectWeatherCity, WEATHER_CITIES: WEATHER_CITIES };
    else root.DashboardWeatherWidget = DashboardWeatherWidget;
}(globalThis));
