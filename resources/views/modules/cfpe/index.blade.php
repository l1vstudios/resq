@extends('layouts.master')

@section('title') CFPE - Geospatial Workspace @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/leaflet/leaflet.css') }}" rel="stylesheet" type="text/css" />
    <style>
        #cfpe-map {
            width: 100%;
            height: 500px;
            border-radius: 0.25rem;
            z-index: 1;
            background:
                linear-gradient(145deg, rgba(10, 42, 71, .12), rgba(255,255,255,.04)),
                #eaf3ed;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,.8),
                inset 0 -18px 40px rgba(7,31,73,.08),
                0 18px 36px rgba(7,31,73,.10);
        }
        #cfpe-map .leaflet-tile-pane {
            filter: saturate(1.08) contrast(1.06);
        }
        #cfpe-map .leaflet-overlay-pane {
            filter: drop-shadow(0 5px 4px rgba(7,31,73,.22));
        }
        .cfpe-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 0.5rem 0;
        }
        .cfpe-legend-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.8rem;
            cursor: pointer;
            padding: 3px 6px;
            border-radius: 4px;
            transition: background .2s, opacity .2s;
        }
        .cfpe-legend-item:hover {
            background: rgba(0, 0, 0, 0.08);
        }
        .cfpe-legend-toggle {
            margin: 0 2px 0 0;
        }
        .cfpe-legend-item.is-hidden {
            opacity: 0.48;
        }
        .cfpe-legend-color {
            width: 24px;
            height: 4px;
            border-radius: 2px;
        }
        .cfpe-legend-color-line {
            width: 24px;
            height: 3px;
            border-radius: 2px;
        }
        .eta-table th, .eta-table td {
            white-space: nowrap;
            font-size: 0.85rem;
        }
        .cfpe-spinner {
            display: none;
        }
        .cfpe-spinner.active {
            display: inline-block;
        }
        .import-results {
            max-height: 200px;
            overflow-y: auto;
        }
        .leaflet-div-icon-transparent {
            background: transparent;
            border: none;
            text-align: center;
        }
        .map-overlay-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            display: none;
        }
        .map-overlay-loading.active {
            display: block;
        }
        .map-container {
            position: relative;
        }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Sentinel @endslot
        @slot('title') CFPE - Geospatial Workspace @endslot
    @endcomponent

    {{-- Map Section --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Geospatial Map</h4>
                        <span class="badge bg-info-subtle text-info">3D Terrain / Contour GIS</span>
                    </div>
                    <div class="map-container">
                        <div id="cfpe-map"></div>
                        <div class="map-overlay-loading" id="map-loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="cfpe-legend mt-3 p-2 border rounded bg-light" id="cfpe-legend">
                        <small class="text-muted">Legend will appear after data is loaded...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Imported Data Lists --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bx bx-git-branch me-1 text-danger"></i> Route CFPE (dari CSV)</h5>
                    <div class="table-responsive" id="route-list-container">
                        <table class="table table-sm table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Kode Route</th>
                                    <th>Nama</th>
                                    <th>Koridor</th>
                                    <th>Panjang Total</th>
                                    <th>Jumlah Titik BM</th>
                                </tr>
                            </thead>
                            <tbody id="route-list-body">
                                <tr><td colspan="6" class="text-muted text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <nav class="mt-2" id="route-pagination"></nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bx bx-layer me-1 text-primary"></i> GPKG Layers (Corridor & Information Layer)</h5>
                    <div class="table-responsive" id="gpkg-list-container">
                        <table class="table table-sm table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Layer</th>
                                    <th>Tipe</th>
                                    <th>Jumlah Objek</th>
                                    <th>Warna</th>
                                </tr>
                            </thead>
                            <tbody id="gpkg-list-body">
                                <tr><td colspan="5" class="text-muted text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <nav class="mt-2" id="gpkg-pagination"></nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Configuration & Results --}}
    <div class="row">
        {{-- CFPE Configuration Panel --}}
        <div class="col-xl-4 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">CFPE Configuration</h4>
                    <form id="cfpe-config-form">
                        <div class="mb-3">
                            <label for="cfpe-project" class="form-label">Project</label>
                            <select id="cfpe-project" class="form-select">
                                <option value="">-- Select Project --</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cfpe-route" class="form-label">Route</label>
                            <select id="cfpe-route" class="form-select" disabled>
                                <option value="">-- Select Route --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cfpe-reference-bm" class="form-label">Reference BM</label>
                            <select id="cfpe-reference-bm" class="form-select" disabled>
                                <option value="">-- Select Reference BM --</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Offset Direction</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="offset_direction" id="offset-upstream" value="upstream">
                                    <label class="form-check-label" for="offset-upstream">Upstream</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="offset_direction" id="offset-downstream" value="downstream" checked>
                                    <label class="form-check-label" for="offset-downstream">Downstream</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="cfpe-offset-distance" class="form-label">Offset Distance (m)</label>
                            <input type="number" id="cfpe-offset-distance" class="form-control" value="0" min="0" step="0.1">
                        </div>

                        <div class="mb-3">
                            <label for="cfpe-ground-zero" class="form-label">Station Ground Zero Chainage (m)</label>
                            <input type="number" id="cfpe-ground-zero" class="form-control" readonly placeholder="Auto-calculated">
                            <small class="text-muted">BM chainage ± offset distance</small>
                        </div>

                        <div class="mb-3">
                            <label for="cfpe-flow-velocity" class="form-label">Flow Velocity (m/s)</label>
                            <input type="number" id="cfpe-flow-velocity" class="form-control" value="1.5" min="0.01" step="0.01">
                        </div>

                        <div class="mb-3">
                            <label for="cfpe-uncertainty" class="form-label">Uncertainty Factor (±%)</label>
                            <input type="number" id="cfpe-uncertainty" class="form-control" value="0.30" min="0" max="1" step="0.01">
                            <small class="text-muted">0.30 = ±30%</small>
                        </div>

                        <button type="button" id="cfpe-calculate-btn" class="btn btn-primary w-100" disabled>
                            <span class="cfpe-spinner spinner-border spinner-border-sm me-1" id="calc-spinner" role="status"></span>
                            <i class="bx bx-calculator me-1"></i> Calculate ETA
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ETA Results Table --}}
        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">ETA Results</h4>
                        <span class="badge bg-info" id="results-count" style="display:none;">0 points</span>
                    </div>

                    <div id="eta-no-results" class="text-center text-muted py-4">
                        <i class="bx bx-info-circle font-size-24 d-block mb-2"></i>
                        Configure parameters and click Calculate to see ETA results.
                    </div>

                    <div class="table-responsive" id="eta-results-container" style="display:none;">
                        <table class="table table-hover table-striped table-sm eta-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference Point</th>
                                    <th>BM ID</th>
                                    <th class="text-end">Chainage (m)</th>
                                    <th class="text-end">Distance (m)</th>
                                    <th class="text-end">Basic ETA (min)</th>
                                    <th class="text-end">ETA Range (min)</th>
                                </tr>
                            </thead>
                            <tbody id="eta-results-body">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- CSV Import Section --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <a class="text-dark" data-bs-toggle="collapse" href="#csvImportCollapse" role="button" aria-expanded="false" aria-controls="csvImportCollapse">
                            <i class="bx bx-import me-1"></i> CSV Route Import
                            <i class="bx bx-chevron-down float-end"></i>
                        </a>
                    </h5>
                </div>
                <div class="collapse" id="csvImportCollapse">
                    <div class="card-body">
                        <form id="csv-import-form" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="import-project" class="form-label">Project</label>
                                    <select id="import-project" class="form-select" required>
                                        <option value="">-- Select Project --</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="import-workspace" class="form-label">Workspace (optional)</label>
                                    <select id="import-workspace" class="form-select">
                                        <option value="">-- None --</option>
                                        @foreach ($projects as $project)
                                            @foreach ($project->workspaces as $workspace)
                                                <option value="{{ $workspace->id }}" data-project="{{ $project->id }}">
                                                    {{ $workspace->name }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="import-csv-file" class="form-label">CSV File(s)</label>
                                <input type="file" id="import-csv-file" class="form-control" accept=".csv,.txt" multiple required>
                                <small class="text-muted">Pilih satu atau lebih file CSV. Format: semicolon-delimited (id;Route;ID_CFPE;Chaniage;...)</small>
                            </div>
                            <button type="submit" id="import-btn" class="btn btn-success">
                                <span class="cfpe-spinner spinner-border spinner-border-sm me-1" id="import-spinner" role="status"></span>
                                <i class="bx bx-upload me-1"></i> Import CSV
                            </button>
                        </form>

                        <div id="import-results" class="mt-3 import-results" style="display:none;">
                            <div class="alert mb-0" id="import-alert"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- GPKG Import Section --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <a class="text-dark" data-bs-toggle="collapse" href="#gpkgImportCollapse" role="button" aria-expanded="false" aria-controls="gpkgImportCollapse">
                            <i class="bx bx-layer me-1"></i> GPKG Import (Corridor & Information Layer)
                            <i class="bx bx-chevron-down float-end"></i>
                        </a>
                    </h5>
                </div>
                <div class="collapse" id="gpkgImportCollapse">
                    <div class="card-body">
                        <form id="gpkg-import-form" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="gpkg-import-type" class="form-label">Import Type</label>
                                    <select id="gpkg-import-type" class="form-select" required>
                                        <option value="corridor">Monitoring Corridor</option>
                                        <option value="information_layer">Information Layer</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="gpkg-project" class="form-label">Project</label>
                                    <select id="gpkg-project" class="form-select" required>
                                        <option value="">-- Select Project --</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="gpkg-workspace" class="form-label">Workspace</label>
                                    <select id="gpkg-workspace" class="form-select">
                                        <option value="">-- None --</option>
                                        @foreach ($projects as $project)
                                            @foreach ($project->workspaces as $workspace)
                                                <option value="{{ $workspace->id }}" data-project="{{ $project->id }}">
                                                    {{ $workspace->name }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="gpkg-file" class="form-label">GPKG File(s)</label>
                                    <input type="file" id="gpkg-file" class="form-control" accept=".gpkg" multiple required>
                                    <small class="text-muted">Pilih satu atau lebih file GeoPackage (.gpkg)</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="gpkg-layer-name" class="form-label">Layer Name (optional)</label>
                                    <input type="text" id="gpkg-layer-name" class="form-control" placeholder="Nama layer (auto-detect dari filename jika kosong)">
                                </div>
                            </div>
                            <button type="submit" id="gpkg-import-btn" class="btn btn-primary">
                                <span class="cfpe-spinner spinner-border spinner-border-sm me-1" id="gpkg-spinner" role="status"></span>
                                <i class="bx bx-layer-plus me-1"></i> Import GPKG
                            </button>
                        </form>

                        <div id="gpkg-results" class="mt-3" style="display:none;">
                            <div class="alert mb-0" id="gpkg-alert"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/leaflet/leaflet.js') }}"></script>
    <script>
    (function () {
        'use strict';

        // --- Constants ---
        const ROUTE_COLORS = ['#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'];
        const CFPE_MIN_ZOOM = 12;
        const CFPE_MAX_ZOOM = 16;
        const MAP_BOUNDS = {
            south: -8.371469032,
            north: -8.055529857,
            west: 112.783198121,
            east: 113.249208404
        };
        const INITIAL_MAP_BOUNDS = L.latLngBounds(
            [MAP_BOUNDS.south, MAP_BOUNDS.west],
            [MAP_BOUNDS.north, MAP_BOUNDS.east]
        );
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content
            || '{{ csrf_token() }}';

        // --- State ---
        let map, markersLayer, polylinesLayer;
        let routesData = @json($routes);
        let projectsData = @json($projects);
        let selectedPoints = [];
        let calculationResults = [];
        let latestMapData = null;
        let visibleLegendKeys = new Set();
        let legendVisibilityInitialized = false;

        // --- DOM Elements ---
        const $project = document.getElementById('cfpe-project');
        const $route = document.getElementById('cfpe-route');
        const $referenceBm = document.getElementById('cfpe-reference-bm');
        const $offsetDistance = document.getElementById('cfpe-offset-distance');
        const $groundZero = document.getElementById('cfpe-ground-zero');
        const $flowVelocity = document.getElementById('cfpe-flow-velocity');
        const $uncertainty = document.getElementById('cfpe-uncertainty');
        const $calculateBtn = document.getElementById('cfpe-calculate-btn');
        const $calcSpinner = document.getElementById('calc-spinner');
        const $etaBody = document.getElementById('eta-results-body');
        const $etaContainer = document.getElementById('eta-results-container');
        const $etaNoResults = document.getElementById('eta-no-results');
        const $resultsCount = document.getElementById('results-count');
        const $mapLoading = document.getElementById('map-loading');

        // --- Map Initialization ---
        function initMap() {
            map = L.map('cfpe-map', {
                zoomControl: true,
                scrollWheelZoom: true,
                minZoom: CFPE_MIN_ZOOM,
                maxZoom: CFPE_MAX_ZOOM,
                maxBounds: INITIAL_MAP_BOUNDS,
                maxBoundsViscosity: 0.9
            });

            // Terrain base layer (OpenTopoMap with contour lines)
            L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> & <a href="https://opentopomap.org">OpenTopoMap</a>',
                minZoom: CFPE_MIN_ZOOM,
                maxZoom: CFPE_MAX_ZOOM
            }).addTo(map);

            // Hillshade overlay for 3D depth effect
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Elevation/World_Hillshade/MapServer/tile/{z}/{y}/{x}', {
                minZoom: CFPE_MIN_ZOOM,
                maxZoom: CFPE_MAX_ZOOM,
                opacity: 0.34,
                attribution: 'Hillshade &copy; Esri'
            }).addTo(map);

            map.fitBounds(INITIAL_MAP_BOUNDS, { maxZoom: CFPE_MIN_ZOOM });

            markersLayer = L.layerGroup().addTo(map);
            polylinesLayer = L.layerGroup().addTo(map);
            corridorLayer = L.layerGroup().addTo(map);
            infoLayerGroup = L.layerGroup().addTo(map);

            // Terrain mode badge
            var terrainBadge = L.control({ position: 'topleft' });
            terrainBadge.onAdd = function () {
                var div = L.DomUtil.create('div');
                div.style.cssText = 'background:rgba(7,31,73,.88);border:1px solid rgba(255,255,255,.28);border-radius:8px;box-shadow:0 10px 22px rgba(7,31,73,.20);color:#fff;display:grid;gap:2px;padding:8px 10px;font-size:12px;';
                div.innerHTML = '<strong>GIS Terrain</strong><span style="opacity:.7;font-size:11px">Contour + Hillshade</span>';
                return div;
            };
            terrainBadge.addTo(map);

            drawInitialRoutes();
            setTimeout(loadMapData, 300);
        }

        function cfpeDivIcon(color, size) {
            var markerSize = size || 18;
            return L.divIcon({
                className: 'leaflet-div-icon-transparent',
                iconSize: [markerSize, markerSize],
                iconAnchor: [markerSize / 2, markerSize / 2],
                html: '<span style="display:block;width:' + markerSize + 'px;height:' + markerSize + 'px;border-radius:999px;background:' + color + ';border:3px solid #fff;box-shadow:0 8px 18px rgba(15,35,60,.24);"></span>'
            });
        }

        function relationRows(items, emptyText, formatter) {
            var rows = (items || []).map(formatter).filter(Boolean);
            if (!rows.length) {
                return '<div class="text-muted" style="font-size:12px;">' + escapeHtml(emptyText) + '</div>';
            }
            return '<div style="display:grid;gap:6px;margin-top:6px;">' + rows.join('') + '</div>';
        }

        function relationItem(title, subtitle, status, details) {
            return '<div style="border-top:1px solid #e8eef5;padding-top:6px;">' +
                '<div style="font-weight:700;color:#263238;">' + escapeHtml(title || '-') + '</div>' +
                '<div style="font-size:12px;color:#65758b;">' + escapeHtml(subtitle || '-') + '</div>' +
                (status ? '<div style="font-size:12px;color:#2563eb;">' + escapeHtml(status) + '</div>' : '') +
                (details ? '<div style="font-size:12px;color:#475569;margin-top:3px;">' + details + '</div>' : '') +
            '</div>';
        }

        function activePresetText(presets) {
            var rows = (presets || []).map(function (preset) {
                return [
                    preset.device_model || preset.profile_code || 'Preset',
                    preset.canonical_parameter || preset.source_parameter
                ].filter(Boolean).join(' - ');
            }).filter(Boolean);

            if (!rows.length) {
                return '';
            }

            return '<span style="font-weight:700;color:#071f49;">Preset aktif:</span> ' + escapeHtml(rows.join(', '));
        }

        function monitoringStationPopup(station) {
            return '<strong>Monitoring Station</strong><br>' +
                '<code>' + escapeHtml(station.station_code || '-') + '</code><br>' +
                escapeHtml(station.name || '-') +
                '<div style="margin-top:8px;font-weight:700;color:#071f49;">Warning Station Terikat</div>' +
                relationRows(station.warning_stations, 'Belum ada warning station terikat.', function (warning) {
                    return relationItem(
                        [warning.station_code, warning.name].filter(Boolean).join(' - '),
                        warning.zone_id ? 'Zone: ' + warning.zone_id : 'Warning Station',
                        [warning.status, warning.controller_status].filter(Boolean).join(' / ')
                    );
                });
        }

        function warningStationPopup(station) {
            var topology = relationRows(station.data_loggers, 'Belum ada data logger dan sensor terikat.', function (logger) {
                var sensors = relationRows(logger.sensors, 'Belum ada sensor pada data logger ini.', function (sensor) {
                    return relationItem(
                        [sensor.sensor_code, sensor.name].filter(Boolean).join(' - '),
                        [sensor.type, sensor.parameter].filter(Boolean).join(' / '),
                        [sensor.status, sensor.alert_level].filter(Boolean).join(' / '),
                        activePresetText(sensor.active_presets)
                    );
                });

                return '<div style="border-top:1px solid #dce7f3;padding-top:7px;">' +
                    '<div style="font-weight:800;color:#071f49;">Data Logger: ' + escapeHtml(logger.logger_code || '-') + '</div>' +
                    (logger.logger_status ? '<div style="font-size:12px;color:#65758b;">Status: ' + escapeHtml(logger.logger_status) + '</div>' : '') +
                    (activePresetText(logger.active_presets) ? '<div style="font-size:12px;color:#475569;margin-top:2px;">' + activePresetText(logger.active_presets) + '</div>' : '') +
                    '<div style="margin-left:10px;">' + sensors + '</div>' +
                '</div>';
            });

            return '<strong>Warning Station</strong><br>' +
                '<code>' + escapeHtml(station.station_code || '-') + '</code><br>' +
                escapeHtml(station.name || '-') +
                '<div style="margin-top:8px;font-weight:700;color:#071f49;">Topologi Data Logger & Sensor</div>' +
                topology;
        }

        function sensorPopup(sensor) {
            return '<strong>Sensor</strong><br>' +
                '<code>' + escapeHtml(sensor.sensor_code || '-') + '</code><br>' +
                escapeHtml(sensor.name || sensor.parameter || '-') +
                (sensor.type ? '<br>Type: ' + escapeHtml(sensor.type) : '') +
                (sensor.data_logger_code ? '<br>Data Logger: ' + escapeHtml(sensor.data_logger_code) : '') +
                (sensor.warning_station_code ? '<br>Warning Station: ' + escapeHtml(sensor.warning_station_code) : '') +
                (sensor.monitoring_station_code ? '<br>Monitoring Station: ' + escapeHtml(sensor.monitoring_station_code) : '') +
                (sensor.status || sensor.alert_level ? '<br>Status: ' + escapeHtml([sensor.status, sensor.alert_level].filter(Boolean).join(' / ')) : '');
        }

        function makeLegendKey(category, id) {
            return category + ':' + String(id || '').toLowerCase();
        }

        function renderMapLayers(data, shouldLockBounds) {
            if (!data) return;
            if (map) {
                map.closePopup();
            }

            var legendItems = [];
            legendItems = legendItems.concat(renderCorridors(data.corridors || []));
            legendItems = legendItems.concat(renderInformationLayers(data.information_layers || []));
            legendItems = legendItems.concat(renderRoutePolylines(data.routes || []));
            renderStations(data.monitoring_stations || [], data.warning_stations || [], data.sensors || []);
            if (shouldLockBounds) {
                lockMapToData(data);
            }
            buildLegend(legendItems);
        }

        function referencePointPopup(point, route) {
            return '<strong>' + escapeHtml(point.point_code || '-') + '</strong>' +
                (point.name ? '<br>' + escapeHtml(point.name) : '') +
                (point.bm_id ? '<br>BM: ' + escapeHtml(point.bm_id) : '') +
                (point.chainage != null ? '<br>Chainage: ' + formatNumber(point.chainage) + ' m' : '') +
                (point.segment_name ? '<br>Segment: ' + escapeHtml(point.segment_name) : '') +
                (route ? '<br>Route: ' + escapeHtml(route.route_code || route.name || '-') : '');
        }

        function saveMapPoint(type, id, latLng, onSaved) {
            if (!id) return;
            fetch('/cfpe/map-point', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    type: type,
                    id: id,
                    latitude: latLng.lat,
                    longitude: latLng.lng
                })
            })
            .then(handleResponse)
            .then(function (data) {
                if (typeof onSaved === 'function') {
                    onSaved(data);
                }
            })
            .catch(function (err) {
                showError('Gagal menyimpan koordinat: ' + err.message);
            });
        }

        function bindDraggablePoint(marker, type, item, popupBuilder) {
            marker.on('dragend', function () {
                var latLng = marker.getLatLng();
                item.latitude = latLng.lat;
                item.longitude = latLng.lng;
                saveMapPoint(type, item.id, latLng, function () {
                    if (typeof popupBuilder === 'function') {
                        marker.bindPopup(popupBuilder(item));
                    }
                });
            });
            return marker;
        }

        function appendPathCoordinates(coords, points) {
            if (!Array.isArray(coords) || !coords.length) return;
            if (Array.isArray(coords[0]) && typeof coords[0][0] === 'number') {
                coords.forEach(function (c) {
                    if (Array.isArray(c) && c.length >= 2) points.push([c[1], c[0]]);
                });
                return;
            }
            coords.forEach(function (child) {
                appendPathCoordinates(child, points);
            });
        }

        function lockMapToPoints(points) {
            var valid = (points || []).filter(function (point) {
                return Array.isArray(point) && isFinite(point[0]) && isFinite(point[1]);
            });
            if (!valid.length) {
                map.setMaxBounds(INITIAL_MAP_BOUNDS);
                return;
            }
            var bounds = L.latLngBounds(valid);
            var padded = bounds.pad(0.35);
            map.setMaxBounds(padded);
            map.fitBounds(bounds, { padding: [42, 42], maxZoom: CFPE_MIN_ZOOM });
        }

        function lockMapToData(data) {
            var points = [];
            (data.routes || []).forEach(function (route) {
                appendPathCoordinates(route.path_coordinates, points);
                (route.points || []).forEach(function (point) {
                    if (point.latitude && point.longitude) points.push([point.latitude, point.longitude]);
                });
            });
            (data.corridors || []).forEach(function (corridor) {
                appendPathCoordinates(corridor.path_coordinates, points);
            });
            (data.monitoring_stations || []).concat(data.warning_stations || []).forEach(function (station) {
                if (station.latitude && station.longitude) points.push([station.latitude, station.longitude]);
            });
            (data.sensors || []).forEach(function (sensor) {
                if (sensor.latitude && sensor.longitude) points.push([sensor.latitude, sensor.longitude]);
            });
            lockMapToPoints(points);
        }

        function renderStations(monitoringStations, warningStations, sensors) {
            // Monitoring stations - tower icon
            monitoringStations.forEach(function (station) {
                if (!station.latitude || !station.longitude) return;
                var marker = L.marker([station.latitude, station.longitude], {
                    draggable: true,
                    icon: L.divIcon({
                        html: '<span style="font-size:22px;filter:drop-shadow(0 2px 2px rgba(0,0,0,.3))">🗼</span>',
                        className: 'leaflet-div-icon-transparent',
                        iconSize: [26, 26],
                        iconAnchor: [13, 26]
                    })
                }).bindPopup(monitoringStationPopup(station)).addTo(corridorLayer);
                bindDraggablePoint(marker, 'monitoring_station', station, monitoringStationPopup);
            });

            // Warning stations - siren icon
            warningStations.forEach(function (station) {
                if (!station.latitude || !station.longitude) return;
                var marker = L.marker([station.latitude, station.longitude], {
                    draggable: true,
                    icon: L.divIcon({
                        html: '<span style="font-size:22px;filter:drop-shadow(0 2px 2px rgba(0,0,0,.3))">🚨</span>',
                        className: 'leaflet-div-icon-transparent',
                        iconSize: [26, 26],
                        iconAnchor: [13, 26]
                    })
                }).bindPopup(warningStationPopup(station)).addTo(corridorLayer);
                bindDraggablePoint(marker, 'warning_station', station, warningStationPopup);
            });

            // Sensors - small purple nodes
            (sensors || []).forEach(function (sensor) {
                if (!sensor.latitude || !sensor.longitude) return;
                var marker = L.marker([sensor.latitude, sensor.longitude], {
                    draggable: true,
                    icon: cfpeDivIcon('#7c3aed', 14)
                }).bindPopup(sensorPopup(sensor)).addTo(corridorLayer);
                bindDraggablePoint(marker, 'sensor', sensor, sensorPopup);
            });
        }

        function buildRouteList(routes) {
            var tbody = document.getElementById('route-list-body');
            var paginationEl = document.getElementById('route-pagination');
            if (!tbody) return;
            var cfpeRoutes = routes.filter(function (r) { return r.route_type === 'cfpe_corridor'; });
            if (cfpeRoutes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-muted text-center">Belum ada route CSV yang di-import.</td></tr>';
                if (paginationEl) paginationEl.innerHTML = '';
                return;
            }
            var PAGE_SIZE = 10;
            var currentPage = 1;

            function renderPage(page) {
                currentPage = page;
                var start = (page - 1) * PAGE_SIZE;
                var pageData = cfpeRoutes.slice(start, start + PAGE_SIZE);
                tbody.innerHTML = pageData.map(function (route, idx) {
                    var pointCount = route.points ? route.points.length : 0;
                    var length = route.total_length ? parseFloat(route.total_length).toLocaleString('id-ID', {maximumFractionDigits: 0}) + ' m' : '-';
                    return '<tr>' +
                        '<td>' + (start + idx + 1) + '</td>' +
                        '<td><code>' + escapeHtml(route.route_code) + '</code></td>' +
                        '<td>' + escapeHtml(route.name) + '</td>' +
                        '<td>' + escapeHtml(route.corridor_code || '-') + '</td>' +
                        '<td>' + length + '</td>' +
                        '<td><span class="badge bg-info">' + pointCount + ' titik</span></td>' +
                        '</tr>';
                }).join('');
                renderPagination(paginationEl, cfpeRoutes.length, PAGE_SIZE, currentPage, renderPage);
            }
            renderPage(1);
        }

        function buildGpkgList(corridors, layers) {
            var tbody = document.getElementById('gpkg-list-body');
            var paginationEl = document.getElementById('gpkg-pagination');
            if (!tbody) return;
            var allItems = [];

            corridors.forEach(function (c) {
                var numPoints = 0;
                if (c.path_coordinates && Array.isArray(c.path_coordinates)) {
                    if (Array.isArray(c.path_coordinates[0]) && Array.isArray(c.path_coordinates[0][0])) {
                        numPoints = c.path_coordinates[0].length;
                    } else if (Array.isArray(c.path_coordinates[0])) {
                        numPoints = c.path_coordinates.length;
                    }
                }
                allItems.push({
                    name: c.name || c.corridor_code,
                    icon: 'bx-git-branch text-primary',
                    type: 'Corridor',
                    typeBadge: 'bg-primary',
                    count: numPoints + ' titik koordinat',
                    color: '#1565C0'
                });
            });

            layers.forEach(function (l) {
                var featureCount = (l.layer_payload && l.layer_payload.features) ? l.layer_payload.features.length : 0;
                var typeLabel = (l.layer_type || 'overlay');
                var typeBadge = typeLabel === 'polygon' ? 'bg-success' : typeLabel === 'line' ? 'bg-warning text-dark' : 'bg-secondary';
                allItems.push({
                    name: l.name,
                    icon: 'bx-layer text-success',
                    type: typeLabel.charAt(0).toUpperCase() + typeLabel.slice(1),
                    typeBadge: typeBadge,
                    count: featureCount + ' objek',
                    color: l.style_color || '#4CAF50'
                });
            });

            if (allItems.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-muted text-center">Belum ada GPKG yang di-import.</td></tr>';
                if (paginationEl) paginationEl.innerHTML = '';
                return;
            }

            var PAGE_SIZE = 10;
            function renderPage(page) {
                var start = (page - 1) * PAGE_SIZE;
                var pageData = allItems.slice(start, start + PAGE_SIZE);
                tbody.innerHTML = pageData.map(function (item, idx) {
                    return '<tr>' +
                        '<td>' + (start + idx + 1) + '</td>' +
                        '<td><i class="bx ' + item.icon + ' me-1"></i>' + escapeHtml(item.name) + '</td>' +
                        '<td><span class="badge ' + item.typeBadge + '">' + escapeHtml(item.type) + '</span></td>' +
                        '<td>' + escapeHtml(item.count) + '</td>' +
                        '<td><span style="display:inline-block;width:16px;height:16px;border-radius:3px;background:' + item.color + ';border:1px solid rgba(0,0,0,.1)"></span></td>' +
                        '</tr>';
                }).join('');
                renderPagination(paginationEl, allItems.length, PAGE_SIZE, page, renderPage);
            }
            renderPage(1);
        }

        function renderPagination(container, totalItems, pageSize, currentPage, onPageClick) {
            if (!container) return;
            var totalPages = Math.ceil(totalItems / pageSize);
            if (totalPages <= 1) {
                container.innerHTML = '<small class="text-muted">Total: ' + totalItems + ' item</small>';
                return;
            }
            var html = '<ul class="pagination pagination-sm mb-0 justify-content-end">';
            html += '<li class="page-item' + (currentPage === 1 ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (currentPage - 1) + '">&laquo;</a></li>';
            for (var i = 1; i <= totalPages; i++) {
                html += '<li class="page-item' + (i === currentPage ? ' active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
            }
            html += '<li class="page-item' + (currentPage === totalPages ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (currentPage + 1) + '">&raquo;</a></li>';
            html += '</ul><small class="text-muted mt-1 d-block text-end">Total: ' + totalItems + ' item</small>';
            container.innerHTML = html;

            container.querySelectorAll('[data-page]').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    var page = parseInt(this.dataset.page);
                    if (page >= 1 && page <= totalPages) {
                        onPageClick(page);
                    }
                });
            });
        }

        function drawInitialRoutes() {
            // Initial routes are loaded via loadMapData() after map init
            // This function only sets up the legend placeholder
            return;
        }

        // --- Project / Route Selection ---
        $project.addEventListener('change', function () {
            const projectId = this.value;
            $route.innerHTML = '<option value="">-- Select Route --</option>';
            $referenceBm.innerHTML = '<option value="">-- Select Reference BM --</option>';
            $referenceBm.disabled = true;
            $calculateBtn.disabled = true;
            clearResults();

            if (!projectId) {
                $route.disabled = true;
                return;
            }

            // Filter routes for this project
            const project = projectsData.find(function (p) { return p.id == projectId; });
            const projectRoutes = project && project.reference_routes ? project.reference_routes : routesData;

            projectRoutes.forEach(function (route) {
                const opt = document.createElement('option');
                opt.value = route.id;
                opt.textContent = route.name || route.route_code;
                $route.appendChild(opt);
            });

            $route.disabled = false;
        });

        $route.addEventListener('change', function () {
            const routeId = this.value;
            $referenceBm.innerHTML = '<option value="">-- Loading... --</option>';
            $referenceBm.disabled = true;
            $calculateBtn.disabled = true;
            clearResults();

            if (!routeId) {
                $referenceBm.innerHTML = '<option value="">-- Select Reference BM --</option>';
                return;
            }

            // Fetch points from API
            showMapLoading(true);
            fetch('/cfpe/routes/' + routeId + '/points', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
            })
            .then(handleResponse)
            .then(function (data) {
                selectedPoints = data.points || [];
                $referenceBm.innerHTML = '<option value="">-- Select Reference BM --</option>';

                selectedPoints.forEach(function (point) {
                    const opt = document.createElement('option');
                    opt.value = point.id;
                    opt.textContent = (point.point_code || point.bm_id) + ' (Ch: ' + (point.chainage || 0).toFixed(1) + 'm)';
                    opt.dataset.chainage = point.chainage || 0;
                    $referenceBm.appendChild(opt);
                });

                $referenceBm.disabled = false;
                updateMapForRoute(selectedPoints);
            })
            .catch(function (err) {
                showError('Failed to load route points: ' + err.message);
                $referenceBm.innerHTML = '<option value="">-- Error loading --</option>';
            })
            .finally(function () {
                showMapLoading(false);
            });
        });

        $referenceBm.addEventListener('change', updateGroundZero);
        $offsetDistance.addEventListener('input', updateGroundZero);
        document.querySelectorAll('input[name="offset_direction"]').forEach(function (radio) {
            radio.addEventListener('change', updateGroundZero);
        });

        function updateGroundZero() {
            const selectedOpt = $referenceBm.options[$referenceBm.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) {
                $groundZero.value = '';
                $calculateBtn.disabled = true;
                return;
            }

            const chainage = parseFloat(selectedOpt.dataset.chainage) || 0;
            const offset = parseFloat($offsetDistance.value) || 0;
            const direction = document.querySelector('input[name="offset_direction"]:checked').value;

            const groundZero = direction === 'downstream'
                ? chainage + offset
                : chainage - offset;

            $groundZero.value = groundZero.toFixed(2);
            $calculateBtn.disabled = false;
        }

        // --- Map Update for Route Points ---
        function updateMapForRoute(points) {
            if (!markersLayer) return;
            markersLayer.clearLayers();

            if (!points || points.length === 0) return;

            const validPoints = points.filter(function (p) {
                return p.latitude && p.longitude;
            });

            validPoints.forEach(function (point) {
                const marker = L.marker([point.latitude, point.longitude], {
                    draggable: true,
                    icon: cfpeDivIcon('#e74c3c', 17)
                }).addTo(markersLayer);

                marker.bindPopup(referencePointPopup(point));
                bindDraggablePoint(marker, 'reference_point', point, referencePointPopup);
            });

            if (validPoints.length > 0) {
                lockMapToPoints(validPoints.map(function (p) {
                    return [p.latitude, p.longitude];
                }));
            }
        }

        // --- Calculate ETA ---
        $calculateBtn.addEventListener('click', function () {
            const routeId = $route.value;
            const groundZero = parseFloat($groundZero.value);
            const flowVelocity = parseFloat($flowVelocity.value);
            const uncertainty = parseFloat($uncertainty.value);
            const direction = document.querySelector('input[name="offset_direction"]:checked').value;

            if (!routeId || isNaN(groundZero) || isNaN(flowVelocity) || flowVelocity <= 0) {
                showError('Please fill in all required fields with valid values.');
                return;
            }

            $calculateBtn.disabled = true;
            $calcSpinner.classList.add('active');

            fetch('/cfpe/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    reference_route_id: routeId,
                    station_ground_zero_chainage: groundZero,
                    flow_velocity: flowVelocity,
                    uncertainty_factor: uncertainty,
                    propagation_direction: direction
                })
            })
            .then(handleResponse)
            .then(function (data) {
                calculationResults = data.results || [];
                renderResults(calculationResults);
            })
            .catch(function (err) {
                showError('Calculation failed: ' + err.message);
            })
            .finally(function () {
                $calculateBtn.disabled = false;
                $calcSpinner.classList.remove('active');
            });
        });

        function renderResults(results) {
            if (!results || results.length === 0) {
                clearResults();
                return;
            }

            $etaNoResults.style.display = 'none';
            $etaContainer.style.display = 'block';
            $resultsCount.style.display = 'inline';
            $resultsCount.textContent = results.length + ' point' + (results.length !== 1 ? 's' : '');

            $etaBody.innerHTML = '';
            results.forEach(function (row) {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + escapeHtml(row.point_code || '-') + '</td>' +
                    '<td>' + escapeHtml(row.bm_id || '-') + '</td>' +
                    '<td class="text-end">' + formatNumber(row.chainage) + '</td>' +
                    '<td class="text-end">' + formatNumber(row.distance) + '</td>' +
                    '<td class="text-end">' + formatNumber(row.basic_eta_minutes) + '</td>' +
                    '<td class="text-end">' + formatEtaRange(row.eta_min_minutes, row.eta_max_minutes) + '</td>';
                $etaBody.appendChild(tr);
            });

            // Highlight markers on map for calculated points
            highlightCalculatedPoints(results);
        }

        function highlightCalculatedPoints(results) {
            if (!results || results.length === 0) return;

            results.forEach(function (row) {
                if (row.latitude && row.longitude) {
                    L.circleMarker([row.latitude, row.longitude], {
                        radius: 9,
                        fillColor: '#f39c12',
                        color: '#c0392b',
                        weight: 2,
                        fillOpacity: 0.7
                    }).addTo(markersLayer).bindPopup(
                        '<strong>' + escapeHtml(row.point_code || '') + '</strong><br>' +
                        'ETA: ' + formatNumber(row.basic_eta_minutes) + ' min<br>' +
                        'Range: ' + formatEtaRange(row.eta_min_minutes, row.eta_max_minutes) + ' min'
                    );
                }
            });
        }

        function clearResults() {
            $etaNoResults.style.display = 'block';
            $etaContainer.style.display = 'none';
            $resultsCount.style.display = 'none';
            $etaBody.innerHTML = '';
            calculationResults = [];
        }

        // --- GPKG Import (Multi-file) ---
        document.getElementById('gpkg-import-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const fileInput = document.getElementById('gpkg-file');
            const projectId = document.getElementById('gpkg-project').value;
            const workspaceId = document.getElementById('gpkg-workspace').value;
            const importType = document.getElementById('gpkg-import-type').value;
            const layerName = document.getElementById('gpkg-layer-name').value;

            if (!fileInput.files.length || !projectId) {
                showGpkgResult('warning', 'Pilih file GPKG dan project.');
                return;
            }

            if (importType === 'corridor' && !workspaceId) {
                showGpkgResult('warning', 'Workspace diperlukan untuk import corridor.');
                return;
            }

            const files = Array.from(fileInput.files);
            const $btn = document.getElementById('gpkg-import-btn');
            const $spinner = document.getElementById('gpkg-spinner');
            $btn.disabled = true;
            $spinner.classList.add('active');
            showGpkgResult('info', 'Importing ' + files.length + ' file(s)...');

            var totalFeatures = 0, completed = 0, errors = [];

            function importGpkgNext(index) {
                if (index >= files.length) {
                    $btn.disabled = false;
                    $spinner.classList.remove('active');
                    if (errors.length > 0) {
                        showGpkgResult('warning', 'Selesai dengan error: ' + errors.join('; ') + ' | Total: ' + totalFeatures + ' features.');
                    } else {
                        showGpkgResult('success', 'Import selesai! ' + totalFeatures + ' features dari ' + files.length + ' file.');
                    }
                    fileInput.value = '';
                    setTimeout(function () { loadMapData(); }, 500);
                    return;
                }

                var formData = new FormData();
                formData.append('gpkg_file', files[index]);
                formData.append('project_id', projectId);
                formData.append('import_type', importType);
                if (workspaceId) formData.append('workspace_id', workspaceId);
                // Use individual filename as layer name if not specified
                var name = layerName || '';
                if (!name && importType === 'information_layer') {
                    name = files[index].name.replace('.gpkg', '');
                }
                if (name) formData.append('layer_name', name);

                showGpkgResult('info', 'Importing file ' + (index + 1) + '/' + files.length + ': ' + files[index].name + '...');

                fetch('/cfpe/import-gpkg', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: formData
                })
                .then(handleResponse)
                .then(function (data) {
                    totalFeatures += data.count || 0;
                })
                .catch(function (err) {
                    errors.push(files[index].name + ': ' + err.message);
                })
                .finally(function () {
                    importGpkgNext(index + 1);
                });
            }

            importGpkgNext(0);
        });

        function showGpkgResult(type, message) {
            var $results = document.getElementById('gpkg-results');
            var $alert = document.getElementById('gpkg-alert');
            $results.style.display = 'block';
            $alert.className = 'alert alert-' + type + ' mb-0';
            $alert.textContent = message;
        }

        // --- Load Map Data (corridors, routes, information layers) ---
        var corridorLayer, infoLayerGroup;

        function loadMapData() {
            if (!map) return; // Guard: map not initialized yet

            fetch('/cfpe/map-data', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
            })
            .then(handleResponse)
            .then(function (data) {
                console.log('Map data loaded:', (data.corridors||[]).length, 'corridors,', (data.routes||[]).length, 'routes,', (data.information_layers||[]).length, 'layers');
                latestMapData = data;
                if (!legendVisibilityInitialized) {
                    visibleLegendKeys.clear();
                    legendVisibilityInitialized = true;
                }
                renderMapLayers(data, true);
                buildRouteList(data.routes || []);
                buildGpkgList(data.corridors || [], data.information_layers || []);
            })
            .catch(function (err) {
                console.error('Failed to load map data:', err.message);
            });
        }

        function buildLegend(items) {
            var container = document.getElementById('cfpe-legend');
            if (!container) return;
            if (!items.length) {
                container.innerHTML = '<small class="text-muted">Legend will appear after data is loaded...</small>';
                return;
            }
            container.innerHTML = items.map(function (item, idx) {
                var prefix = '';
                if (item.category === 'corridor') prefix = '<span class="badge bg-primary me-1" style="font-size:9px">CORRIDOR</span>';
                else if (item.category === 'route') prefix = '<span class="badge bg-danger me-1" style="font-size:9px">ROUTE</span>';
                else if (item.category === 'layer') prefix = '<span class="badge bg-success me-1" style="font-size:9px">LAYER</span>';

                var swatch = item.type === 'line'
                    ? '<span class="cfpe-legend-color-line" style="background:' + item.color + '"></span>'
                    : '<span class="cfpe-legend-color" style="background:' + item.color + ';height:10px;width:10px;border-radius:2px;opacity:0.6"></span>';
                var isVisible = visibleLegendKeys.has(item.key);
                var hiddenClass = isVisible ? '' : ' is-hidden';
                return '<div class="cfpe-legend-item' + hiddenClass + '" data-legend-idx="' + idx + '">' +
                    '<input class="form-check-input cfpe-legend-toggle" type="checkbox" autocomplete="off" data-legend-toggle="' + idx + '">' +
                    swatch + prefix + '<span>' + escapeHtml(item.label) + '</span>' +
                '</div>';
            }).join('');

            container.querySelectorAll('[data-legend-toggle]').forEach(function (input) {
                var initialIdx = parseInt(input.dataset.legendToggle);
                var initialItem = items[initialIdx];
                input.defaultChecked = false;
                input.checked = !!(initialItem && visibleLegendKeys.has(initialItem.key));

                input.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
                input.addEventListener('change', function () {
                    var idx = parseInt(this.dataset.legendToggle);
                    var item = items[idx];
                    if (!item) return;

                    if (this.checked) {
                        visibleLegendKeys.add(item.key);
                    } else {
                        visibleLegendKeys.delete(item.key);
                    }

                    renderMapLayers(latestMapData, false);
                });
            });

            container.querySelectorAll('[data-legend-idx]').forEach(function (el) {
                el.addEventListener('click', function () {
                    var idx = parseInt(this.dataset.legendIdx);
                    var item = items[idx];
                    if (item && item.bounds) {
                        map.fitBounds(item.bounds, { padding: [50, 50], maxZoom: 14 });
                        // Flash the layer
                        if (item.layerRef) {
                            item.layerRef.setStyle({ weight: 8, opacity: 1 });
                            setTimeout(function () {
                                item.layerRef.setStyle({ weight: item.originalWeight || 4, opacity: item.originalOpacity || 0.8 });
                            }, 1500);
                        }
                    }
                });
            });
        }

        function renderCorridors(corridors) {
            var legendItems = [];
            if (!corridorLayer) return legendItems;
            corridorLayer.clearLayers();
            var corridorColors = ['#1565C0', '#0D47A1', '#1976D2', '#1E88E5', '#42A5F5'];
            corridors.forEach(function (corridor, idx) {
                if (!corridor.path_coordinates || !corridor.path_coordinates.length) return;
                var color = corridorColors[idx % corridorColors.length];
                var coords = corridor.path_coordinates;
                var drawn = false;
                var legendKey = makeLegendKey('corridor', corridor.id || corridor.corridor_code || corridor.name || idx);
                var drawnLayer = L.featureGroup();

                // Detect format: [[[lng,lat],...]] (multi-line) or [[lng,lat],...] (single line) or [lng,lat] (point)
                if (!Array.isArray(coords[0])) return; // skip if just numbers [lng, lat]

                if (Array.isArray(coords[0][0])) {
                    // Multi-line: [[[lng,lat], ...], ...]
                    coords.forEach(function (line) {
                        if (!Array.isArray(line) || line.length < 2) return;
                        var latLngs = line.map(function (c) {
                            if (!Array.isArray(c) || c.length < 2) return null;
                            return [c[1], c[0]];
                        }).filter(Boolean);
                        if (latLngs.length >= 2) {
                            L.polyline(latLngs, { color: color, weight: 4, opacity: 0.8 })
                                .bindPopup('<strong>' + escapeHtml(corridor.name) + '</strong><br>Code: ' + escapeHtml(corridor.corridor_code))
                                .addTo(drawnLayer);
                            drawn = true;
                        }
                    });
                } else if (typeof coords[0][0] === 'number') {
                    // Single line: [[lng,lat], [lng,lat], ...]
                    var latLngs = coords.map(function (c) {
                        if (!Array.isArray(c) || c.length < 2) return null;
                        return [c[1], c[0]];
                    }).filter(Boolean);
                    if (latLngs.length >= 2) {
                        L.polyline(latLngs, { color: color, weight: 4, opacity: 0.8 })
                            .bindPopup('<strong>' + escapeHtml(corridor.name) + '</strong><br>Code: ' + escapeHtml(corridor.corridor_code))
                            .addTo(drawnLayer);
                        drawn = true;
                    }
                }

                if (drawn) {
                    if (visibleLegendKeys.has(legendKey)) {
                        drawnLayer.addTo(corridorLayer);
                    }
                    legendItems.push({
                        key: legendKey,
                        type: 'line',
                        color: color,
                        label: corridor.name || corridor.corridor_code,
                        category: 'corridor',
                        bounds: drawnLayer.getBounds(),
                        layerRef: drawnLayer,
                        parentLayer: corridorLayer,
                        originalWeight: 4,
                        originalOpacity: 0.8
                    });
                }
            });
            return legendItems;
        }

        function renderInformationLayers(layers) {
            var legendItems = [];
            if (!infoLayerGroup) return legendItems;
            infoLayerGroup.clearLayers();
            layers.forEach(function (layer) {
                if (!layer.layer_payload || !layer.layer_payload.features) return;
                var color = layer.style_color || '#4CAF50';
                var layerName = (layer.name || '').toLowerCase();
                var legendKey = makeLegendKey('layer', layer.id || layer.layer_code || layer.name);

                // Determine icon/style per layer type
                var icon = null;
                var isPointIcon = false;
                if (layerName.indexOf('kesehatan') >= 0) {
                    icon = '🏥'; isPointIcon = true;
                } else if (layerName.indexOf('pendidikan') >= 0) {
                    icon = '🏫'; isPointIcon = true;
                } else if (layerName.indexOf('permukiman') >= 0) {
                    icon = '🏘️'; isPointIcon = true;
                } else if (layerName.indexOf('puncak') >= 0) {
                    icon = '🌋'; isPointIcon = true;
                }

                try {
                    var geoJsonLayer = L.geoJSON(layer.layer_payload, {
                        style: function () {
                            var weight = 2;
                            var fillOpacity = 0.15;
                            var dashArray = null;

                            if (layerName.indexOf('contour') >= 0) {
                                weight = 1; fillOpacity = 0; color = '#8D6E63'; dashArray = '3, 3';
                            } else if (layerName.indexOf('batas') >= 0) {
                                weight = 2; fillOpacity = 0.05; dashArray = '6, 4';
                            } else if (layerName.indexOf('sungai') >= 0) {
                                weight = 2; fillOpacity = 0.1;
                            } else if (layerName.indexOf('perimeter') >= 0) {
                                weight = 3; fillOpacity = 0.15; color = '#FF0000'; dashArray = null;
                            } else if (layerName.indexOf('permukiman') >= 0) {
                                weight = 1; fillOpacity = 0.25;
                            }

                            return {
                                color: color,
                                weight: weight,
                                opacity: 0.7,
                                fillColor: color,
                                fillOpacity: fillOpacity,
                                dashArray: dashArray
                            };
                        },
                        pointToLayer: function (feature, latlng) {
                            if (isPointIcon && icon) {
                                return L.marker(latlng, {
                                    icon: L.divIcon({
                                        html: '<span style="font-size:18px">' + icon + '</span>',
                                        className: 'leaflet-div-icon-transparent',
                                        iconSize: [24, 24],
                                        iconAnchor: [12, 12]
                                    })
                                });
                            }
                            return L.circleMarker(latlng, { radius: 5, fillColor: color, color: color, weight: 1, fillOpacity: 0.7 });
                        },
                        onEachFeature: function (feature, featureLayer) {
                            var props = feature.properties || {};
                            var popup = '<strong>' + escapeHtml(layer.name) + '</strong>';
                            if (props.NAMOBJ) popup += '<br>' + escapeHtml(props.NAMOBJ);
                            if (props.REMARK) popup += '<br>' + escapeHtml(props.REMARK);
                            if (props.NAMWS) popup += '<br>WS: ' + escapeHtml(props.NAMWS);
                            featureLayer.bindPopup(popup);
                        }
                    });
                    if (visibleLegendKeys.has(legendKey)) {
                        geoJsonLayer.addTo(infoLayerGroup);
                    }

                    var legendLabel = layer.name;
                    if (icon) legendLabel = icon + ' ' + layer.name;
                    legendItems.push({
                        key: legendKey,
                        type: layer.layer_type || 'polygon',
                        color: color,
                        label: legendLabel,
                        category: 'layer',
                        bounds: geoJsonLayer.getBounds(),
                        layerRef: geoJsonLayer,
                        parentLayer: infoLayerGroup,
                        originalWeight: 2,
                        originalOpacity: 0.7
                    });
                } catch (e) {
                    console.warn('Failed to render layer:', layer.name, e);
                }
            });
            return legendItems;
        }

        function renderRoutePolylines(routes) {
            var legendItems = [];
            if (!markersLayer) return legendItems;
            markersLayer.clearLayers();
            var routeColors = ['#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'];
            routes.forEach(function (route, idx) {
                if (!route.path_coordinates || !route.path_coordinates.length) return;
                var color = routeColors[idx % routeColors.length];
                var coords = route.path_coordinates;
                var legendKey = makeLegendKey('route', route.id || route.route_code || route.name || idx);
                var isRouteVisible = visibleLegendKeys.has(legendKey);
                var drawnRouteLayer = L.featureGroup();

                // Skip if coords is just [lng, lat] (a single point, not drawable)
                if (!Array.isArray(coords[0])) return;

                if (Array.isArray(coords[0][0])) {
                    // Multi-line: [[[lng,lat],...],...]
                    coords.forEach(function (line) {
                        if (!Array.isArray(line) || line.length < 2) return;
                        var latLngs = line.map(function (c) {
                            if (!Array.isArray(c) || c.length < 2) return null;
                            return [c[1], c[0]];
                        }).filter(Boolean);
                        if (latLngs.length >= 2) {
                            L.polyline(latLngs, { color: color, weight: 3, opacity: 0.9, dashArray: route.route_type === 'cfpe_corridor' ? null : '5, 5' })
                                .bindPopup('<strong>' + escapeHtml(route.name) + '</strong>')
                                .addTo(drawnRouteLayer);
                        }
                    });
                } else if (typeof coords[0][0] === 'number') {
                    // Single line: [[lng,lat], [lng,lat],...]
                    var latLngs = coords.map(function (c) {
                        if (!Array.isArray(c) || c.length < 2) return null;
                        return [c[1], c[0]];
                    }).filter(Boolean);
                    if (latLngs.length >= 2) {
                        L.polyline(latLngs, { color: color, weight: 3, opacity: 0.9, dashArray: route.route_type === 'cfpe_corridor' ? null : '5, 5' })
                            .bindPopup('<strong>' + escapeHtml(route.name) + '</strong>')
                            .addTo(drawnRouteLayer);
                    }
                }

                if (drawnRouteLayer.getLayers().length && isRouteVisible) {
                    drawnRouteLayer.addTo(markersLayer);
                }

                // Add to legend (only CFPE routes, skip monitoring_corridor since those are in corridorLayer)
                if (route.route_type === 'cfpe_corridor') {
                    legendItems.push({
                        key: legendKey,
                        type: 'line',
                        color: color,
                        label: route.name || route.route_code,
                        category: 'route',
                        bounds: drawnRouteLayer.getLayers().length ? drawnRouteLayer.getBounds() : null,
                        layerRef: drawnRouteLayer,
                        parentLayer: markersLayer,
                        originalWeight: 3,
                        originalOpacity: 0.9
                    });
                }

                // Add BM markers
                if (isRouteVisible && route.points) {
                    route.points.forEach(function (point) {
                        if (!point.latitude || !point.longitude) return;
                        var marker = L.marker([point.latitude, point.longitude], {
                            draggable: true,
                            icon: cfpeDivIcon(color, 13)
                        }).bindPopup(referencePointPopup(point, route)).addTo(markersLayer);
                        bindDraggablePoint(marker, 'reference_point', point, function (updatedPoint) {
                            return referencePointPopup(updatedPoint, route);
                        });
                    });
                }
            });
            return legendItems;
        }

        // Load map data is called from initMap after layers are ready

        // --- CSV Import (Multi-file) ---
        document.getElementById('csv-import-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const fileInput = document.getElementById('import-csv-file');
            const projectId = document.getElementById('import-project').value;
            const workspaceId = document.getElementById('import-workspace').value;

            if (!fileInput.files.length || !projectId) {
                showImportResult('warning', 'Pilih file CSV dan project.');
                return;
            }

            const files = Array.from(fileInput.files);
            const $importBtn = document.getElementById('import-btn');
            const $importSpinner = document.getElementById('import-spinner');
            $importBtn.disabled = true;
            $importSpinner.classList.add('active');
            showImportResult('info', 'Importing ' + files.length + ' file(s)...');

            var totalRoutes = 0, totalPoints = 0, completed = 0, errors = [];

            function importNext(index) {
                if (index >= files.length) {
                    // All done
                    $importBtn.disabled = false;
                    $importSpinner.classList.remove('active');
                    if (errors.length > 0) {
                        showImportResult('warning', 'Selesai dengan error: ' + errors.join('; ') + ' | Total: ' + totalRoutes + ' route, ' + totalPoints + ' points.');
                    } else {
                        showImportResult('success', 'Import selesai! ' + totalRoutes + ' route(s), ' + totalPoints + ' point(s) dari ' + files.length + ' file.');
                    }
                    fileInput.value = '';
                    setTimeout(function () { loadMapData(); }, 500);
                    return;
                }

                var formData = new FormData();
                formData.append('csv_file', files[index]);
                formData.append('project_id', projectId);
                if (workspaceId) formData.append('workspace_id', workspaceId);

                showImportResult('info', 'Importing file ' + (index + 1) + '/' + files.length + ': ' + files[index].name + '...');

                fetch('/cfpe/import-csv', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: formData
                })
                .then(handleResponse)
                .then(function (data) {
                    var summary = data.summary || {};
                    totalRoutes += summary.routes_count || 0;
                    totalPoints += summary.points_count || 0;
                })
                .catch(function (err) {
                    errors.push(files[index].name + ': ' + err.message);
                })
                .finally(function () {
                    importNext(index + 1);
                });
            }

            importNext(0);
        });

        function showImportResult(type, message) {
            const $results = document.getElementById('import-results');
            const $alert = document.getElementById('import-alert');
            $results.style.display = 'block';
            $alert.className = 'alert alert-' + type + ' mb-0';
            $alert.textContent = message;
        }

        // --- Utilities ---
        function handleResponse(response) {
            if (!response.ok) {
                return response.json().then(function (data) {
                    throw new Error(data.message || 'Request failed (' + response.status + ')');
                }).catch(function (parseErr) {
                    if (parseErr.message && !parseErr.message.includes('Request failed')) {
                        throw parseErr;
                    }
                    throw new Error('Request failed (' + response.status + ')');
                });
            }
            return response.json();
        }

        function escapeHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function formatNumber(val) {
            if (val == null || isNaN(val)) return '-';
            return parseFloat(val).toFixed(2);
        }

        function formatEtaRange(min, max) {
            if (min == null || max == null) return '-';
            return parseFloat(min).toFixed(1) + ' – ' + parseFloat(max).toFixed(1);
        }

        function showError(message) {
            // Use a simple toast-style alert at the top of content
            var alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.setAttribute('role', 'alert');
            alertDiv.innerHTML = '<i class="bx bx-error-circle me-1"></i> ' + escapeHtml(message) +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';

            var contentArea = document.querySelector('.page-content .container-fluid');
            if (contentArea) {
                contentArea.insertBefore(alertDiv, contentArea.children[1]);
                setTimeout(function () {
                    alertDiv.remove();
                }, 5000);
            }
        }

        function showMapLoading(show) {
            $mapLoading.classList.toggle('active', show);
        }

        // --- Initialize ---
        document.addEventListener('DOMContentLoaded', function () {
            initMap();
        });

        // If DOM already loaded (late script)
        if (document.readyState !== 'loading') {
            initMap();
        }
    })();
    </script>
@endsection
