<?php $__env->startSection('title'); ?> Konfigurasi Proyek <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<link href="<?php echo e(URL::asset('build/libs/leaflet/leaflet.css')); ?>" rel="stylesheet" type="text/css" />
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

    .sensor-live-icon {
        background: transparent;
        border: 0;
    }

    .sensor-pulse-marker {
        --sensor-color: #50a5f1;
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> RESQ <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Konfigurasi Proyek <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

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
                        <h5 class="mb-0"><?php echo e($dashboardTotals['projects'] ?? 0); ?></h5>
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
                        <h5 class="mb-0"><?php echo e($dashboardTotals['workspaces'] ?? 0); ?></h5>
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
                        <h5 class="mb-0"><?php echo e($dashboardTotals['monitoring_stations'] ?? 0); ?></h5>
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
                        <h5 class="mb-0"><?php echo e($dashboardTotals['sensors'] ?? 0); ?></h5>
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
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = ($coverageRows ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td><?php echo e($row['province']); ?></td>
                                    <td><?php echo e($row['workspaces']); ?></td>
                                    <td><?php echo e($row['sensors']); ?></td>
                                    <td>
                                        <span class="badge <?php echo e(in_array($row['status'], ['Danger', 'Bahaya', 'Awas']) ? 'bg-danger' : ($row['status'] === 'Waspada' ? 'bg-warning' : 'bg-success')); ?>">
                                            <?php echo e($row['status']); ?>

                                        </span>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Belum ada cakupan provinsi.</td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                            <strong><?php echo e($dashboardTotals['provinces'] ?? 0); ?></strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-3 mb-xl-0">
                            <span class="text-muted">Workspace Aktif</span>
                            <strong><?php echo e($dashboardTotals['workspaces'] ?? 0); ?></strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-3 mb-sm-0">
                            <span class="text-muted">Titik Sensor</span>
                            <strong><?php echo e($dashboardTotals['sensors'] ?? 0); ?></strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Stasiun Peringatan</span>
                            <strong><?php echo e($dashboardTotals['warning_stations'] ?? 0); ?></strong>
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
                    <a href="<?php echo e(route('projects.index')); ?>" class="btn btn-primary btn-sm">
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
                                <td><a href="<?php echo e(route('projects.index')); ?>">Proyek</a></td>
                                <td>ID proyek, pemilik, tanggal, basis data proyek</td>
                                <td><span class="badge bg-primary-subtle text-primary">Pengaturan</span></td>
                            </tr>
                            <tr>
                                <td>02</td>
                                <td><a href="<?php echo e(route('clusters.index')); ?>">Klaster</a></td>
                                <td>Ancaman bencana, penerima manfaat, provinsi, kab/kota</td>
                                <td><span class="badge bg-success-subtle text-success">Pemetaan</span></td>
                            </tr>
                            <tr>
                                <td>03</td>
                                <td><a href="<?php echo e(route('monitoring-stations.index')); ?>">Stasiun Pemantauan</a></td>
                                <td>Data logger, konektivitas, kredensial, sensor</td>
                                <td><span class="badge bg-info-subtle text-info">Telemetri</span></td>
                            </tr>
                            <tr>
                                <td>04</td>
                                <td><a href="<?php echo e(route('warning-stations.index')); ?>">Stasiun Peringatan</a></td>
                                <td>Kontroler, perangkat keluaran, uji perintah</td>
                                <td><span class="badge bg-warning-subtle text-warning">Aktivasi</span></td>
                            </tr>
                            <tr>
                                <td>05</td>
                                <td><a href="<?php echo e(route('telemetry.index')); ?>">Konfigurasi Telemetri</a></td>
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="<?php echo e(URL::asset('build/libs/leaflet/leaflet.js')); ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var mapElement = document.getElementById('sensor-cluster-map');

        if (!mapElement || typeof L === 'undefined') {
            return;
        }

        var clusters = <?php echo json_encode($mapClusters ?? [], 15, 512) ?>;
        var sensorPoints = <?php echo json_encode($mapSensors ?? [], 15, 512) ?>;
        var warningStations = <?php echo json_encode($mapWarningStations ?? [], 15, 512) ?>;
        var mapDataUrl = <?php echo json_encode(route('dashboard.map-data'), 15, 512) ?>;
        var sirenAudio = new Audio(<?php echo json_encode(asset('sound/sirene.mp3'), 15, 512) ?>);
        var sirenShouldPlay = false;
        var sirenIsPlaying = false;

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
            var clusters = data.clusters || [];
            var sensorPoints = data.sensors || [];
            var warningStations = data.warningStations || [];

            return clusters.some(isDanger)
                || sensorPoints.some(isDanger)
                || warningStations.some(function (station) {
                    return isDanger(station) || (station.danger_sensors || []).some(isDanger);
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
            return '<div class="map-danger-reading">' +
                '<div><strong>Current Value :</strong> ' + escapeHtml(sensor.value || '-') + '</div>' +
                '<div><strong>Threshold:</strong> ' + escapeHtml(sensor.threshold || '-') + '</div>' +
                '<div><strong>Alert:</strong> ' + escapeHtml(sensor.alert_level || sensor.status || '-') + '</div>' +
                '<div><strong>Update:</strong> ' + escapeHtml(sensor.last_seen || '-') + '</div>' +
            '</div>';
        }

        var map = L.map('sensor-cluster-map', {
            scrollWheelZoom: false
        }).setView([-2.6, 118.0], 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var clusterLayer = L.layerGroup().addTo(map);
        var sensorLayer = L.layerGroup().addTo(map);
        var warningLayer = L.layerGroup().addTo(map);

        function renderMapData(data) {
            var clusters = data.clusters || [];
            var sensorPoints = data.sensors || [];
            var warningStations = data.warningStations || [];

            syncSiren(hasDangerState(data));
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

            sensorPoints.forEach(function (sensor) {
                var sensorDanger = isDanger(sensor);
                var color = sensorDanger ? '#f46a6a' : (statusColors[sensor.status] || '#50a5f1');
                var statusClass = sensorDanger
                    ? 'danger'
                    : (sensor.status === 'Waspada' ? 'warning' : 'live');
                var sensorIcon = L.divIcon({
                    className: 'sensor-live-icon',
                    html: '<span class="sensor-pulse-marker ' + statusClass + '" style="--sensor-color: ' + color + ';"></span>',
                    iconSize: sensorDanger ? [52, 52] : [30, 30],
                    iconAnchor: sensorDanger ? [26, 26] : [15, 15],
                    popupAnchor: [0, -14]
                });
                var sensorPopupBody =
                    '<br>Jenis: ' + escapeHtml(sensor.type) +
                    '<br>Parameter: ' + escapeHtml(sensor.parameter) +
                    '<br>Stasiun: ' + escapeHtml(sensor.station) +
                    '<br>Warning Station: ' + escapeHtml(sensor.warning_station) +
                    '<br>Provinsi: ' + escapeHtml(sensor.province) +
                    '<br>Status: ' + escapeHtml(sensor.status) +
                    sensorReadingHtml(sensor);

                if (sensorDanger) {
                    L.circle([sensor.lat, sensor.lng], {
                        radius: 120000,
                        color: '#f46a6a',
                        fillColor: '#f46a6a',
                        fillOpacity: 0.22,
                        weight: 4,
                        className: 'danger-map-radius'
                    }).addTo(sensorLayer);
                }

                L.marker([sensor.lat, sensor.lng], {
                    icon: sensorIcon,
                    title: sensor.name
                }).bindPopup(sensorDanger
                    ? dangerPopup(sensor.name, sensorPopupBody)
                    : '<strong>' + escapeHtml(sensor.name) + '</strong>' + sensorPopupBody
                ).addTo(sensorLayer);
            });

            warningStations.forEach(function (station) {
                var stationDanger = isDanger(station);
                var color = stationDanger ? '#f46a6a' : (statusColors[station.status] || '#f1b44c');
                var warningReadings = '';

                (station.danger_sensors || []).forEach(function (sensor) {
                    warningReadings += sensorReadingHtml(sensor)
                        .replace('<div class="map-danger-reading">', '<div class="map-danger-reading"><strong>' + escapeHtml(sensor.name) + '</strong>');
                });

                var stationPopupBody =
                    '<br>Provinsi: ' + escapeHtml(station.province) +
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
        }

        L.control.layers(null, {
            'Klaster': clusterLayer,
            'Sensor Pemantauan': sensorLayer,
            'Stasiun Peringatan': warningLayer
        }, {
            collapsed: false
        }).addTo(map);

        renderMapData({
            clusters: clusters,
            sensors: sensorPoints,
            warningStations: warningStations
        });

        var mapRefreshInFlight = false;

        function refreshMapData() {
            if (mapRefreshInFlight) {
                return;
            }

            mapRefreshInFlight = true;
            fetch(mapDataUrl, {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    return response.ok ? response.json() : null;
                })
                .then(function (data) {
                    if (data) {
                        renderMapData(data);
                    }
                })
                .catch(function () {})
                .finally(function () {
                    mapRefreshInFlight = false;
                });
        }

        refreshMapData();
        setInterval(refreshMapData, 1000);
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/dashboard/index.blade.php ENDPATH**/ ?>