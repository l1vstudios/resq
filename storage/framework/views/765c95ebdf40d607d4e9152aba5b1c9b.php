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
        animation: sensorDangerBlink .72s ease-in-out infinite alternate;
    }

    .sensor-pulse-marker.danger::after {
        animation-duration: .78s;
        border-width: 3px;
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

        var statusColors = {
            Normal: '#34c38f',
            Danger: '#f46a6a',
            Siap: '#34c38f',
            Waspada: '#f1b44c',
            Pengujian: '#50a5f1',
            Bahaya: '#f46a6a',
            Siaga: '#f46a6a'
        };

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

        clusters.forEach(function (cluster) {
            var color = statusColors[cluster.status] || '#34c38f';

            L.circle([cluster.lat, cluster.lng], {
                radius: 22000,
                color: color,
                fillColor: color,
                fillOpacity: 0.12,
                weight: 2
            }).addTo(clusterLayer);

            L.circleMarker([cluster.lat, cluster.lng], {
                radius: 9,
                color: '#ffffff',
                fillColor: color,
                fillOpacity: 1,
                weight: 2
            }).bindPopup(
                '<strong>' + cluster.name + '</strong>' +
                '<br>Provinsi: ' + cluster.province +
                '<br>Kab/Kota: ' + cluster.city +
                '<br>Ancaman: ' + cluster.hazard +
                '<br>Sensor: ' + cluster.sensors +
                '<br>Stasiun Peringatan: ' + cluster.warnings +
                '<br>Status: ' + cluster.status
            ).addTo(clusterLayer);
        });

        sensorPoints.forEach(function (sensor) {
            var color = statusColors[sensor.status] || '#50a5f1';
            var statusClass = sensor.status === 'Danger' || sensor.status === 'Bahaya' || sensor.status === 'Siaga'
                ? 'danger'
                : (sensor.status === 'Waspada' ? 'warning' : 'live');
            var sensorIcon = L.divIcon({
                className: 'sensor-live-icon',
                html: '<span class="sensor-pulse-marker ' + statusClass + '" style="--sensor-color: ' + color + ';"></span>',
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                popupAnchor: [0, -14]
            });

            L.marker([sensor.lat, sensor.lng], {
                icon: sensorIcon,
                title: sensor.name
            }).bindPopup(
                '<strong>' + sensor.name + '</strong>' +
                '<br>Jenis: ' + sensor.type +
                '<br>Stasiun: ' + sensor.station +
                '<br>Provinsi: ' + sensor.province +
                '<br>Status: ' + sensor.status +
                '<br>Sinyal Aktif: Menyala'
            ).addTo(sensorLayer);
        });

        warningStations.forEach(function (station) {
            var color = statusColors[station.status] || '#f1b44c';

            L.marker([station.lat, station.lng], {
                title: station.name
            }).bindPopup(
                '<strong>' + station.name + '</strong>' +
                '<br>Provinsi: ' + station.province +
                '<br>Status: ' + station.status
            ).addTo(warningLayer);

            L.circleMarker([station.lat, station.lng], {
                radius: 11,
                color: color,
                fillColor: '#ffffff',
                fillOpacity: 0.2,
                weight: 3
            }).addTo(warningLayer);
        });

        L.control.layers(null, {
            'Klaster': clusterLayer,
            'Sensor Pemantauan': sensorLayer,
            'Stasiun Peringatan': warningLayer
        }, {
            collapsed: false
        }).addTo(map);
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/dashboard/index.blade.php ENDPATH**/ ?>