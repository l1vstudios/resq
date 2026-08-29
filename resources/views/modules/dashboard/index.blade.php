@extends('layouts.master')

@section('title') Konfigurasi Proyek @endsection

@section('css')
<link href="{{ URL::asset('build/libs/leaflet/leaflet.css') }}" rel="stylesheet" type="text/css" />
<style>
    #sensor-cluster-map {
        min-height: 560px;
        width: 100%;
        border-radius: 8px;
        overflow: hidden;
    }

    .map-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .map-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #74788d;
        font-size: 12px;
    }

    .map-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }

    .map-filter-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(5, minmax(140px, 1fr)) auto;
    }

    .map-filter-grid .form-label {
        color: #495057;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .map-filter-status {
        color: #74788d;
        font-size: 12px;
        margin-top: 8px;
    }

    .terrain-cue-grid {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 10px;
    }

    .terrain-cue {
        background: #ffffff;
        border: 1px solid #e9e9ef;
        border-radius: 6px;
        color: #495057;
        font-size: 12px;
        padding: 8px 10px;
    }

    .terrain-cue strong {
        color: #343a40;
        display: block;
        font-size: 12px;
        margin-bottom: 2px;
    }

    .gis-toolbar {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .gis-readout {
        background: #ffffff;
        border: 1px solid #d8dde6;
        border-radius: 6px;
        color: #495057;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-size: 12px;
        min-height: 31px;
        padding: 7px 9px;
    }

    .gis-opacity-control {
        align-items: center;
        background: #ffffff;
        border: 1px solid #d8dde6;
        border-radius: 6px;
        display: inline-flex;
        gap: 8px;
        min-height: 31px;
        padding: 5px 9px;
    }

    .gis-opacity-control label {
        color: #495057;
        font-size: 12px;
        font-weight: 700;
        margin: 0;
        white-space: nowrap;
    }

    .gis-opacity-control input {
        width: 110px;
    }

    #sensor-cluster-map.gis-measuring {
        cursor: crosshair;
    }

    @media (max-width: 991.98px) {
        .map-filter-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .terrain-cue-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .map-filter-grid {
            grid-template-columns: 1fr;
        }
    }

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
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') RESQ @endslot
@slot('title') Konfigurasi Proyek @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h4 class="card-title mb-1">Peta Sensor & Klaster</h4>
                        <p class="text-muted mb-0">Sebaran klaster, sensor bencana, dan stasiun peringatan per provinsi.</p>
                    </div>
                    <div class="map-legend">
                        <span class="map-legend-item"><span class="map-legend-dot bg-success"></span> Klaster</span>
                        <span class="map-legend-item"><span class="map-legend-dot bg-info"></span> Sensor Aktif</span>
                        <span class="map-legend-item"><span class="map-legend-dot bg-warning"></span> Stasiun Peringatan</span>
                        <span class="map-legend-item"><span class="map-legend-dot bg-danger"></span> Bahaya / Kritis</span>
                    </div>
                </div>
                <div class="border rounded bg-light p-3 mb-3">
                    <div class="map-filter-grid align-items-end">
                        <div>
                            <label for="map-province-filter" class="form-label">Daerah / Provinsi</label>
                            <select id="map-province-filter" class="form-select form-select-sm">
                                <option value="">Semua Indonesia</option>
                            </select>
                        </div>
                        <div>
                            <label for="map-city-filter" class="form-label">Kabupaten / Kota</label>
                            <select id="map-city-filter" class="form-select form-select-sm" disabled>
                                <option value="">Semua kab/kota</option>
                            </select>
                        </div>
                        <div>
                            <label for="map-radius-filter" class="form-label">Lock Radius</label>
                            <select id="map-radius-filter" class="form-select form-select-sm">
                                <option value="auto">Auto bounds</option>
                                <option value="15000">15 km</option>
                                <option value="30000" selected>30 km</option>
                                <option value="50000">50 km</option>
                                <option value="100000">100 km</option>
                            </select>
                        </div>
                        <div>
                            <label for="map-terrain-profile" class="form-label">Visual Terrain</label>
                            <select id="map-terrain-profile" class="form-select form-select-sm">
                                <option value="mountain" selected>Mountain / Highland</option>
                                <option value="operation">Operational</option>
                                <option value="satellite">Satellite Relief</option>
                            </select>
                        </div>
                        <div class="form-check form-switch pb-1">
                            <input class="form-check-input" type="checkbox" id="map-lock-filter" checked>
                            <label class="form-check-label" for="map-lock-filter">Lock area</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="map-filter-reset">Reset</button>
                    </div>
                    <div class="map-filter-status" id="map-filter-status">Menampilkan semua titik.</div>
                    <div class="terrain-cue-grid">
                        <div class="terrain-cue"><strong>Kontur rapat</strong>Lereng lebih curam, rawan aliran cepat atau longsoran.</div>
                        <div class="terrain-cue"><strong>Kontur tertutup/tinggi</strong>Punggungan, bukit, atau dataran tinggi sekitar sensor.</div>
                        <div class="terrain-cue"><strong>Bayangan relief</strong>Memperjelas lembah, alur sungai, dan arah kemiringan lahan.</div>
                    </div>
                    <div class="gis-toolbar mt-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="map-fit-filtered">
                            <i class="bx bx-target-lock me-1"></i> Fit Area
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="map-fit-all">
                            <i class="bx bx-world me-1"></i> Fit All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-dark" id="map-measure-toggle">
                            <i class="bx bx-ruler me-1"></i> Measure
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="map-measure-clear">
                            Clear Measure
                        </button>
                        <div class="gis-opacity-control">
                            <label for="map-hillshade-opacity">Relief</label>
                            <input type="range" id="map-hillshade-opacity" min="0" max="80" value="52">
                        </div>
                        <div class="gis-readout" id="map-coordinate-readout">Lat -, Lng -</div>
                        <div class="gis-readout" id="map-measure-readout">Measure: 0 m</div>
                    </div>
                </div>
                <div id="sensor-cluster-map"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-20">
                            <i class="bx bx-briefcase-alt-2"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-muted mb-1">Proyek</p>
                        <h5 class="mb-0">{{ $dashboardTotals['projects'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-success-subtle text-success font-size-20">
                            <i class="bx bx-map-alt"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-muted mb-1">Geospatial Workspace</p>
                        <h5 class="mb-0">{{ $dashboardTotals['workspaces'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-info-subtle text-info font-size-20">
                            <i class="bx bx-map-pin"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-muted mb-1">Monitoring Station</p>
                        <h5 class="mb-0">{{ $dashboardTotals['monitoring_stations'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-warning-subtle text-warning font-size-20">
                            <i class="bx bx-broadcast"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-muted mb-1">Sensor</p>
                        <h5 class="mb-0">{{ $dashboardTotals['sensors'] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Cakupan Provinsi</h4>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Provinsi</th>
                                <th>Klaster</th>
                                <th>Sensor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($coverageRows ?? collect()) as $row)
                                <tr>
                                    <td>{{ $row['province'] }}</td>
                                    <td>{{ $row['workspaces'] }}</td>
                                    <td>{{ $row['sensors'] }}</td>
                                    <td>
                                        <span class="badge {{ in_array($row['status'], ['Danger', 'Bahaya', 'Awas']) ? 'bg-danger' : ($row['status'] === 'Waspada' ? 'bg-warning' : 'bg-success') }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada cakupan provinsi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Ringkasan Peta</h4>
                <div class="row">
                    <div class="col-xl-3 col-sm-6">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-3 mb-xl-0">
                            <span class="text-muted">Total Provinsi</span>
                            <strong>{{ $dashboardTotals['provinces'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-3 mb-xl-0">
                            <span class="text-muted">Workspace Aktif</span>
                            <strong>{{ $dashboardTotals['workspaces'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-3 mb-sm-0">
                            <span class="text-muted">Titik Sensor</span>
                            <strong>{{ $dashboardTotals['sensors'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Stasiun Peringatan</span>
                            <strong>{{ $dashboardTotals['warning_stations'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Alur Konfigurasi</h4>
                        <p class="text-muted mb-0">Urutan modul mengikuti dokumen Konfigurasi Proyek.</p>
                    </div>
                    <a href="{{ route('projects.index') }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-plus me-1"></i> Proyek Baru
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tahap</th>
                                <th>Modul</th>
                                <th>Data Utama</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>01</td>
                                <td><a href="{{ route('projects.index') }}">Proyek</a></td>
                                <td>ID proyek, pemilik, tanggal, basis data proyek</td>
                                <td><span class="badge bg-primary-subtle text-primary">Pengaturan</span></td>
                            </tr>
                            <tr>
                                <td>02</td>
                                <td><a href="{{ route('clusters.index') }}">Klaster</a></td>
                                <td>Ancaman bencana, penerima manfaat, provinsi, kab/kota</td>
                                <td><span class="badge bg-success-subtle text-success">Pemetaan</span></td>
                            </tr>
                            <tr>
                                <td>03</td>
                                <td><a href="{{ route('monitoring-stations.index') }}">Stasiun Pemantauan</a></td>
                                <td>Data logger, konektivitas, kredensial, sensor</td>
                                <td><span class="badge bg-info-subtle text-info">Telemetri</span></td>
                            </tr>
                            <tr>
                                <td>04</td>
                                <td><a href="{{ route('warning-stations.index') }}">Stasiun Peringatan</a></td>
                                <td>Kontroler, perangkat keluaran, uji perintah</td>
                                <td><span class="badge bg-warning-subtle text-warning">Aktivasi</span></td>
                            </tr>
                            <tr>
                                <td>05</td>
                                <td><a href="{{ route('telemetry.index') }}">Konfigurasi Telemetri</a></td>
                                <td>Data terkirim, jadwal kalibrasi, validasi</td>
                                <td><span class="badge bg-secondary-subtle text-secondary">Pemantauan</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/leaflet/leaflet.js') }}"></script>
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
        var mapDataUrl = @json(route('dashboard.map-data', [], false));
        var sirenAudio = new Audio(@json('/sound/sirene.mp3'));
        var sirenShouldPlay = false;
        var sirenIsPlaying = false;
        var lastMapFocusKey = null;
        var hasAppliedInitialFocus = false;
        var latestMapData = {
            clusters: clusters,
            sensors: sensorPoints,
            warningStations: warningStations,
            alert_active: initialAlertActive
        };
        var provinceFilter = document.getElementById('map-province-filter');
        var cityFilter = document.getElementById('map-city-filter');
        var radiusFilter = document.getElementById('map-radius-filter');
        var terrainProfile = document.getElementById('map-terrain-profile');
        var lockFilter = document.getElementById('map-lock-filter');
        var resetFilter = document.getElementById('map-filter-reset');
        var filterStatus = document.getElementById('map-filter-status');
        var fitFilteredButton = document.getElementById('map-fit-filtered');
        var fitAllButton = document.getElementById('map-fit-all');
        var measureToggleButton = document.getElementById('map-measure-toggle');
        var measureClearButton = document.getElementById('map-measure-clear');
        var coordinateReadout = document.getElementById('map-coordinate-readout');
        var measureReadout = document.getElementById('map-measure-readout');
        var hillshadeOpacity = document.getElementById('map-hillshade-opacity');
        var defaultMapCenter = [-2.6, 118.0];
        var defaultMapZoom = 5;
        var regionPresets = {
            'DKI Jakarta': {
                center: [-6.2088, 106.8456],
                bounds: [[-6.38, 106.65], [-5.95, 107.05]],
                zoom: 11
            },
            'Jakarta': {
                center: [-6.2088, 106.8456],
                bounds: [[-6.38, 106.65], [-5.95, 107.05]],
                zoom: 11
            }
        };

        sirenAudio.loop = true;
        sirenAudio.preload = 'auto';

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

        function hasDangerState(data) {
            if (typeof data.alert_active === 'boolean') {
                return data.alert_active;
            }

            var clusters = data.clusters || [];
            var sensorPoints = data.sensors || [];
            var warningStations = data.warningStations || [];

            return clusters.some(isDanger)
                || sensorPoints.some(isDanger)
                || warningStations.some(function (station) {
                    return Boolean(station.is_danger) || (station.danger_sensors || []).some(isDanger);
                });
        }

        function syncSiren(shouldPlay) {
            sirenShouldPlay = shouldPlay;

            if (!shouldPlay) {
                sirenAudio.pause();
                sirenAudio.currentTime = 0;
                sirenIsPlaying = false;
                return;
            }

            if (sirenIsPlaying) {
                return;
            }

            sirenAudio.play()
                .then(function () {
                    if (!sirenShouldPlay) {
                        sirenAudio.pause();
                        sirenAudio.currentTime = 0;
                        sirenIsPlaying = false;
                        return;
                    }

                    sirenIsPlaying = true;
                })
                .catch(function () {
                    sirenIsPlaying = false;
                });
        }

        document.addEventListener('click', function () {
            if (sirenShouldPlay && !sirenIsPlaying) {
                syncSiren(true);
            }
        });

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

        function uniqueSorted(values) {
            return Array.from(new Set(values.filter(Boolean).map(function (value) {
                return String(value).trim();
            }).filter(Boolean))).sort(function (a, b) {
                return a.localeCompare(b);
            });
        }

        function allMapItems(data) {
            return []
                .concat(data.clusters || [])
                .concat(data.sensors || [])
                .concat(data.warningStations || []);
        }

        function populateProvinceFilter(data) {
            if (!provinceFilter) {
                return;
            }

            var selectedProvince = provinceFilter.value;
            var provinces = uniqueSorted(allMapItems(data).map(function (item) {
                return item.province;
            }));
            provinceFilter.innerHTML = '<option value="">Semua Indonesia</option>' + provinces.map(function (province) {
                return '<option value="' + escapeHtml(province) + '">' + escapeHtml(province) + '</option>';
            }).join('');

            if (provinces.includes(selectedProvince)) {
                provinceFilter.value = selectedProvince;
            }
        }

        function populateCityFilter(data) {
            if (!cityFilter || !provinceFilter) {
                return;
            }

            var province = provinceFilter.value;
            var selectedCity = cityFilter.value;
            var cities = province
                ? uniqueSorted(allMapItems(data)
                    .filter(function (item) {
                        return item.province === province;
                    })
                    .map(function (item) {
                        return item.city;
                    }))
                : [];

            cityFilter.disabled = !province || !cities.length;
            cityFilter.innerHTML = '<option value="">Semua kab/kota</option>' + cities.map(function (city) {
                return '<option value="' + escapeHtml(city) + '">' + escapeHtml(city) + '</option>';
            }).join('');

            if (cities.includes(selectedCity)) {
                cityFilter.value = selectedCity;
            }
        }

        function itemMatchesFilter(item) {
            var province = provinceFilter ? provinceFilter.value : '';
            var city = cityFilter ? cityFilter.value : '';

            if (province && item.province !== province) {
                return false;
            }

            if (city && item.city !== city) {
                return false;
            }

            return true;
        }

        function filteredMapData(data) {
            return {
                clusters: (data.clusters || []).filter(itemMatchesFilter),
                sensors: (data.sensors || []).filter(itemMatchesFilter),
                warningStations: (data.warningStations || []).filter(itemMatchesFilter),
                alert_active: data.alert_active
            };
        }

        function boundsFromItems(items) {
            var latLngs = items
                .filter(function (item) {
                    return item.lat !== null && item.lng !== null;
                })
                .map(function (item) {
                    return [Number(item.lat), Number(item.lng)];
                });

            return latLngs.length ? L.latLngBounds(latLngs) : null;
        }

        function radiusBounds(center, radiusMeters) {
            var lat = Number(center[0]);
            var lng = Number(center[1]);
            var latDelta = radiusMeters / 111320;
            var lngDelta = radiusMeters / (111320 * Math.cos(lat * Math.PI / 180));

            return L.latLngBounds(
                [lat - latDelta, lng - lngDelta],
                [lat + latDelta, lng + lngDelta]
            );
        }

        function focusCenterForData(data) {
            var province = provinceFilter ? provinceFilter.value : '';
            var city = cityFilter ? cityFilter.value : '';
            var items = allMapItems(data);
            var bounds = boundsFromItems(items);

            if (bounds) {
                return [bounds.getCenter().lat, bounds.getCenter().lng];
            }

            if (province && regionPresets[province]) {
                return regionPresets[province].center;
            }

            if (city && regionPresets[city]) {
                return regionPresets[city].center;
            }

            return defaultMapCenter;
        }

        function applyMapFocus(data) {
            var province = provinceFilter ? provinceFilter.value : '';
            var city = cityFilter ? cityFilter.value : '';
            var lockEnabled = !lockFilter || lockFilter.checked;
            var radiusValue = radiusFilter ? radiusFilter.value : 'auto';
            var items = allMapItems(data);
            var bounds = boundsFromItems(items);
            var preset = regionPresets[city] || regionPresets[province] || null;
            var focusKey = [province || 'all', city || 'all', radiusValue, lockEnabled ? 'locked' : 'free'].join('|');
            var shouldMoveMap = !hasAppliedInitialFocus || focusKey !== lastMapFocusKey;

            if (!province && !city) {
                if (lockEnabled) {
                    map.setMaxBounds(null);
                }
                if (!shouldMoveMap) {
                    return;
                }
                if (bounds && bounds.isValid()) {
                    map.fitBounds(bounds.pad(0.25), { maxZoom: 9 });
                } else {
                    map.setView(defaultMapCenter, defaultMapZoom);
                }
                lastMapFocusKey = focusKey;
                hasAppliedInitialFocus = true;
                return;
            }

            if (radiusValue !== 'auto') {
                bounds = radiusBounds(focusCenterForData(data), Number(radiusValue));
            } else if ((!bounds || !bounds.isValid()) && preset) {
                bounds = L.latLngBounds(preset.bounds);
            }

            if (!bounds || !bounds.isValid()) {
                return;
            }

            focusLayer.addLayer(L.rectangle(bounds, {
                color: '#556ee6',
                fillOpacity: 0,
                opacity: 0.5,
                weight: 2,
                dashArray: '6 6'
            }));

            if (shouldMoveMap) {
                map.fitBounds(bounds.pad(0.08), {
                    maxZoom: preset ? preset.zoom : 12
                });
            }

            if (lockEnabled) {
                map.setMaxBounds(bounds.pad(0.18));
                map.options.maxBoundsViscosity = 0.9;
            } else {
                map.setMaxBounds(null);
            }

            lastMapFocusKey = focusKey;
            hasAppliedInitialFocus = true;
        }

        function updateFilterStatus(data) {
            if (!filterStatus) {
                return;
            }

            var province = provinceFilter ? provinceFilter.value : '';
            var city = cityFilter ? cityFilter.value : '';
            var label = city || province || 'Semua Indonesia';
            var total = (data.clusters || []).length + (data.sensors || []).length + (data.warningStations || []).length;

            filterStatus.textContent = 'Area: ' + label + ' - ' + total + ' titik tampil'
                + (lockFilter && lockFilter.checked ? ' - radius/area dikunci.' : '.');
        }

        function rerenderCurrentMap() {
            renderMapData(latestMapData);
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
            scrollWheelZoom: false
        }).setView(defaultMapCenter, defaultMapZoom);

        L.control.scale({
            imperial: false,
            metric: true,
            position: 'bottomleft'
        }).addTo(map);

        var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        });
        var topoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            maxZoom: 17,
            attribution: '&copy; OpenStreetMap contributors, SRTM | OpenTopoMap'
        });
        var esriTopoLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 18,
            attribution: 'Tiles &copy; Esri'
        });
        var satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 18,
            attribution: 'Tiles &copy; Esri'
        });
        var hillshadeLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Elevation/World_Hillshade/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 16,
            opacity: 0.36,
            attribution: 'Hillshade &copy; Esri'
        });

        topoLayer.addTo(map);
        hillshadeLayer.addTo(map);

        var clusterLayer = L.layerGroup().addTo(map);
        var sensorLayer = L.layerGroup().addTo(map);
        var warningLayer = L.layerGroup().addTo(map);
        var focusLayer = L.layerGroup().addTo(map);
        var measureLayer = L.layerGroup().addTo(map);
        var spatialLayer = L.layerGroup().addTo(map);
        var measureMode = false;
        var measurePoints = [];

        function renderMapData(data) {
            latestMapData = data;
            populateProvinceFilter(data);
            populateCityFilter(data);

            var visibleData = filteredMapData(data);
            var clusters = visibleData.clusters || [];
            var sensorPoints = visibleData.sensors || [];
            var warningStations = visibleData.warningStations || [];

            syncSiren(hasDangerState(visibleData));
            clusterLayer.clearLayers();
            sensorLayer.clearLayers();
            warningLayer.clearLayers();
            focusLayer.clearLayers();

            function impactRadiusMeters(item) {
                var radius = Number(item.impact_radius_meters);

                return Number.isFinite(radius) && radius > 0 ? radius : 25000;
            }

            clusters.forEach(function (cluster) {
                var color = isDanger(cluster) ? '#f46a6a' : (statusColors[cluster.status] || '#34c38f');
                var clusterDanger = isDanger(cluster);

                L.circle([cluster.lat, cluster.lng], {
                    radius: impactRadiusMeters(cluster),
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
            sensorPoints.forEach(function (sensor) {
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
                        radius: impactRadiusMeters(sensor),
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
                        radius: impactRadiusMeters(station),
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
            updateFilterStatus(visibleData);
        }

        function setTerrainProfile(profile) {
            [osmLayer, topoLayer, esriTopoLayer, satelliteLayer].forEach(function (layer) {
                if (map.hasLayer(layer)) {
                    map.removeLayer(layer);
                }
            });

            if (profile === 'operation') {
                osmLayer.addTo(map);
                hillshadeLayer.setOpacity(0.22);
                if (hillshadeOpacity) {
                    hillshadeOpacity.value = 22;
                }
                if (!map.hasLayer(hillshadeLayer)) {
                    hillshadeLayer.addTo(map);
                }
                return;
            }

            if (profile === 'satellite') {
                satelliteLayer.addTo(map);
                hillshadeLayer.setOpacity(0.42);
                if (hillshadeOpacity) {
                    hillshadeOpacity.value = 42;
                }
                if (!map.hasLayer(hillshadeLayer)) {
                    hillshadeLayer.addTo(map);
                }
                return;
            }

            topoLayer.addTo(map);
            hillshadeLayer.setOpacity(0.52);
            if (hillshadeOpacity) {
                hillshadeOpacity.value = 52;
            }
            if (!map.hasLayer(hillshadeLayer)) {
                hillshadeLayer.addTo(map);
            }
        }

        function formatDistance(meters) {
            return meters >= 1000
                ? (meters / 1000).toFixed(2) + ' km'
                : Math.round(meters) + ' m';
        }

        function measureDistance() {
            var total = 0;

            for (var index = 1; index < measurePoints.length; index++) {
                total += map.distance(measurePoints[index - 1], measurePoints[index]);
            }

            return total;
        }

        function renderMeasure() {
            measureLayer.clearLayers();

            measurePoints.forEach(function (point, index) {
                L.circleMarker(point, {
                    radius: 5,
                    color: '#343a40',
                    fillColor: '#ffffff',
                    fillOpacity: 1,
                    weight: 2
                }).bindTooltip(String(index + 1), {
                    direction: 'top',
                    permanent: true,
                    opacity: 0.9
                }).addTo(measureLayer);
            });

            if (measurePoints.length > 1) {
                L.polyline(measurePoints, {
                    color: '#343a40',
                    dashArray: '8 6',
                    weight: 3
                }).addTo(measureLayer);
            }

            if (measureReadout) {
                measureReadout.textContent = 'Measure: ' + formatDistance(measureDistance());
            }
        }

        function setMeasureMode(enabled) {
            measureMode = enabled;
            mapElement.classList.toggle('gis-measuring', enabled);
            if (measureToggleButton) {
                measureToggleButton.classList.toggle('active', enabled);
                measureToggleButton.classList.toggle('btn-dark', enabled);
                measureToggleButton.classList.toggle('btn-outline-dark', !enabled);
            }
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
            'Koridor & Route': spatialLayer
        }, {
            collapsed: false
        }).addTo(map);

        if (provinceFilter) {
            provinceFilter.addEventListener('change', function () {
                if (cityFilter) {
                    cityFilter.value = '';
                }
                rerenderCurrentMap();
            });
        }

        if (cityFilter) {
            cityFilter.addEventListener('change', rerenderCurrentMap);
        }

        if (radiusFilter) {
            radiusFilter.addEventListener('change', rerenderCurrentMap);
        }

        if (terrainProfile) {
            terrainProfile.addEventListener('change', function () {
                setTerrainProfile(terrainProfile.value);
            });
            setTerrainProfile(terrainProfile.value);
        }

        if (hillshadeOpacity) {
            hillshadeOpacity.addEventListener('input', function () {
                hillshadeLayer.setOpacity(Number(hillshadeOpacity.value || 0) / 100);
                if (!map.hasLayer(hillshadeLayer) && Number(hillshadeOpacity.value || 0) > 0) {
                    hillshadeLayer.addTo(map);
                }
            });
        }

        if (fitFilteredButton) {
            fitFilteredButton.addEventListener('click', function () {
                lastMapFocusKey = null;
                hasAppliedInitialFocus = false;
                rerenderCurrentMap();
            });
        }

        if (fitAllButton) {
            fitAllButton.addEventListener('click', function () {
                if (provinceFilter) {
                    provinceFilter.value = '';
                }
                if (cityFilter) {
                    cityFilter.value = '';
                }
                if (lockFilter) {
                    lockFilter.checked = false;
                }
                lastMapFocusKey = null;
                hasAppliedInitialFocus = false;
                rerenderCurrentMap();
            });
        }

        if (measureToggleButton) {
            measureToggleButton.addEventListener('click', function () {
                setMeasureMode(!measureMode);
            });
        }

        if (measureClearButton) {
            measureClearButton.addEventListener('click', function () {
                measurePoints = [];
                renderMeasure();
            });
        }

        map.on('mousemove', function (event) {
            if (coordinateReadout) {
                coordinateReadout.textContent = 'Lat ' + event.latlng.lat.toFixed(6) + ', Lng ' + event.latlng.lng.toFixed(6);
            }
        });

        mapElement.addEventListener('mouseleave', function () {
            if (coordinateReadout) {
                coordinateReadout.textContent = 'Lat -, Lng -';
            }
        });

        map.on('click', function (event) {
            if (!measureMode) {
                return;
            }

            measurePoints.push(event.latlng);
            renderMeasure();
        });

        if (lockFilter) {
            lockFilter.addEventListener('change', rerenderCurrentMap);
        }

        if (resetFilter) {
            resetFilter.addEventListener('click', function () {
                if (provinceFilter) {
                    provinceFilter.value = '';
                }
                if (cityFilter) {
                    cityFilter.value = '';
                }
                if (radiusFilter) {
                    radiusFilter.value = '30000';
                }
                if (terrainProfile) {
                    terrainProfile.value = 'mountain';
                    setTerrainProfile(terrainProfile.value);
                }
                if (lockFilter) {
                    lockFilter.checked = true;
                }
                rerenderCurrentMap();
            });
        }

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
                        syncSiren(false);
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
                    syncSiren(false);
                })
                .finally(function () {
                    mapRefreshInFlight = false;
                });
        }

        refreshMapData();
        setInterval(refreshMapData, 1000);

        // --- Spatial Overlay (Routes, Corridors, Information Layers from CFPE) ---
        var spatialLoaded = false;

        function loadSpatialOverlay() {
            if (spatialLoaded) return;
            spatialLoaded = true;

            fetch('/cfpe/map-data', {
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
            })
            .catch(function () {});
        }

        // Load spatial data after a short delay
        setTimeout(loadSpatialOverlay, 2000);

        // Add spatial layer to layer control
        map.on('overlayadd', function () {});
    });
</script>
@endsection
