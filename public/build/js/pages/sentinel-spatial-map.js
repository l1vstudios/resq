(function (window, document) {
    'use strict';

    function asArray(value) {
        return Array.isArray(value) ? value : [];
    }

    function numberOrNull(value) {
        var number = Number(value);

        return Number.isFinite(number) ? number : null;
    }

    function coordinateFrom(value) {
        if (!value) {
            return null;
        }

        if (Array.isArray(value) && value.length >= 2) {
            return { lat: numberOrNull(value[0]), lng: numberOrNull(value[1]) };
        }

        if (typeof value === 'object') {
            return {
                lat: numberOrNull(value.lat ?? value.latitude),
                lng: numberOrNull(value.lng ?? value.lon ?? value.longitude),
            };
        }

        if (typeof value === 'string') {
            var parts = value.split(',').map(function (part) {
                return numberOrNull(part.trim());
            });

            if (parts.length >= 2) {
                return { lat: parts[0], lng: parts[1] };
            }
        }

        return null;
    }

    function validCoordinate(point) {
        return point
            && Number.isFinite(point.lat)
            && Number.isFinite(point.lng)
            && Math.abs(point.lat) <= 90
            && Math.abs(point.lng) <= 180;
    }

    function coordinateList(value) {
        return asArray(value)
            .map(coordinateFrom)
            .filter(validCoordinate);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resourceCoordinate(resource) {
        return coordinateFrom(resource.coordinate) || {
            lat: numberOrNull(resource.latitude),
            lng: numberOrNull(resource.longitude),
        };
    }

    function boundsFrom(points) {
        if (!points.length) {
            return null;
        }

        var minLat = Math.min.apply(null, points.map(function (point) { return point.lat; }));
        var maxLat = Math.max.apply(null, points.map(function (point) { return point.lat; }));
        var minLng = Math.min.apply(null, points.map(function (point) { return point.lng; }));
        var maxLng = Math.max.apply(null, points.map(function (point) { return point.lng; }));

        return {
            minLat: minLat,
            maxLat: maxLat,
            minLng: minLng,
            maxLng: maxLng,
        };
    }

    function statusTone(status) {
        var value = String(status || '').toLowerCase();

        if (value.includes('awas') || value.includes('critical') || value.includes('danger')) {
            return 'danger';
        }

        if (value.includes('waspada') || value.includes('warning') || value.includes('siaga')) {
            return 'warning';
        }

        if (value.includes('active') || value.includes('normal') || value.includes('online')) {
            return 'success';
        }

        return 'muted';
    }

    function renderFallback(container, resources, points) {
        var bounds = boundsFrom(points);
        var width = Math.max(container.clientWidth || 900, 600);
        var height = Math.max(container.clientHeight || 420, 360);
        var pad = 44;

        function place(point) {
            if (!bounds) {
                return { x: width / 2, y: height / 2 };
            }

            var lngRange = Math.max(bounds.maxLng - bounds.minLng, 0.001);
            var latRange = Math.max(bounds.maxLat - bounds.minLat, 0.001);

            return {
                x: pad + ((point.lng - bounds.minLng) / lngRange) * (width - pad * 2),
                y: height - pad - ((point.lat - bounds.minLat) / latRange) * (height - pad * 2),
            };
        }

        var routeLines = asArray(resources.referenceRoutes).concat(asArray(resources.corridors)).map(function (route) {
            var path = coordinateList(route.path_coordinates);

            if (path.length < 2) {
                return '';
            }

            return '<polyline points="' + path.map(function (point) {
                var p = place(point);
                return p.x + ',' + p.y;
            }).join(' ') + '" fill="none" stroke="' + (route.corridor_code ? '#0f4ea2' : '#087b78') + '" stroke-width="' + (route.corridor_code ? 5 : 3) + '" stroke-linecap="round" opacity="0.82" />';
        }).join('');

        var markers = asArray(resources.monitoringStations).map(function (station) {
            var point = resourceCoordinate(station);

            if (!validCoordinate(point)) {
                return '';
            }

            var p = place(point);

            return '<g><circle cx="' + p.x + '" cy="' + p.y + '" r="8" fill="#087b78" stroke="#fff" stroke-width="3" />' +
                '<text x="' + (p.x + 12) + '" y="' + (p.y - 10) + '" font-size="12" font-weight="700" fill="#071f49">' + escapeHtml(station.station_code) + '</text></g>';
        }).join('');

        var referencePoints = asArray(resources.referencePoints).map(function (pointRow) {
            var point = resourceCoordinate(pointRow);

            if (!validCoordinate(point)) {
                return '';
            }

            var p = place(point);

            return '<g><rect x="' + (p.x - 5) + '" y="' + (p.y - 5) + '" width="10" height="10" rx="2" fill="#d9a223" stroke="#fff" stroke-width="2" />' +
                '<text x="' + (p.x + 10) + '" y="' + (p.y + 4) + '" font-size="11" fill="#526273">' + escapeHtml(pointRow.point_code) + '</text></g>';
        }).join('');

        var warningMarkers = asArray(resources.warningStations).map(function (station) {
            var point = resourceCoordinate(station);

            if (!validCoordinate(point)) {
                return '';
            }

            var p = place(point);

            return '<g><circle cx="' + p.x + '" cy="' + p.y + '" r="8" fill="#c9362b" stroke="#fff" stroke-width="3" />' +
                '<text x="' + (p.x + 12) + '" y="' + (p.y + 18) + '" font-size="12" font-weight="700" fill="#071f49">' + escapeHtml(station.station_code) + '</text></g>';
        }).join('');

        var sensorMarkers = asArray(resources.sensors).map(function (sensor) {
            var point = resourceCoordinate(sensor);

            if (!validCoordinate(point)) {
                return '';
            }

            var p = place(point);

            return '<g><circle cx="' + p.x + '" cy="' + p.y + '" r="5" fill="#7c3aed" stroke="#fff" stroke-width="2" />' +
                '<text x="' + (p.x + 9) + '" y="' + (p.y + 4) + '" font-size="10" fill="#526273">' + escapeHtml(sensor.sensor_code || sensor.parameter || 'Sensor') + '</text></g>';
        }).join('');

        container.innerHTML = '<div class="sentinel-static-map">' +
            '<svg viewBox="0 0 ' + width + ' ' + height + '" role="img" aria-label="Geospatial workspace map">' +
                '<defs><pattern id="sentinelGrid" width="48" height="48" patternUnits="userSpaceOnUse"><path d="M48 0H0V48" fill="none" stroke="#dbe8f3" stroke-width="1"/></pattern></defs>' +
                '<rect width="100%" height="100%" fill="#f6fbfd" />' +
                '<rect width="100%" height="100%" fill="url(#sentinelGrid)" opacity="0.9" />' +
                routeLines + markers + referencePoints + warningMarkers + sensorMarkers +
            '</svg>' +
            '<div class="sentinel-map-legend">' +
                '<span><i style="background:#087b78"></i> Monitoring Station</span>' +
                '<span><i style="background:#c9362b"></i> Warning Station</span>' +
                '<span><i style="background:#7c3aed"></i> Sensor</span>' +
                '<span><i style="background:#0f4ea2"></i> Corridor</span>' +
                '<span><i style="background:#d9a223"></i> Reference Point</span>' +
            '</div>' +
        '</div>';
    }

    function addMarker(map, point, label, status, color, radius) {
        var marker = window.L.circleMarker([point.lat, point.lng], {
            radius: radius || 8,
            color: '#ffffff',
            weight: 2,
            fillColor: color,
            fillOpacity: 0.96,
        }).addTo(map);

        marker.bindPopup('<strong>' + escapeHtml(label) + '</strong><br><span>' + escapeHtml(status || '-') + '</span>');

        return marker;
    }

    function createLeafletMap(container, resources, allPoints) {
        var workspace = asArray(resources.workspaces)[0] || {};
        var center = resourceCoordinate(workspace);

        if (!validCoordinate(center)) {
            center = allPoints[0] || { lat: -2.5, lng: 118 };
        }

        var map = window.L.map(container).setView([center.lat, center.lng], Number(workspace.basemap?.default_zoom || 12));
        var tileUrl = workspace.basemap?.tile_url || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

        window.L.tileLayer(tileUrl, {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        asArray(resources.referenceRoutes).forEach(function (route) {
            var path = coordinateList(route.path_coordinates);
            if (path.length > 1) {
                window.L.polyline(path.map(function (point) { return [point.lat, point.lng]; }), {
                    color: '#087b78',
                    weight: 3,
                    dashArray: '7 5',
                }).addTo(map).bindPopup('<strong>' + escapeHtml(route.id || route.route_code || 'Reference Route') + '</strong>');
            }
        });

        asArray(resources.corridors).forEach(function (corridor) {
            var path = coordinateList(corridor.path_coordinates);
            if (path.length > 1) {
                window.L.polyline(path.map(function (point) { return [point.lat, point.lng]; }), {
                    color: '#0f4ea2',
                    weight: 5,
                }).addTo(map).bindPopup('<strong>' + escapeHtml(corridor.id || corridor.corridor_code || 'Corridor') + '</strong><br>' + escapeHtml(corridor.status || '-'));
            }
        });

        asArray(resources.referencePoints).forEach(function (pointRow) {
            var point = resourceCoordinate(pointRow);
            if (validCoordinate(point)) {
                addMarker(map, point, pointRow.point_code || pointRow.name || 'Reference Point', pointRow.point_type || pointRow.status, '#d9a223');
            }
        });

        asArray(resources.monitoringStations).forEach(function (station) {
            var point = resourceCoordinate(station);
            if (validCoordinate(point)) {
                addMarker(map, point, station.station_code || station.name || 'Monitoring Station', station.status, '#087b78');
            }
        });

        asArray(resources.warningStations).forEach(function (station) {
            var point = resourceCoordinate(station);
            if (validCoordinate(point)) {
                addMarker(map, point, station.station_code || station.name || 'Warning Station', station.status || station.controller_status, '#c9362b');
            }
        });

        asArray(resources.sensors).forEach(function (sensor) {
            var point = resourceCoordinate(sensor);
            if (validCoordinate(point)) {
                addMarker(
                    map,
                    point,
                    sensor.sensor_code || sensor.name || 'Sensor',
                    [sensor.station_code, sensor.parameter, sensor.status || sensor.alert_level].filter(Boolean).join(' / '),
                    '#7c3aed',
                    6
                );
            }
        });

        if (allPoints.length > 1) {
            map.fitBounds(allPoints.map(function (point) { return [point.lat, point.lng]; }), { padding: [28, 28] });
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 150);

        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function () {
                setTimeout(function () {
                    map.invalidateSize();
                    if (allPoints.length > 1) {
                        map.fitBounds(allPoints.map(function (point) { return [point.lat, point.lng]; }), { padding: [28, 28] });
                    }
                }, 80);
            });
        });

        return map;
    }

    function collectPoints(resources) {
        var points = [];

        asArray(resources.workspaces).forEach(function (item) {
            var point = resourceCoordinate(item);
            if (validCoordinate(point)) points.push(point);
        });

        asArray(resources.referenceRoutes).concat(asArray(resources.corridors)).forEach(function (item) {
            points = points.concat(coordinateList(item.path_coordinates));
        });

        asArray(resources.referencePoints).concat(asArray(resources.monitoringStations)).concat(asArray(resources.warningStations)).concat(asArray(resources.sensors)).forEach(function (item) {
            var point = resourceCoordinate(item);
            if (validCoordinate(point)) points.push(point);
        });

        return points;
    }

    function createConfigurationMap(id, resources) {
        var container = document.getElementById(id);

        if (!container) {
            return null;
        }

        var points = collectPoints(resources || {});
        container.innerHTML = '';

        if (!points.length) {
            container.innerHTML = '<div class="sentinel-map-empty"><strong>No spatial data yet.</strong><span>Add workspace coordinates, corridor paths, station placement, routes, or reference points.</span></div>';
            return null;
        }

        if (!window.L) {
            renderFallback(container, resources || {}, points);
            return null;
        }

        return createLeafletMap(container, resources || {}, points);
    }

    window.SentinelSpatialMap = {
        createConfigurationMap: createConfigurationMap,
    };
})(window, document);
