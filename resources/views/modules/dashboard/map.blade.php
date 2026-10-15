<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $project->name }} | Peta Project</title>
    <link rel="shortcut icon" href="{{ asset('build/images/favicon.ico') }}">
    <link href="{{ asset('build/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('build/css/icons.min.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/libs/leaflet/leaflet.css') }}" rel="stylesheet" type="text/css" />
<style>
    .sensor-live-icon {
        background: transparent;
        border: 0;
        overflow: visible;
    }

    .sensor-map-marker-wrap {
        align-items: center;
        display: inline-flex;
        gap: 8px;
        left: 0;
        pointer-events: auto;
        position: absolute;
        top: 0;
        transform: translate(-50%, -50%);
        white-space: nowrap;
    }

    .sensor-pulse-marker {
        --sensor-color: #50a5f1;
        flex: 0 0 auto;
        position: relative;
        display: block;
        width: 18px;
        height: 18px;
        border: 3px solid #ffffff;
        border-radius: 50%;
        background: var(--sensor-color);
        box-shadow: 0 0 14px var(--sensor-color);
    }

    .sensor-pulse-marker::after {
        content: "";
        position: absolute;
        inset: -8px;
        border: 2px solid var(--sensor-color);
        border-radius: 50%;
        animation: sensorPulseRing 1.4s ease-out infinite;
    }

    .sensor-pulse-marker.warning::after {
        animation-duration: 1s;
    }

    .sensor-pulse-marker.danger {
        width: 24px;
        height: 24px;
        animation: sensorDangerBlink .72s ease-in-out infinite alternate;
    }

    .sensor-pulse-marker.danger::after {
        inset: -18px;
        animation-duration: .78s;
        border-width: 3px;
    }

    .sensor-map-label {
        background: rgba(255, 255, 255, .96);
        border: 1px solid rgba(80, 165, 241, .35);
        border-radius: 6px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .12);
        color: #343a40;
        display: block;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.15;
        max-width: 220px;
        overflow: hidden;
        padding: 5px 7px;
        pointer-events: none;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sensor-map-label small {
        color: #74788d;
        display: block;
        font-size: 11px;
        font-weight: 700;
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sensor-map-label.danger {
        animation: dangerLabelBlink .72s ease-in-out infinite alternate;
        background: rgba(244, 106, 106, .98);
        border-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(244, 106, 106, .18), 0 8px 22px rgba(244, 106, 106, .34);
        color: #ffffff;
    }

    .sensor-map-label.danger small {
        color: rgba(255, 255, 255, .9);
    }

    .danger-map-radius {
        animation: mapDangerRadiusBlink 1s ease-in-out infinite alternate;
    }

    .danger-warning-icon {
        align-items: center;
        animation: warningIconBlink .64s ease-in-out infinite alternate;
        background: #f46a6a;
        border: 3px solid #ffffff;
        border-radius: 50%;
        box-shadow: 0 0 0 12px rgba(244, 106, 106, .18), 0 0 30px rgba(244, 106, 106, .95);
        color: #ffffff;
        display: flex;
        height: 36px;
        justify-content: center;
        position: relative;
        width: 36px;
    }

    .danger-warning-icon::after {
        animation: warningRingBlink 1s ease-out infinite;
        border: 4px solid rgba(244, 106, 106, .75);
        border-radius: 50%;
        content: "";
        inset: -22px;
        position: absolute;
    }

    .danger-warning-icon i {
        font-size: 22px;
        line-height: 1;
    }

    .map-danger-popup .danger-popup-head {
        align-items: center;
        color: #f46a6a;
        display: flex;
        font-weight: 800;
        gap: 8px;
        margin-bottom: 8px;
    }

    .map-danger-popup .danger-popup-head i {
        font-size: 22px;
    }

    .map-danger-reading {
        background: rgba(244, 106, 106, .1);
        border-left: 3px solid #f46a6a;
        border-radius: 4px;
        margin-top: 8px;
        padding: 8px;
    }

    @keyframes sensorPulseRing {
        0% {
            opacity: .8;
            transform: scale(.55);
        }
        100% {
            opacity: 0;
            transform: scale(2.25);
        }
    }

    @keyframes sensorDangerBlink {
        0% {
            transform: scale(.92);
            box-shadow: 0 0 8px var(--sensor-color);
        }
        100% {
            transform: scale(1.18);
            box-shadow: 0 0 22px var(--sensor-color);
        }
    }

    @keyframes mapDangerRadiusBlink {
        0% {
            opacity: .18;
            stroke-opacity: .55;
        }
        100% {
            opacity: .56;
            stroke-opacity: 1;
        }
    }

    @keyframes dangerLabelBlink {
        0% {
            filter: brightness(.94);
            transform: translateY(0);
        }
        100% {
            filter: brightness(1.18);
            transform: translateY(-1px);
        }
    }

    @keyframes warningIconBlink {
        0% {
            transform: scale(.9);
            filter: brightness(.9);
        }
        100% {
            transform: scale(1.18);
            filter: brightness(1.35);
        }
    }

    @keyframes warningRingBlink {
        0% {
            opacity: .75;
            transform: scale(.45);
        }
        100% {
            opacity: 0;
            transform: scale(2.2);
        }
    }
</style>

    <style>
        html, body { width: 100%; height: 100%; margin: 0; overflow: hidden; }
        #sensor-cluster-map { height: 100vh; height: 100dvh; min-height: 0; border-radius: 0; }
        .project-map-header {
            position: absolute; z-index: 1000; top: 16px; left: 60px;
            display: flex; align-items: center; gap: 12px;
            max-width: calc(100% - 132px); padding: 10px 14px;
            background: #fff; border-radius: 10px; box-shadow: 0 4px 18px rgba(18, 38, 63, .16);
        }
        .project-map-title { min-width: 0; }
        .project-map-title h1 { font-size: 15px; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .project-map-title p { font-size: 12px; color: #74788d; margin: 3px 0 0; }
        .project-map-back { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 32px; height: 32px; color: #343a40; border-radius: 6px; }
        .project-map-back:hover, .project-map-back:focus-visible { background: #f1f4f8; }
        @media (max-width: 575px) {
            .project-map-header { top: 10px; gap: 6px; padding: 8px; }
            .project-map-title h1 { font-size: 13px; }
            #map-fit-filtered { padding: 4px; }
        }
    </style>
</head>
<body>
    <div id="sensor-cluster-map" role="region" aria-label="Peta {{ $project->name }}"></div>
    <header class="project-map-header">
        <a href="{{ route('dashboard') }}" class="project-map-back" aria-label="Kembali ke daftar project" title="Kembali ke daftar project"><i class="bx bx-arrow-back fs-4" aria-hidden="true"></i></a>
        <div class="project-map-title">
            <h1>{{ $project->name }}</h1>
            <p>{{ $project->project_code }}</p>
        </div>
        <button type="button" id="map-fit-filtered" class="btn btn-sm btn-light" aria-label="Fokus ke project" title="Fokus ke project"><i class="bx bx-target-lock fs-5" aria-hidden="true"></i></button>
    </header>
@include('modules.dashboard.partials.monitoring-panel')
<script src="{{ URL::asset('build/libs/leaflet/leaflet.js') }}"></script>
<script src="{{ asset('build/js/pages/project-map-monitoring.js') }}?v={{ filemtime(public_path('build/js/pages/project-map-monitoring.js')) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var mapElement = document.getElementById('sensor-cluster-map');

        if (!mapElement || typeof L === 'undefined') {
            return;
        }

        var clusters = @json($mapClusters ?? []);
        var sensorPoints = @json($mapSensors ?? []);
        var warningStations = @json($mapWarningStations ?? []);
        var initialAlertActive = @json($dashboardAlertActive ?? false);
        var mapDataUrl = @json(route('dashboard.projects.map-data', $project, false));
        var hasAppliedInitialFocus = false;
        var latestMapData = {
            clusters: clusters,
            sensors: sensorPoints,
            warningStations: warningStations,
            alert_active: initialAlertActive
        };
        var fitFilteredButton = document.getElementById('map-fit-filtered');
        // Match the monitoring zoom range used by the CFPE map.
        var mapMinZoom = 12;
        var mapMaxZoom = 16;
        var defaultMapCenter = [-2.6, 118.0];
        var defaultMapZoom = mapMinZoom;


        var statusColors = {
            Normal: '#34c38f',
            Danger: '#f46a6a',
            Siap: '#34c38f',
            Waspada: '#f1b44c',
            Pengujian: '#50a5f1',
            Bahaya: '#f46a6a',
            Awas: '#f46a6a',
            Siaga: '#f46a6a'
        };

        function escapeHtml(value) {
            return String(value ?? '-')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function isDanger(item) {
            return Boolean(item && (
                item.is_danger ||
                item.status === 'Danger' ||
                item.status === 'Bahaya' ||
                item.status === 'Awas' ||
                item.status === 'Siaga' ||
                item.alert_level === 'Awas' ||
                item.alert_level === 'Siaga'
            ));
        }

        function dangerPopup(title, bodyHtml) {
            return '<div class="map-danger-popup">' +
                '<div class="danger-popup-head"><i class="bx bxs-error"></i><span>BAHAYA</span></div>' +
                '<strong>' + escapeHtml(title) + '</strong>' +
                bodyHtml +
            '</div>';
        }

        function sensorReadingHtml(sensor) {
            var parameterValues = Array.isArray(sensor.parameter_values) ? sensor.parameter_values : [];
            var multiParameterHtml = parameterValues.length
                ? '<div class="mt-2"><strong>Multi Parameter:</strong><br>' + parameterValues.map(function (item) {
                    var label = item.label || item.parameter || '-';
                    var value = item.value_text || item.value || '-';

                    return '<span class="badge bg-info-subtle text-info border border-info-subtle me-1 mb-1">' +
                        escapeHtml(label) + ': ' + escapeHtml(value) +
                    '</span>';
                }).join('') + '</div>'
                : '';

            return '<div class="map-danger-reading">' +
                '<div><strong>Current Value :</strong> ' + escapeHtml(sensor.value || '-') + '</div>' +
                '<div><strong>Threshold:</strong> ' + escapeHtml(sensor.threshold || '-') + '</div>' +
                '<div><strong>Alert:</strong> ' + escapeHtml(sensor.alert_level || sensor.status || '-') + '</div>' +
                '<div><strong>Update:</strong> ' + escapeHtml(sensor.last_seen || '-') + '</div>' +
                multiParameterHtml +
            '</div>';
        }

        function markerLabelValue(sensor) {
            var values = Array.isArray(sensor.parameter_values) ? sensor.parameter_values : [];

            if (values.length) {
                return values.slice(0, 2).map(function (item) {
                    return item.value_text || item.value || '-';
                }).join(', ');
            }

            return sensor.value || sensor.status || '-';
        }

        function allMapItems(data) {
            return []
                .concat(data.clusters || [])
                .concat(data.sensors || [])
                .concat(data.warningStations || []);
        }

        function hasCoordinate(item) {
            return item.lat != null && item.lng != null &&
                Number.isFinite(Number(item.lat)) && Number.isFinite(Number(item.lng)) &&
                Math.abs(Number(item.lat)) <= 90 && Math.abs(Number(item.lng)) <= 180;
        }

        function boundsFromItems(items) {
            var latLngs = items
                .filter(hasCoordinate)
                .map(function (item) {
                    return [Number(item.lat), Number(item.lng)];
                });

            return latLngs.length ? L.latLngBounds(latLngs) : null;
        }

        function applyMapFocus(data) {
            if (hasAppliedInitialFocus) {
                return;
            }

            var bounds = boundsFromItems(allMapItems(data));
            var spatialBounds = spatialLayer.getBounds();
            if (spatialBounds.isValid()) {
                bounds = bounds ? bounds.extend(spatialBounds) : spatialBounds;
            }
            if (!bounds || !bounds.isValid()) {
                return;
            }

            map.fitBounds(bounds.pad(0.15), {
                paddingTopLeft: [40, 90],
                paddingBottomRight: [40, Math.min(window.innerHeight / 2, document.getElementById('map-monitor-panel').offsetHeight + 40)],
                maxZoom: mapMinZoom
            });
            hasAppliedInitialFocus = true;
        }

        function sensorDisplayPoint(sensor, indexByCoordinate) {
            var key = [
                Number(sensor.lat || 0).toFixed(5),
                Number(sensor.lng || 0).toFixed(5)
            ].join(',');
            var index = indexByCoordinate[key] || 0;

            indexByCoordinate[key] = index + 1;

            if (index === 0) {
                return [sensor.lat, sensor.lng];
            }

            var angle = ((index - 1) % 8) * (Math.PI / 4);
            var ring = Math.floor((index - 1) / 8) + 1;
            var offset = 0.00045 * ring;

            return [
                Number(sensor.lat) + (Math.sin(angle) * offset),
                Number(sensor.lng) + (Math.cos(angle) * offset)
            ];
        }

        var map = L.map('sensor-cluster-map', {
            scrollWheelZoom: true,
            minZoom: mapMinZoom,
            maxZoom: mapMaxZoom
        }).setView(defaultMapCenter, defaultMapZoom);

        L.control.scale({
            imperial: false,
            metric: true,
            position: 'bottomleft'
        }).addTo(map);

        var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            minZoom: mapMinZoom,
            maxZoom: mapMaxZoom,
            attribution: '&copy; OpenStreetMap contributors'
        });
        var topoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            minZoom: mapMinZoom,
            maxZoom: mapMaxZoom,
            attribution: '&copy; OpenStreetMap contributors, SRTM | OpenTopoMap'
        });
        var esriTopoLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
            minZoom: mapMinZoom,
            maxZoom: mapMaxZoom,
            attribution: 'Tiles &copy; Esri'
        });
        var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            minZoom: mapMinZoom,
            maxZoom: mapMaxZoom,
            attribution: 'Tiles &copy; Esri'
        });
        var hillshadeLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Elevation/World_Hillshade/MapServer/tile/{z}/{y}/{x}', {
            minZoom: mapMinZoom,
            maxZoom: mapMaxZoom,
            opacity: 0.36,
            attribution: 'Hillshade &copy; Esri'
        });

        topoLayer.addTo(map);
        hillshadeLayer.addTo(map);

        var clusterLayer = L.layerGroup().addTo(map);
        var sensorLayer = L.layerGroup().addTo(map);
        var warningLayer = L.layerGroup().addTo(map);
        var spatialLayer = L.featureGroup().addTo(map);
        var tdeLayer = L.layerGroup().addTo(map);
        var monitoring = new ProjectMapMonitoring({
            panel: document.getElementById('map-monitor-panel'),
            sirenUrl: @json(asset('sound/sirene.mp3')),
            tdeUrl: @json(route('dashboard.projects.tde-data', $project, false)),
            onTdeData: function (stations, presentation) {
                tdeLayer.clearLayers();
                stations.filter(hasCoordinate).forEach(function (station) {
                    var result = presentation(station.tde);
                    L.circleMarker([Number(station.lat), Number(station.lng)], {
                        radius: 9, color: '#ffffff', weight: 2,
                        fillColor: result.tone === 'info' ? '#1849a9' : '#b7791f', fillOpacity: 0.95
                    }).bindTooltip('TDE · ' + escapeHtml(station.station_code), { direction: 'top', offset: [0, -10] })
                        .bindPopup('<strong>TDE · ' + escapeHtml(station.station_code) + '</strong><br>' +
                            escapeHtml(result.summary) + '<br>Kondisi: ' + escapeHtml(result.condition) +
                            '<br>Pola: ' + escapeHtml(result.pattern) + '<br>Perkiraan waktu: ' + escapeHtml(result.estimate))
                        .addTo(tdeLayer);
                });
            }
        });
        window.addEventListener('pagehide', function () { monitoring.destroy(); });
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                monitoring.destroyed = false;
                monitoring.start();
            }
        });

        function renderMapData(data) {
            latestMapData = data;

            var visibleData = data;
            var clusters = visibleData.clusters || [];
            var sensorPoints = visibleData.sensors || [];
            var warningStations = visibleData.warningStations || [];

            monitoring.updateSensors(data);
            clusterLayer.clearLayers();
            sensorLayer.clearLayers();
            warningLayer.clearLayers();

            clusters.forEach(function (cluster) {
                var color = isDanger(cluster) ? '#f46a6a' : (statusColors[cluster.status] || '#34c38f');
                var clusterDanger = isDanger(cluster);

                L.circle([cluster.lat, cluster.lng], {
                    radius: clusterDanger ? 90000 : 22000,
                    color: color,
                    fillColor: color,
                    fillOpacity: clusterDanger ? 0.2 : 0.12,
                    weight: clusterDanger ? 4 : 2,
                    className: clusterDanger ? 'danger-map-radius' : ''
                }).addTo(clusterLayer);

                L.circleMarker([cluster.lat, cluster.lng], {
                    radius: clusterDanger ? 12 : 9,
                    color: '#ffffff',
                    fillColor: color,
                    fillOpacity: 1,
                    weight: 2
                }).bindPopup(
                    '<strong>' + escapeHtml(cluster.name) + '</strong>' +
                    '<br>Provinsi: ' + escapeHtml(cluster.province) +
                    '<br>Kab/Kota: ' + escapeHtml(cluster.city) +
                    '<br>Ancaman: ' + escapeHtml(cluster.hazard) +
                    '<br>Sensor: ' + escapeHtml(cluster.sensors) +
                    '<br>Stasiun Peringatan: ' + escapeHtml(cluster.warnings) +
                    '<br>Status: ' + escapeHtml(cluster.status)
                ).addTo(clusterLayer);
            });

            var sensorIndexByCoordinate = {};
            sensorPoints.filter(hasCoordinate).forEach(function (sensor) {
                var sensorDanger = isDanger(sensor);
                var color = sensorDanger ? '#f46a6a' : (statusColors[sensor.status] || '#50a5f1');
                var statusClass = sensorDanger
                    ? 'danger'
                    : (sensor.status === 'Waspada' ? 'warning' : 'live');
                var displayPoint = sensorDisplayPoint(sensor, sensorIndexByCoordinate);
                var labelValue = markerLabelValue(sensor);
                var sensorIcon = L.divIcon({
                    className: 'sensor-live-icon' + (sensorDanger ? ' sensor-live-icon-danger' : ''),
                    html: '<span class="sensor-map-marker-wrap">' +
                        '<span class="sensor-pulse-marker ' + statusClass + '" style="--sensor-color: ' + color + ';"></span>' +
                        '<span class="sensor-map-label ' + (sensorDanger ? 'danger' : '') + '">' +
                            escapeHtml(sensor.name || '-') +
                            '<small>' + escapeHtml(labelValue) + '</small>' +
                        '</span>' +
                    '</span>',
                    iconSize: [1, 1],
                    iconAnchor: [0, 0],
                    popupAnchor: [0, -14]
                });
                var sensorPopupBody =
                    '<br>Jenis: ' + escapeHtml(sensor.type) +
                    '<br>Parameter: ' + escapeHtml(sensor.parameter) +
                    '<br>Stasiun: ' + escapeHtml(sensor.station) +
                    '<br>Warning Station: ' + escapeHtml(sensor.warning_station) +
                    '<br>Provinsi: ' + escapeHtml(sensor.province) +
                    '<br>Kab/Kota: ' + escapeHtml(sensor.city) +
                    '<br>Status: ' + escapeHtml(sensor.status) +
                    sensorReadingHtml(sensor);

                if (sensorDanger) {
                    L.circle(displayPoint, {
                        radius: 120000,
                        color: '#f46a6a',
                        fillColor: '#f46a6a',
                        fillOpacity: 0.22,
                        weight: 4,
                        className: 'danger-map-radius'
                    }).addTo(sensorLayer);
                }

                L.marker(displayPoint, {
                    icon: sensorIcon,
                    title: sensor.name
                }).bindPopup(sensorDanger
                    ? dangerPopup(sensor.name, sensorPopupBody)
                    : '<strong>' + escapeHtml(sensor.name) + '</strong>' + sensorPopupBody
                ).addTo(sensorLayer);
            });

            warningStations.forEach(function (station) {
                var stationDanger = Boolean(station.is_danger);
                var color = stationDanger ? '#f46a6a' : (statusColors[station.status] || '#f1b44c');
                var warningReadings = '';

                (station.danger_sensors || []).forEach(function (sensor) {
                    warningReadings += sensorReadingHtml(sensor)
                        .replace('<div class="map-danger-reading">', '<div class="map-danger-reading"><strong>' + escapeHtml(sensor.name) + '</strong>');
                });

                var stationPopupBody =
                    '<br>Provinsi: ' + escapeHtml(station.province) +
                    '<br>Kab/Kota: ' + escapeHtml(station.city) +
                    '<br>Status: ' + escapeHtml(station.status) +
                    '<br>Public Warning: ' + (station.public_warning_enabled ? 'Aktif' : 'Standby') +
                    '<br>ACK: ' + escapeHtml(station.ack_response) +
                    (warningReadings || '<div class="map-danger-reading">Belum ada bacaan sensor bahaya.</div>');

                if (stationDanger) {
                    L.circle([station.lat, station.lng], {
                        radius: 150000,
                        color: '#f46a6a',
                        fillColor: '#f46a6a',
                        fillOpacity: 0.24,
                        weight: 5,
                        className: 'danger-map-radius'
                    }).addTo(warningLayer);
                }

                var warningIcon = stationDanger
                    ? L.divIcon({
                        className: 'sensor-live-icon',
                        html: '<span class="danger-warning-icon"><i class="bx bxs-error"></i></span>',
                        iconSize: [60, 60],
                        iconAnchor: [30, 30],
                        popupAnchor: [0, -26]
                    })
                    : undefined;
                var warningMarkerOptions = {
                    title: station.name
                };

                if (warningIcon) {
                    warningMarkerOptions.icon = warningIcon;
                }

                L.marker([station.lat, station.lng], warningMarkerOptions).bindPopup(stationDanger
                    ? dangerPopup(station.name, stationPopupBody)
                    : '<strong>' + escapeHtml(station.name) + '</strong>' + stationPopupBody
                ).addTo(warningLayer);

                L.circleMarker([station.lat, station.lng], {
                    radius: stationDanger ? 16 : 11,
                    color: color,
                    fillColor: stationDanger ? color : '#ffffff',
                    fillOpacity: stationDanger ? 0.45 : 0.2,
                    weight: stationDanger ? 4 : 3,
                    className: stationDanger ? 'danger-map-radius' : ''
                }).addTo(warningLayer);
            });

            applyMapFocus(visibleData);
        }

        L.control.layers({
            'Terrain + Kontur': topoLayer,
            'Topographic Relief': esriTopoLayer,
            'OpenStreetMap': osmLayer,
            'Satellite': satelliteLayer
        }, {
            'Efek Terrain 3D / Hillshade': hillshadeLayer,
            'Klaster': clusterLayer,
            'Sensor Pemantauan': sensorLayer,
            'Stasiun Peringatan': warningLayer,
            'Koridor & Route': spatialLayer,
            'Hasil TDE': tdeLayer
        }, {
            collapsed: true
        }).addTo(map);

        fitFilteredButton.addEventListener('click', function () {
            hasAppliedInitialFocus = false;
            applyMapFocus(latestMapData);
        });

        renderMapData(latestMapData);

        var mapRefreshInFlight = false;

        function refreshMapData() {
            if (mapRefreshInFlight) {
                return;
            }

            mapRefreshInFlight = true;
            var url = new URL(mapDataUrl, window.location.origin);
            url.searchParams.set('_', Date.now());

            fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-store'
                },
                cache: 'no-store'
            })
                .then(function (response) {
                    if (!response.ok) {
                        monitoring.showSensorError();
                        return null;
                    }

                    return response.json();
                })
                .then(function (data) {
                    if (data) {
                        renderMapData(data);
                    }
                })
                .catch(function () {
                    monitoring.showSensorError();
                })
                .finally(function () {
                    mapRefreshInFlight = false;
                });
        }

        monitoring.start();
        refreshMapData();
        setInterval(refreshMapData, 1000);

        // --- Spatial Overlay (Routes, Corridors, Information Layers from CFPE) ---
        var spatialLoaded = false;

        function loadSpatialOverlay() {
            if (spatialLoaded) return;
            spatialLoaded = true;

            fetch(@json(route('cfpe.map-data', ['project_id' => $project->id], false)), {
                headers: { 'Accept': 'application/json' }
            })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;

                // Draw corridors
                (data.corridors || []).forEach(function (corridor) {
                    if (!corridor.path_coordinates || !corridor.path_coordinates.length) return;
                    var coords = corridor.path_coordinates;
                    if (!Array.isArray(coords[0])) return;

                    var lines = [];
                    if (Array.isArray(coords[0][0])) {
                        lines = coords;
                    } else if (typeof coords[0][0] === 'number') {
                        lines = [coords];
                    }

                    lines.forEach(function (line) {
                        var latLngs = line.filter(function (c) { return Array.isArray(c) && c.length >= 2; })
                            .map(function (c) { return [c[1], c[0]]; });
                        if (latLngs.length >= 2) {
                            L.polyline(latLngs, { color: '#0D47A1', weight: 3, opacity: 0.7 })
                                .bindPopup('<strong>Corridor: ' + escapeHtml(corridor.name) + '</strong>')
                                .addTo(spatialLayer);
                        }
                    });
                });

                // Draw CFPE routes
                (data.routes || []).forEach(function (route) {
                    if (!route.path_coordinates || !route.path_coordinates.length) return;
                    if (route.route_type === 'monitoring_corridor') return; // skip, already drawn as corridor
                    var coords = route.path_coordinates;
                    if (!Array.isArray(coords[0])) return;

                    var lines = [];
                    if (Array.isArray(coords[0][0])) {
                        lines = coords;
                    } else if (typeof coords[0][0] === 'number') {
                        lines = [coords];
                    }

                    lines.forEach(function (line) {
                        var latLngs = line.filter(function (c) { return Array.isArray(c) && c.length >= 2; })
                            .map(function (c) { return [c[1], c[0]]; });
                        if (latLngs.length >= 2) {
                            L.polyline(latLngs, { color: '#e74c3c', weight: 2, opacity: 0.8, dashArray: '6, 4' })
                                .bindPopup('<strong>Route: ' + escapeHtml(route.name) + '</strong>')
                                .addTo(spatialLayer);
                        }
                    });

                    // BM points
                    (route.points || []).forEach(function (p) {
                        if (!p.latitude || !p.longitude) return;
                        L.circleMarker([p.latitude, p.longitude], {
                            radius: 4, fillColor: '#e74c3c', color: '#fff', weight: 1, fillOpacity: 0.8
                        }).bindPopup('<strong>' + escapeHtml(p.point_code) + '</strong>' +
                            (p.chainage ? '<br>Chainage: ' + p.chainage.toFixed(0) + ' m' : ''))
                        .addTo(spatialLayer);
                    });
                });

                // Draw information layers
                (data.information_layers || []).forEach(function (layer) {
                    if (!layer.layer_payload || !layer.layer_payload.features) return;
                    var color = layer.style_color || '#4CAF50';
                    var layerName = (layer.name || '').toLowerCase();

                    try {
                        L.geoJSON(layer.layer_payload, {
                            style: function () {
                                var weight = 2, fillOpacity = 0.1, dashArray = null;
                                if (layerName.indexOf('contour') >= 0) { weight = 1; fillOpacity = 0; color = '#8D6E63'; dashArray = '2,2'; }
                                else if (layerName.indexOf('batas') >= 0) { weight = 1.5; fillOpacity = 0.03; dashArray = '5,3'; }
                                else if (layerName.indexOf('sungai') >= 0) { weight = 1.5; fillOpacity = 0.08; }
                                else if (layerName.indexOf('perimeter') >= 0) { weight = 3; fillOpacity = 0.12; color = '#FF0000'; }
                                else if (layerName.indexOf('permukiman') >= 0) { weight = 1; fillOpacity = 0.15; }
                                return { color: color, weight: weight, opacity: 0.6, fillColor: color, fillOpacity: fillOpacity, dashArray: dashArray };
                            },
                            pointToLayer: function (feature, latlng) {
                                var icon = '📍';
                                if (layerName.indexOf('kesehatan') >= 0) icon = '🏥';
                                else if (layerName.indexOf('pendidikan') >= 0) icon = '🏫';
                                else if (layerName.indexOf('puncak') >= 0) icon = '🌋';
                                return L.marker(latlng, {
                                    icon: L.divIcon({ html: '<span style="font-size:14px">' + icon + '</span>', className: '', iconSize: [18, 18], iconAnchor: [9, 9] })
                                });
                            },
                            onEachFeature: function (feature, fl) {
                                var props = feature.properties || {};
                                fl.bindPopup('<strong>' + escapeHtml(layer.name) + '</strong>' + (props.NAMOBJ ? '<br>' + escapeHtml(props.NAMOBJ) : ''));
                            }
                        }).addTo(spatialLayer);
                    } catch (e) {}
                });

                // Draw monitoring stations with tower icon
                (data.monitoring_stations || []).forEach(function (station) {
                    if (!station.latitude || !station.longitude) return;
                    L.marker([station.latitude, station.longitude], {
                        icon: L.divIcon({
                            html: '<span style="font-size:22px;filter:drop-shadow(0 2px 2px rgba(0,0,0,.3))">🗼</span>',
                            className: '',
                            iconSize: [26, 26],
                            iconAnchor: [13, 13]
                        })
                    }).bindPopup(
                        '<strong>📡 Monitoring Station</strong><br>' +
                        escapeHtml(station.station_code) + '<br>' +
                        escapeHtml(station.name) + '<br>' +
                        '<span class="badge bg-info">' + escapeHtml(station.status || 'Active') + '</span>'
                    ).addTo(spatialLayer);
                });

                // Draw warning stations with siren icon
                (data.warning_stations || []).forEach(function (station) {
                    if (!station.latitude || !station.longitude) return;
                    L.marker([station.latitude, station.longitude], {
                        icon: L.divIcon({
                            html: '<span style="font-size:22px;filter:drop-shadow(0 2px 2px rgba(0,0,0,.3))">🚨</span>',
                            className: '',
                            iconSize: [26, 26],
                            iconAnchor: [13, 13]
                        })
                    }).bindPopup(
                        '<strong>🚨 Warning Station</strong><br>' +
                        escapeHtml(station.station_code) + '<br>' +
                        escapeHtml(station.name) + '<br>' +
                        '<span class="badge bg-danger">' + escapeHtml(station.status || 'Active') + '</span>'
                    ).addTo(spatialLayer);
                });
                applyMapFocus(latestMapData);
            })
            .catch(function () {});
        }

        // Load spatial data after a short delay
        setTimeout(loadSpatialOverlay, 2000);

    });
</script>

</body>
</html>
