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
                        <h5 class="mb-0">Daftar Proyek</h5>
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
                        <p class="text-muted mb-1">Klaster</p>
                        <h5 class="mb-0">Basis Data Klaster</h5>
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
                        <p class="text-muted mb-1">Stasiun</p>
                        <h5 class="mb-0">Pemantauan & Peringatan</h5>
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
                        <p class="text-muted mb-1">Telemetri</p>
                        <h5 class="mb-0">Data Sensor</h5>
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
                            <tr>
                                <td>DKI Jakarta</td>
                                <td>1</td>
                                <td>3</td>
                                <td><span class="badge bg-success">Normal</span></td>
                            </tr>
                            <tr>
                                <td>Jawa Barat</td>
                                <td>2</td>
                                <td>5</td>
                                <td><span class="badge bg-warning">Waspada</span></td>
                            </tr>
                            <tr>
                                <td>Jawa Tengah</td>
                                <td>1</td>
                                <td>2</td>
                                <td><span class="badge bg-success">Normal</span></td>
                            </tr>
                            <tr>
                                <td>Sumatera Barat</td>
                                <td>1</td>
                                <td>3</td>
                                <td><span class="badge bg-danger">Bahaya</span></td>
                            </tr>
                            <tr>
                                <td>Sulawesi Selatan</td>
                                <td>1</td>
                                <td>2</td>
                                <td><span class="badge bg-info">Pengujian</span></td>
                            </tr>
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
                            <strong>5</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-3 mb-xl-0">
                            <span class="text-muted">Klaster Aktif</span>
                            <strong>6</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-3 mb-sm-0">
                            <span class="text-muted">Titik Sensor</span>
                            <strong>15</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6">
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Stasiun Peringatan</span>
                            <strong>5</strong>
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

        var clusters = [
            {
                name: 'Klaster Banjir Jakarta Utara',
                province: 'DKI Jakarta',
                city: 'Jakarta Utara',
                hazard: 'Banjir',
                status: 'Normal',
                sensors: 3,
                warnings: 1,
                lat: -6.1214,
                lng: 106.7741
            },
            {
                name: 'Klaster Cekungan Bandung',
                province: 'Jawa Barat',
                city: 'Bandung',
                hazard: 'Banjir',
                status: 'Waspada',
                sensors: 3,
                warnings: 1,
                lat: -6.9175,
                lng: 107.6191
            },
            {
                name: 'Klaster Longsor Bogor',
                province: 'Jawa Barat',
                city: 'Bogor',
                hazard: 'Longsor',
                status: 'Normal',
                sensors: 2,
                warnings: 1,
                lat: -6.5971,
                lng: 106.8060
            },
            {
                name: 'Klaster Banjir Pesisir Semarang',
                province: 'Jawa Tengah',
                city: 'Semarang',
                hazard: 'Banjir',
                status: 'Normal',
                sensors: 2,
                warnings: 1,
                lat: -6.9667,
                lng: 110.4167
            },
            {
                name: 'Klaster Tsunami Padang',
                province: 'Sumatera Barat',
                city: 'Padang',
                hazard: 'Tsunami',
                status: 'Bahaya',
                sensors: 3,
                warnings: 1,
                lat: -0.9471,
                lng: 100.4172
            },
            {
                name: 'Klaster Gempa Bumi Makassar',
                province: 'Sulawesi Selatan',
                city: 'Makassar',
                hazard: 'Gempa Bumi',
                status: 'Pengujian',
                sensors: 2,
                warnings: 1,
                lat: -5.1477,
                lng: 119.4327
            }
        ];

        var sensorPoints = [
            { name: 'TMA-JKT-01', type: 'Sensor Tinggi Muka Air', station: 'Stasiun 1', province: 'DKI Jakarta', lat: -6.1290, lng: 106.8100, status: 'Normal' },
            { name: 'CH-JKT-02', type: 'Sensor Curah Hujan', station: 'Stasiun 1', province: 'DKI Jakarta', lat: -6.1680, lng: 106.8300, status: 'Normal' },
            { name: 'BMS-JKT-03', type: 'Baterai / BMS', station: 'Stasiun 1', province: 'DKI Jakarta', lat: -6.1050, lng: 106.7600, status: 'Normal' },
            { name: 'TMA-BDG-01', type: 'Sensor Tinggi Muka Air', station: 'Stasiun 1', province: 'Jawa Barat', lat: -6.9000, lng: 107.6100, status: 'Waspada' },
            { name: 'CH-BDG-02', type: 'Sensor Curah Hujan', station: 'Stasiun 2', province: 'Jawa Barat', lat: -6.9400, lng: 107.6600, status: 'Normal' },
            { name: 'TMA-BGR-01', type: 'Sensor Tinggi Muka Air', station: 'Stasiun 3', province: 'Jawa Barat', lat: -6.6100, lng: 106.7900, status: 'Normal' },
            { name: 'GT-BGR-02', type: 'Sensor Gerakan Tanah', station: 'Stasiun 3', province: 'Jawa Barat', lat: -6.5550, lng: 106.8500, status: 'Waspada' },
            { name: 'TMA-SMG-01', type: 'Sensor Tinggi Muka Air', station: 'Stasiun 1', province: 'Jawa Tengah', lat: -6.9580, lng: 110.4300, status: 'Normal' },
            { name: 'CH-SMG-02', type: 'Sensor Curah Hujan', station: 'Stasiun 1', province: 'Jawa Tengah', lat: -6.9900, lng: 110.3800, status: 'Normal' },
            { name: 'PS-PDG-01', type: 'Sensor Pasang Surut Tsunami', station: 'Stasiun 1', province: 'Sumatera Barat', lat: -0.9200, lng: 100.3600, status: 'Bahaya' },
            { name: 'GB-PDG-02', type: 'Sensor Getaran Gempa Bumi', station: 'Stasiun 2', province: 'Sumatera Barat', lat: -0.9800, lng: 100.4200, status: 'Bahaya' },
            { name: 'BMS-PDG-03', type: 'Baterai / BMS', station: 'Stasiun 2', province: 'Sumatera Barat', lat: -0.9400, lng: 100.4700, status: 'Normal' },
            { name: 'GB-MKS-01', type: 'Sensor Getaran Gempa Bumi', station: 'Stasiun 1', province: 'Sulawesi Selatan', lat: -5.1300, lng: 119.4200, status: 'Pengujian' },
            { name: 'CH-MKS-02', type: 'Sensor Curah Hujan', station: 'Stasiun 1', province: 'Sulawesi Selatan', lat: -5.1750, lng: 119.4600, status: 'Pengujian' }
        ];

        var warningStations = [
            { name: 'Stasiun Peringatan Jakarta Utara', province: 'DKI Jakarta', lat: -6.1150, lng: 106.7900, status: 'Siap' },
            { name: 'Stasiun Peringatan Bandung', province: 'Jawa Barat', lat: -6.9300, lng: 107.6000, status: 'Siap' },
            { name: 'Stasiun Peringatan Bogor', province: 'Jawa Barat', lat: -6.5900, lng: 106.8350, status: 'Siap' },
            { name: 'Stasiun Peringatan Semarang', province: 'Jawa Tengah', lat: -6.9700, lng: 110.4500, status: 'Siap' },
            { name: 'Stasiun Peringatan Padang', province: 'Sumatera Barat', lat: -0.9550, lng: 100.3500, status: 'Siaga' },
            { name: 'Stasiun Peringatan Makassar', province: 'Sulawesi Selatan', lat: -5.1550, lng: 119.4100, status: 'Pengujian' }
        ];

        <?php
            $clusterCoordinates = [
                'CLS-TSU-PDG' => [-0.9471, 100.4172],
                'CLS-FLD-JKT' => [-6.1214, 106.7741],
            ];

            $clustersData = collect(config('resq_dummy.clusters'))->map(function ($cluster) use ($clusterCoordinates) {
                return [
                    'name' => $cluster['name'],
                    'province' => $cluster['province'],
                    'city' => $cluster['city'],
                    'hazard' => $cluster['hazard'],
                    'status' => $cluster['status'],
                    'sensors' => collect(config('resq_dummy.sensors'))->where('cluster_id', $cluster['id'])->count(),
                    'warnings' => 1,
                    'lat' => $clusterCoordinates[$cluster['id']][0],
                    'lng' => $clusterCoordinates[$cluster['id']][1],
                ];
            })->values();

            $sensorCoordinates = [
                'PS-PDG-01' => [-0.9200, 100.3600],
                'GB-PDG-02' => [-0.9800, 100.4200],
                'TMA-JKT-01' => [-6.1290, 106.8100],
            ];

            $sensorPointsData = collect(config('resq_dummy.sensors'))->map(function ($sensor) use ($sensorCoordinates) {
                return [
                    'name' => $sensor['id'],
                    'type' => $sensor['type'],
                    'station' => $sensor['monitoring_station_id'],
                    'province' => collect(config('resq_dummy.clusters'))->firstWhere('id', $sensor['cluster_id'])['province'],
                    'lat' => $sensorCoordinates[$sensor['id']][0],
                    'lng' => $sensorCoordinates[$sensor['id']][1],
                    'status' => $sensor['status'],
                ];
            })->values();

            $warningCoordinates = [
                'WS-PDG-001' => [-0.9550, 100.3500],
                'WS-JKT-001' => [-6.1150, 106.7900],
            ];

            $warningStationsData = collect(config('resq_dummy.warning_stations'))->map(function ($station) use ($warningCoordinates) {
                return [
                    'name' => $station['id'] . ' - ' . $station['name'],
                    'province' => collect(config('resq_dummy.clusters'))->firstWhere('id', $station['cluster_id'])['province'],
                    'lat' => $warningCoordinates[$station['id']][0],
                    'lng' => $warningCoordinates[$station['id']][1],
                    'status' => $station['status'],
                ];
            })->values();
        ?>

        clusters = <?php echo json_encode($clustersData, 15, 512) ?>;

        sensorPoints = <?php echo json_encode($sensorPointsData, 15, 512) ?>;

        warningStations = <?php echo json_encode($warningStationsData, 15, 512) ?>;

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