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

    function markerIcon(color, size) {
        var markerSize = size || 18;

        return window.L.divIcon({
            className: 'sentinel-spatial-div-marker',
            iconSize: [markerSize, markerSize],
            iconAnchor: [markerSize / 2, markerSize / 2],
            html: '<span style="display:block;width:' + markerSize + 'px;height:' + markerSize + 'px;border-radius:999px;background:' + color + ';border:3px solid #fff;box-shadow:0 8px 18px rgba(15,35,60,.22);"></span>',
        });
    }

    function addMarker(map, point, label, status, color, options) {
        var marker = window.L.marker([point.lat, point.lng], {
            icon: markerIcon(color, options?.size),
            draggable: Boolean(options?.draggable),
            autoPan: true,
        }).addTo(map);

        marker.bindPopup(options?.popupHtml || '<strong>' + escapeHtml(label) + '</strong><br><span>' + escapeHtml(status || '-') + '</span>');

        return marker;
    }

    function relationRows(items, emptyText, formatter) {
        var rows = asArray(items).map(formatter).filter(Boolean);

        if (!rows.length) {
            return '<div class="text-muted" style="font-size:12px;">' + escapeHtml(emptyText) + '</div>';
        }

        return '<div style="display:grid;gap:6px;margin-top:6px;">' + rows.join('') + '</div>';
    }

    function relationItem(title, subtitle, status) {
        return '<div style="border-top:1px solid #e8eef5;padding-top:6px;">' +
            '<div style="font-weight:700;color:#263238;">' + escapeHtml(title || '-') + '</div>' +
            '<div style="font-size:12px;color:#65758b;">' + escapeHtml(subtitle || '-') + '</div>' +
            (status ? '<div style="font-size:12px;color:#2563eb;">' + escapeHtml(status) + '</div>' : '') +
        '</div>';
    }

    function monitoringPopup(station) {
        var title = station.station_code || station.name || 'Monitoring Station';
        var relation = relationRows(station.warning_stations, 'Belum ada warning station terikat.', function (warning) {
            return relationItem(
                [warning.station_code, warning.name].filter(Boolean).join(' - '),
                warning.zone_id ? 'Zone: ' + warning.zone_id : 'Warning Station',
                [warning.status, warning.controller_status].filter(Boolean).join(' / ')
            );
        });

        return '<strong>' + escapeHtml(title) + '</strong>' +
            '<br><span>' + escapeHtml(station.name || station.status || '-') + '</span>' +
            '<div style="margin-top:8px;font-weight:700;color:#071f49;">Warning Station Terikat</div>' +
            relation;
    }

    function warningPopup(station) {
        var title = station.station_code || station.name || 'Warning Station';
        var relation = relationRows(station.sensors, 'Belum ada sensor terikat.', function (sensor) {
            return relationItem(
                [sensor.sensor_code, sensor.name].filter(Boolean).join(' - '),
                [sensor.type, sensor.parameter].filter(Boolean).join(' / '),
                [sensor.status, sensor.alert_level].filter(Boolean).join(' / ')
            );
        });

        return '<strong>' + escapeHtml(title) + '</strong>' +
            '<br><span>' + escapeHtml(station.name || station.status || '-') + '</span>' +
            '<div style="margin-top:8px;font-weight:700;color:#071f49;">Sensor Terikat</div>' +
            relation;
    }

    function formFieldsForStation(type, resource, point) {
        var coordinate = point.lat.toFixed(7) + ', ' + point.lng.toFixed(7);

        if (type === 'monitoring') {
            return {
                workspace_id: resource.workspace_id || '',
                project_id: resource.project_id || '',
                corridor_id: resource.corridor_id || '',
                station_code: resource.station_code || '',
                name: resource.name || '',
                station_type: resource.station_type || 'environmental_monitoring',
                coordinate: coordinate,
                latitude: point.lat.toFixed(7),
                longitude: point.lng.toFixed(7),
                logger_id: resource.logger_id || '',
                connectivity_status: resource.connectivity_status || 'Online',
                logger_status: resource.logger_status || 'Active',
                registration_status: resource.registration_status || 'registered',
                status: resource.status || 'Normal',
            };
        }

        if (type === 'warning') {
            return {
                workspace_id: resource.workspace_id || '',
                monitoring_station_id: resource.monitoring_station_id || '',
                station_code: resource.station_code || '',
                name: resource.name || '',
                zone_id: resource.zone_id || '',
                controller_id: resource.controller_id || '',
                coordinate: coordinate,
                latitude: point.lat.toFixed(7),
                longitude: point.lng.toFixed(7),
                controller_model: resource.controller_model || '',
                controller_vendor: resource.controller_vendor || '',
                output_devices: resource.output_devices || [],
                controller_status: resource.controller_status || 'Standby',
                status: resource.status || 'Normal',
            };
        }

        return {
            workspace_id: resource.workspace_id || '',
            monitoring_station_id: resource.monitoring_station_id || '',
            input_source: resource.input_source || 'data_logger',
            data_logger_id: resource.data_logger_id || '',
            mqtt_configuration_id: resource.mqtt_configuration_id || '',
            warning_station_id: resource.warning_station_id || '',
            mst_prefix_id: resource.mst_prefix_id || '',
            sensor_code: resource.sensor_code || '',
            slave_id: resource.slave_id || '',
            address: resource.address || '',
            function_code: resource.function_code || 'FC03',
            quantity: resource.quantity || 1,
            poll_interval_ms: resource.poll_interval_ms || 1000,
            type: resource.type || 'soil_moisture',
            data_type: resource.data_type || 'uint16',
            parameter: resource.parameter || resource.name || '',
            coordinate: coordinate,
            latitude: point.lat.toFixed(7),
            longitude: point.lng.toFixed(7),
            unit: resource.unit || '',
            scale_factor: resource.scale_factor ?? 1,
            offset: resource.offset ?? 0,
            threshold: resource.threshold || '',
            alert_level: resource.alert_level || 'Normal',
            reading_method: resource.reading_method || 'Absolute',
            status: resource.status || 'Normal',
        };
    }

    function setFormValue(form, name, value) {
        var inputs = form.querySelectorAll('[name="' + name + '"], [name="' + name + '[]"]');
        var values = Array.isArray(value) ? value.map(String) : [String(value ?? '')];

        inputs.forEach(function (input) {
            if (input.type === 'checkbox') {
                input.checked = values.includes(input.value) || value === true || value === 1 || value === '1';
                return;
            }

            if (input.type === 'radio') {
                input.checked = String(input.value) === String(value ?? '');
                return;
            }

            input.value = value ?? '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function openForm(formSelector, fields) {
        var form = document.querySelector(formSelector);

        if (!form) {
            return;
        }

        var pane = form.closest('.tab-pane');
        if (pane && window.bootstrap) {
            var tab = Array.from(document.querySelectorAll('[data-bs-toggle="tab"]')).find(function (trigger) {
                return trigger.dataset.bsTarget === '#' + pane.id;
            });
            if (tab) {
                window.bootstrap.Tab.getOrCreateInstance(tab).show();
            }
        }

        var collapse = form.closest('.collapse');
        if (collapse && window.bootstrap) {
            window.bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
        }

        Object.keys(fields).forEach(function (name) {
            setFormValue(form, name, fields[name]);
        });

        form.classList.add('border', 'border-primary', 'rounded', 'p-2');
        setTimeout(function () {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);
        setTimeout(function () {
            form.classList.remove('border', 'border-primary', 'rounded', 'p-2');
        }, 1600);
    }

    function bindEditableMarker(marker, type, resource) {
        var formSelector = type === 'monitoring'
            ? '#monitoring-form'
            : (type === 'warning' ? '#warning-form' : '#sensor-form');

        function apply(latLng, open) {
            var point = { lat: latLng.lat, lng: latLng.lng };
            var fields = formFieldsForStation(type, resource, point);
            resource.coordinate = fields.coordinate;
            resource.latitude = fields.latitude;
            resource.longitude = fields.longitude;

            if (open) {
                openForm(formSelector, fields);
            }
        }

        marker.on('dragend', function () {
            apply(marker.getLatLng(), true);
        });
    }

    function localMapBounds(points, center) {
        var source = points.length ? points : [center];
        var latitudes = source.map(function (point) { return point.lat; });
        var longitudes = source.map(function (point) { return point.lng; });
        var minLat = Math.min.apply(null, latitudes);
        var maxLat = Math.max.apply(null, latitudes);
        var minLng = Math.min.apply(null, longitudes);
        var maxLng = Math.max.apply(null, longitudes);
        var midLat = (minLat + maxLat) / 2;
        var midLng = (minLng + maxLng) / 2;
        var halfLat = Math.min(Math.max((maxLat - minLat) / 2 + 0.04, 0.06), 0.35);
        var halfLng = Math.min(Math.max((maxLng - minLng) / 2 + 0.04, 0.06), 0.35);

        return window.L.latLngBounds(
            [midLat - halfLat, midLng - halfLng],
            [midLat + halfLat, midLng + halfLng]
        );
    }

    function createLeafletMap(container, resources, allPoints) {
        var workspace = asArray(resources.workspaces)[0] || {};
        var center = resourceCoordinate(workspace);

        if (!validCoordinate(center)) {
            center = allPoints[0] || { lat: -2.5, lng: 118 };
        }

        var lockedBounds = localMapBounds(allPoints, center);
        var defaultZoom = Math.min(Math.max(Number(workspace.basemap?.default_zoom || 12), 11), 15);
        var map = window.L.map(container, {
            minZoom: 10,
            maxZoom: 16,
            maxBounds: lockedBounds,
            maxBoundsViscosity: 0.9,
        }).setView([center.lat, center.lng], defaultZoom);
        var tileUrl = workspace.basemap?.tile_url || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

        window.L.tileLayer(tileUrl, {
            minZoom: 10,
            maxZoom: 16,
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
                bindEditableMarker(addMarker(map, point, station.station_code || station.name || 'Monitoring Station', station.status, '#087b78', {
                    draggable: true,
                    size: 19,
                    popupHtml: monitoringPopup(station),
                }), 'monitoring', station);
            }
        });

        asArray(resources.warningStations).forEach(function (station) {
            var point = resourceCoordinate(station);
            if (validCoordinate(point)) {
                bindEditableMarker(addMarker(map, point, station.station_code || station.name || 'Warning Station', station.status || station.controller_status, '#c9362b', {
                    draggable: true,
                    size: 19,
                    popupHtml: warningPopup(station),
                }), 'warning', station);
            }
        });

        asArray(resources.sensors).forEach(function (sensor) {
            var point = resourceCoordinate(sensor);
            if (validCoordinate(point)) {
                bindEditableMarker(addMarker(
                    map,
                    point,
                    sensor.parameter || sensor.sensor_code || sensor.name || 'Sensor',
                    [sensor.station_code, sensor.parameter, sensor.status || sensor.alert_level].filter(Boolean).join(' / '),
                    '#7c3aed',
                    {
                        draggable: true,
                        size: 15,
                    }
                ), 'sensor', sensor);
            }
        });

        if (allPoints.length > 1) {
            map.fitBounds(lockedBounds, { padding: [28, 28], maxZoom: 14 });
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 150);

        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function () {
                setTimeout(function () {
                    map.invalidateSize();
                    if (allPoints.length > 1) {
                        map.fitBounds(lockedBounds, { padding: [28, 28], maxZoom: 14 });
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
