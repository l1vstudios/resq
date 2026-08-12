@php
    $mapId = $mapId ?? 'ops-map';
    $points = collect($points ?? [])->values();
    $lines = collect($lines ?? [])->values();
    $mapStyle = $mapStyle ?? 'standard';
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var mapEl = document.getElementById(@json($mapId));
        var points = @json($points);
        var lines = @json($lines);
        var mapStyle = @json($mapStyle);
        var terrainMode = mapStyle === 'terrain3d';

        if (!mapEl || typeof L === 'undefined') {
            return;
        }

        mapEl.classList.toggle('ops-map-terrain-3d', terrainMode);

        var map = L.map(mapEl, {
            scrollWheelZoom: false,
            zoomControl: true,
            preferCanvas: true
        }).setView([-2.5, 118], 5);
        var baseLayer = terrainMode
            ? L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                maxZoom: 17,
                attribution: '&copy; OpenStreetMap & OpenTopoMap'
            })
            : L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '&copy; OpenStreetMap'
            });
        baseLayer.addTo(map);

        if (terrainMode) {
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Elevation/World_Hillshade/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 16,
                opacity: 0.34,
                attribution: 'Hillshade &copy; Esri'
            }).addTo(map);
        }

        var bounds = [];

        lines.forEach(function (line) {
            var path = coordinateList(line.path || line.path_coordinates);

            if (path.length < 2) {
                return;
            }

            var color = line.type === 'route' ? '#087b78' : '#0f4ea2';
            var polyline = L.polyline(path.map(function (point) {
                bounds.push([point.lat, point.lng]);
                return [point.lat, point.lng];
            }), {
                color: color,
                weight: line.type === 'route' ? 3 : 5,
                opacity: 0.88,
                dashArray: line.type === 'route' ? '8 6' : null
            }).addTo(map);

            polyline.bindPopup('<strong>' + escapeHtml(line.label || line.code || 'Corridor') + '</strong><br>' + escapeHtml(line.name || line.status || ''));
        });

        points.forEach(function (point) {
            if (point.lat === null || point.lng === null) {
                return;
            }

            var label = point.label || point.project_code || point.station_code || point.sensor_code || point.name || 'Location';
            var marker = L.circleMarker([point.lat, point.lng], {
                radius: markerRadius(point.type),
                color: '#ffffff',
                weight: 2,
                fillColor: markerColor(point.state || point.status || point.alert_level, point.type),
                fillOpacity: 0.95
            }).addTo(map);
            var popup = '<strong>' + escapeHtml(label) + '</strong><br>' + escapeHtml(point.name || point.parameter || '');
            if (point.asset_label) {
                popup += '<br><span>' + escapeHtml(point.asset_label) + '</span>';
            }
            if (point.state || point.status) {
                popup += '<br><span>' + escapeHtml(point.state || point.status) + '</span>';
            }
            if (point.url) {
                popup += '<br><a href="' + point.url + '">Open</a>';
            }
            marker.bindPopup(popup);
            bounds.push([point.lat, point.lng]);
        });

        if (bounds.length) {
            map.fitBounds(bounds, { padding: [28, 28], maxZoom: terrainMode ? 13 : 12 });
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 250);

        if (terrainMode) {
            var terrainBadge = L.control({ position: 'topleft' });
            terrainBadge.onAdd = function () {
                var div = L.DomUtil.create('div', 'sentinel-map-mode');
                div.innerHTML = '<strong>GIS Terrain</strong><span>Contour + hillshade</span>';
                return div;
            };
            terrainBadge.addTo(map);
        }

        var legend = L.control({ position: 'bottomright' });
        legend.onAdd = function () {
            var div = L.DomUtil.create('div', 'sentinel-map-legend');
            div.innerHTML = [
                '<span><i style="background:#087b78"></i> Monitoring</span>',
                '<span><i style="background:#c9362b"></i> Warning</span>',
                '<span><i style="background:#7c3aed"></i> Sensor</span>',
                '<span><i style="background:#0f4ea2"></i> Corridor</span>'
            ].join('');
            return div;
        };
        legend.addTo(map);

        function markerColor(status, type) {
            if (type === 'monitoring_station') return '#087b78';
            if (type === 'warning_station') return '#c9362b';
            if (type === 'sensor') return '#7c3aed';

            var value = String(status || '').toUpperCase();
            if (['AWAS', 'CRITICAL', 'DANGER'].includes(value)) return '#f46a6a';
            if (['WASPADA', 'SIAGA', 'WARNING', 'DEGRADED', 'UNKNOWN'].includes(value)) return '#f1b44c';
            if (['OFFLINE', 'INACTIVE'].includes(value)) return '#74788d';
            return '#34c38f';
        }

        function markerRadius(type) {
            if (type === 'sensor') return 5;
            if (type === 'warning_station') return 9;

            return 8;
        }

        function coordinateList(value) {
            if (!Array.isArray(value)) {
                return [];
            }

            return value.map(function (row) {
                if (Array.isArray(row) && row.length >= 2) {
                    return { lat: Number(row[0]), lng: Number(row[1]) };
                }

                return {
                    lat: Number(row.lat ?? row.latitude),
                    lng: Number(row.lng ?? row.lon ?? row.longitude)
                };
            }).filter(function (point) {
                return Number.isFinite(point.lat) && Number.isFinite(point.lng);
            });
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function (char) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
            });
        }
    });
</script>
