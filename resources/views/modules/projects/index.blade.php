@extends('layouts.master')

@section('title') Project Setup @endsection

@section('css')
<link href="{{ URL::asset('build/libs/leaflet/leaflet.css') }}" rel="stylesheet" type="text/css" />
<style>
    .sentinel-config-map {
        min-height: 420px;
        border: 1px solid #eff2f7;
        border-radius: 6px;
        overflow: hidden;
        position: relative;
    }

    .spatial-coordinate-input {
        min-height: 96px;
        font-family: monospace;
    }

    .sentinel-static-map,
    .sentinel-static-map svg {
        min-height: 420px;
        width: 100%;
    }

    .sentinel-map-legend {
        align-items: center;
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid #d8e3f0;
        border-radius: 6px;
        bottom: 14px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        left: 14px;
        padding: 8px 10px;
        position: absolute;
        z-index: 400;
    }

    .sentinel-map-legend span {
        align-items: center;
        color: #526273;
        display: inline-flex;
        font-size: 12px;
        font-weight: 700;
        gap: 6px;
    }

    .sentinel-map-legend i {
        border-radius: 999px;
        display: inline-block;
        height: 9px;
        width: 9px;
    }

    .sentinel-map-empty {
        align-items: center;
        background: linear-gradient(135deg, #f6fbfd, #ffffff);
        color: #65758b;
        display: flex;
        flex-direction: column;
        gap: 4px;
        justify-content: center;
        min-height: 420px;
        text-align: center;
    }

    .sentinel-map-empty strong {
        color: #071f49;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Project Configuration @endslot
@slot('title') Project Setup @endslot
@endcomponent

@php
    $project = $project ?? config('resq_dummy.project');
    $projects = collect($projects ?? []);
    $clusters = collect($clusters ?? config('resq_dummy.clusters'));
    $monitoringStations = collect($monitoringStations ?? config('resq_dummy.monitoring_stations'));
    $warningStations = collect($warningStations ?? config('resq_dummy.warning_stations'));
    $sensors = collect($sensors ?? config('resq_dummy.sensors'));
    $dataLoggers = collect($dataLoggers ?? config('resq_dummy.data_loggers'));
    $mqttProjects = collect($mqttProjects ?? []);
    $mqttConfigurations = collect($mqttConfigurations ?? []);
    $mstPrefixes = collect($mstPrefixes ?? []);
    $responsePlans = collect($responsePlans ?? []);
    $informationLayers = collect($informationLayers ?? []);
    $referenceRoutes = collect($referenceRoutes ?? []);
    $corridors = collect($corridors ?? []);
    $referencePoints = collect($referencePoints ?? []);
    $stationSpatialReferences = collect($stationSpatialReferences ?? []);
    $spatialResources = $spatialResources ?? [];
    $permissions = $permissions ?? [];
    $canCreateSpatial = (bool) ($permissions['canCreateSpatial'] ?? false);
    $canEditSpatial = (bool) ($permissions['canEditSpatial'] ?? false);
    $canDeleteSpatial = (bool) ($permissions['canDeleteSpatial'] ?? false);
    $canMutateAssetRegistry = (bool) ($permissions['canMutateAssetRegistry'] ?? false);
    $canWriteSpatial = $canCreateSpatial || $canEditSpatial;
    $provinces = $provinces ?? config('indonesia.provinces') ?? [];
    $databaseReady = $databaseReady ?? false;
    $sensorTypes = [
        'water_level' => 'Water Level / TMA',
        'rain_gauge' => 'Rain Gauge / Curah Hujan',
        'tide_level' => 'Tide Level / Pasang Surut',
        'seismic_vibration' => 'Seismic / Ground Vibration',
        'ground_movement' => 'Ground Movement / Landslide',
        'soil_moisture' => 'Soil Moisture',
        'river_flow' => 'River Flow / Debit',
        'weather_station' => 'Weather Station',
        'temperature' => 'Temperature',
        'humidity' => 'Kelembapan',
        'pressure' => 'Tekanan Udara',
        'wind_speed' => 'Kecepatan Angin',
        'wind_direction' => 'Arah Angin',
        'battery_bms' => 'Battery / BMS',
        'solar_charger' => 'Solar Charger',
        'device_health' => 'Device Health',
    ];
    $dataLoggerTypes = [
        'float32' => 'Float 32-bit',
        'float64' => 'Float 64-bit / Double',
        'int8' => 'Integer 8-bit',
        'int16' => 'Integer 16-bit',
        'int32' => 'Integer 32-bit',
        'int64' => 'Integer 64-bit',
        'uint8' => 'Unsigned Integer 8-bit',
        'uint16' => 'Unsigned Integer 16-bit',
        'uint32' => 'Unsigned Integer 32-bit',
        'uint64' => 'Unsigned Integer 64-bit',
        'boolean' => 'Boolean',
        'string' => 'String / Text',
        'ascii' => 'ASCII',
        'hex' => 'Hexadecimal',
        'byte' => 'Byte',
        'raw' => 'Raw Payload',
    ];
    $weatherParameters = [
        'temperature' => 'Suhu',
        'humidity' => 'Kelembapan',
        'pressure' => 'Tekanan Udara',
        'wind_speed' => 'Kecepatan Angin',
        'wind_direction' => 'Arah Angin',
        'rainfall' => 'Curah Hujan',
        'solar_radiation' => 'Radiasi Matahari',
        'battery_voltage' => 'Tegangan Baterai',
    ];

    $selectedWorkspace = $clusters->first();
    $selectedMonitoringStation = $monitoringStations->first();
    $selectedWarningStation = $warningStations->first();
    $selectedSensor = $sensors->first();
    $loggerSectionOpen = old('logger_code') !== null;
    $mqttSectionOpen = old('configuration_code') !== null;
    $sensorSectionOpen = old('sensor_code') !== null;
@endphp

@if (session('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Data belum bisa disimpan.</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@unless ($databaseReady)
    <div class="alert alert-warning">
        Database setup belum dimigrate. Jalankan <code>php artisan migrate</code> agar form CRUD bisa menyimpan data.
    </div>
@endunless

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="card-title mb-1">Project Setup Flow</h4>
                        <p class="text-muted mb-0">{{ $project['name'] ?? 'RESQ Project' }} - {{ $project['id'] ?? '-' }}</p>
                    </div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary">{{ $projects->count() }} project</span>
                        <span class="badge bg-success-subtle text-success">{{ $clusters->count() }} workspace</span>
                        <span class="badge bg-info-subtle text-info">{{ $monitoringStations->count() }} monitoring</span>
                        <span class="badge bg-warning-subtle text-warning">{{ $warningStations->count() }} warning</span>
                        <span class="badge bg-danger-subtle text-danger">{{ $sensors->count() }} sensor</span>
                    </div>
                </div>

                <ul class="nav nav-tabs nav-tabs-custom flex-wrap" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="project-tab" data-bs-toggle="tab" data-bs-target="#project-tab-pane" type="button" role="tab">Project</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="geospatial-tab" data-bs-toggle="tab" data-bs-target="#geospatial-tab-pane" type="button" role="tab">Geospatial</button></li>
                    @if($canMutateAssetRegistry)
                    <li class="nav-item" role="presentation"><button class="nav-link" id="monitoring-tab" data-bs-toggle="tab" data-bs-target="#monitoring-tab-pane" type="button" role="tab">Monitoring Station</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="warning-tab" data-bs-toggle="tab" data-bs-target="#warning-tab-pane" type="button" role="tab">Warning Station</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="data-tab" data-bs-toggle="tab" data-bs-target="#data-tab-pane" type="button" role="tab">Sensor & Data</button></li>
                    @endif
                    <li class="nav-item" role="presentation"><button class="nav-link" id="canonical-tab" data-bs-toggle="tab" data-bs-target="#canonical-tab-pane" type="button" role="tab">Canonical Data</button></li>
                    @if($canMutateAssetRegistry)
                    <li class="nav-item" role="presentation"><button class="nav-link" id="operation-tab" data-bs-toggle="tab" data-bs-target="#operation-tab-pane" type="button" role="tab">Operational & Response</button></li>
                    @endif
                    <li class="nav-item" role="presentation"><button class="nav-link" id="user-setup-tab" data-bs-toggle="tab" data-bs-target="#user-setup-tab-pane" type="button" role="tab">User Setup</button></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="tab-content text-muted">
    <div class="tab-pane fade show active" id="project-tab-pane" role="tabpanel" aria-labelledby="project-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Set Up New Project</h4>
                        <form method="POST" action="{{ route('projects.store') }}" id="project-form">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Project ID</label>
                                <input type="text" name="project_code" class="form-control" value="{{ old('project_code') }}" placeholder="PRJ-RESQ-001" required @disabled(! $databaseReady)>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Project Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required @disabled(! $databaseReady)>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Project Owner</label>
                                <input type="text" name="owner" class="form-control" value="{{ old('owner') }}" @disabled(! $databaseReady)>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Project Date</label>
                                <input type="date" name="project_date" class="form-control" value="{{ old('project_date') }}" @disabled(! $databaseReady)>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Radius Area Terdampak (km)</label>
                                <input type="number" name="impact_radius_km" class="form-control" value="{{ old('impact_radius_km', 25) }}" min="0.1" max="1000" step="0.1" required @disabled(! $databaseReady)>
                            </div>
                            <input type="hidden" name="status" value="Active">
                            <button type="submit" class="btn btn-primary" @disabled(! $databaseReady)>
                                <i class="bx bx-save me-1"></i> Save / Update Project
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Project List</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Name</th>
                                        <th>Owner</th>
                                        <th>Date</th>
                                        <th>Radius Terdampak</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($projects as $item)
                                        <tr>
                                            <td>{{ $item['id'] }}</td>
                                            <td>{{ $item['name'] }}</td>
                                            <td>{{ $item['owner'] }}</td>
                                            <td>{{ $item['date'] }}</td>
                                            <td>{{ number_format((float) $item['impact_radius_km'], 1) }} km</td>
                                            <td><span class="badge bg-success">{{ $item['status'] }}</span></td>
                                            <td class="text-end">
                                                @isset($item['db_id'])
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#project-form"
                                                            data-edit-fields="{{ base64_encode(json_encode([
                                                                'project_code' => $item['id'] ?? '',
                                                                'name' => $item['name'] ?? '',
                                                                'owner' => $item['owner'] ?? '',
                                                                'project_date' => $item['date'] ?? '',
                                                                'impact_radius_km' => $item['impact_radius_km'] ?? 25,
                                                                'status' => $item['status'] ?? 'Active',
                                                            ])) }}">Edit</button>
                                                        <form method="POST" action="{{ route('project-setup.destroy', ['type' => 'project', 'id' => $item['db_id']]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                @endisset
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">Belum ada project database.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="geospatial-tab-pane" role="tabpanel" aria-labelledby="geospatial-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Add Geospatial Workspace</h4>
                        <form method="POST" action="{{ route('project-workspaces.store') }}" id="workspace-form">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Project</label>
                                <select name="project_id" class="form-select" required @disabled(! $databaseReady || $projects->isEmpty())>
                                    @foreach ($projects as $item)
                                        <option value="{{ $item['db_id'] }}">{{ $item['id'] }} - {{ $item['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Workspace ID</label>
                                <input type="text" name="workspace_code" class="form-control" placeholder="CLS-TSU-PDG" required @disabled(! $databaseReady)>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Workspace Name</label>
                                <input type="text" name="name" class="form-control" required @disabled(! $databaseReady)>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Province</label>
                                <select name="province" class="form-select" required @disabled(! $databaseReady)>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province }}">{{ $province }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Hazard</label>
                                    <input type="text" name="hazard" class="form-control" @disabled(! $databaseReady)>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control" @disabled(! $databaseReady)>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3"><label class="form-label">Beneficiaries</label><input type="number" name="beneficiaries" class="form-control" value="0" @disabled(! $databaseReady)></div>
                                <div class="col-md-4 mb-3"><label class="form-label">Latitude</label><input type="number" step="0.0000001" name="latitude" class="form-control" @disabled(! $databaseReady)></div>
                                <div class="col-md-4 mb-3"><label class="form-label">Longitude</label><input type="number" step="0.0000001" name="longitude" class="form-control" @disabled(! $databaseReady)></div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Basemap Provider</label>
                                    <input name="basemap_provider" class="form-control" value="OpenStreetMap" @disabled(! $databaseReady)>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">Basemap Tile URL</label>
                                    <input name="basemap_tile_url" class="form-control" placeholder="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" @disabled(! $databaseReady)>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Default Zoom</label>
                                    <input type="number" name="default_zoom" class="form-control" value="5" min="1" max="18" @disabled(! $databaseReady)>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Map Bounds</label>
                                <input name="map_bounds" class="form-control" placeholder="[[south, west], [north, east]]" @disabled(! $databaseReady)>
                            </div>
                            <input type="hidden" name="status" value="Normal">
                            <button type="submit" class="btn btn-primary" @disabled(! $databaseReady || $projects->isEmpty() || ! $canWriteSpatial)>Save / Update Workspace</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Workspace Listing</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Province</th><th>City</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($clusters as $cluster)
                                        <tr>
                                            <td>{{ $cluster['id'] }}</td>
                                            <td>{{ $cluster['name'] }}</td>
                                            <td>{{ $cluster['province'] }}</td>
                                            <td>{{ $cluster['city'] }}</td>
                                            <td><span class="badge {{ $cluster['status'] === 'Danger' ? 'bg-danger' : 'bg-success' }}">{{ $cluster['status'] }}</span></td>
                                            <td class="text-end">
                                                @isset($cluster['db_id'])
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#workspace-form"
                                                            data-edit-fields="{{ base64_encode(json_encode([
                                                                'project_id' => $cluster['project_db_id'] ?? '',
                                                                'workspace_code' => $cluster['id'] ?? '',
                                                                'name' => $cluster['name'] ?? '',
                                                                'province' => $cluster['province'] ?? '',
                                                                'hazard' => $cluster['hazard'] ?? '',
                                                                'city' => $cluster['city'] ?? '',
                                                                'beneficiaries' => $cluster['beneficiaries'] ?? 0,
                                                                'latitude' => $cluster['latitude'] ?? '',
                                                                'longitude' => $cluster['longitude'] ?? '',
                                                                'status' => $cluster['status'] ?? 'Normal',
                                                                'basemap_provider' => $cluster['basemap_provider'] ?? 'OpenStreetMap',
                                                                'basemap_tile_url' => $cluster['basemap_tile_url'] ?? '',
                                                                'default_zoom' => $cluster['default_zoom'] ?? 5,
                                                                'map_bounds' => json_encode($cluster['map_bounds'] ?? []),
                                                            ])) }}">Edit</button>
                                                        <form method="POST" action="{{ route('project-setup.destroy', ['type' => 'workspace', 'id' => $cluster['db_id']]) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form>
                                                    </div>
                                                @endisset
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
                            <h4 class="card-title mb-0">Geospatial Workspace Map</h4>
                            <span class="badge bg-primary-subtle text-primary">Configuration Map</span>
                        </div>
                        <div id="project-spatial-config-map" class="sentinel-config-map"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Information Layer Module</h4>
                        <form method="POST" action="{{ route('project-information-layers.store') }}" id="information-layer-form">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 mb-3"><label class="form-label">Project</label><select name="project_id" class="form-select" required @disabled(! $databaseReady || $projects->isEmpty())>@foreach ($projects as $item)<option value="{{ $item['db_id'] }}">{{ $item['id'] }} - {{ $item['name'] }}</option>@endforeach</select></div>
                                <div class="col-md-4 mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" @disabled(! $databaseReady)><option value="">Shared Project Layer</option>@foreach ($clusters->whereNotNull('db_id') as $cluster)<option value="{{ $cluster['db_id'] }}">{{ $cluster['id'] }}</option>@endforeach</select></div>
                                <div class="col-md-4 mb-3"><label class="form-label">Layer ID</label><input name="layer_code" class="form-control" placeholder="LYR-FLOOD-PDG" required @disabled(! $databaseReady)></div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required @disabled(! $databaseReady)></div>
                                <div class="col-md-3 mb-3"><label class="form-label">Type</label><input name="layer_type" class="form-control" value="overlay" required @disabled(! $databaseReady)></div>
                                <div class="col-md-2 mb-3"><label class="form-label">Color</label><input name="style_color" class="form-control" value="#556ee6" @disabled(! $databaseReady)></div>
                                <div class="col-md-3 mb-3"><label class="form-label">Status</label><select name="status" class="form-select" required @disabled(! $databaseReady)><option>Active</option><option>Inactive</option></select></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Source URL</label><input name="source_url" class="form-control" @disabled(! $databaseReady)></div>
                            <div class="mb-3"><label class="form-label">Layer Payload</label><textarea name="layer_payload" class="form-control spatial-coordinate-input" placeholder='{"points":[[-0.9,100.3]]}' @disabled(! $databaseReady)></textarea></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="visible_by_default" value="1" checked @disabled(! $databaseReady)><label class="form-check-label">Visible by default</label></div>
                            <input type="hidden" name="sort_order" value="0">
                            <button class="btn btn-primary" @disabled(! $databaseReady || ! $canWriteSpatial)>Save / Update Layer</button>
                        </form>
                        <div class="table-responsive mt-4">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Project</th><th>Workspace</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    @forelse ($informationLayers as $layer)
                                        <tr>
                                            <td>{{ $layer['id'] }}</td><td>{{ $layer['name'] }}</td><td>{{ $layer['project_id'] }}</td><td>{{ $layer['workspace_id'] ?? 'Shared' }}</td><td><span class="badge bg-success">{{ $layer['status'] }}</span></td>
                                            <td class="text-end">
                                                @isset($layer['db_id'])
                                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                                        data-edit-form="#information-layer-form"
                                                        data-edit-fields="{{ base64_encode(json_encode([
                                                            'project_id' => $layer['project_db_id'] ?? '',
                                                            'workspace_id' => $layer['workspace_db_id'] ?? '',
                                                            'layer_code' => $layer['id'] ?? '',
                                                            'name' => $layer['name'] ?? '',
                                                            'layer_type' => $layer['layer_type'] ?? 'overlay',
                                                            'source_url' => $layer['source_url'] ?? '',
                                                            'style_color' => $layer['style_color'] ?? '',
                                                            'layer_payload' => json_encode($layer['layer_payload'] ?? []),
                                                            'visible_by_default' => $layer['visible_by_default'] ?? true,
                                                            'sort_order' => $layer['sort_order'] ?? 0,
                                                            'status' => $layer['status'] ?? 'Active',
                                                        ])) }}">Edit</button>
                                                @endisset
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada information layer.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Route Registry & Corridor Monitoring</h4>
                        <div class="row">
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('project-reference-routes.store') }}" id="reference-route-form">
                                    @csrf
                                    <div class="mb-3"><label class="form-label">Project</label><select name="project_id" class="form-select" required @disabled(! $databaseReady || $projects->isEmpty())>@foreach ($projects as $item)<option value="{{ $item['db_id'] }}">{{ $item['id'] }} - {{ $item['name'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" @disabled(! $databaseReady)><option value="">Shared Reference Route</option>@foreach ($clusters->whereNotNull('db_id') as $cluster)<option value="{{ $cluster['db_id'] }}">{{ $cluster['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Route ID</label><input name="route_code" class="form-control" placeholder="RTE-PDG-001" required @disabled(! $databaseReady)></div>
                                    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required @disabled(! $databaseReady)></div>
                                    <div class="mb-3"><label class="form-label">Route Type</label><input name="route_type" class="form-control" value="reference" required @disabled(! $databaseReady)></div>
                                    <div class="mb-3"><label class="form-label">Path Coordinates</label><textarea name="path_coordinates" class="form-control spatial-coordinate-input" placeholder="[[-0.92,100.36],[-0.91,100.38]]" @disabled(! $databaseReady)></textarea></div>
                                    <input type="hidden" name="status" value="Active">
                                    <button class="btn btn-primary" @disabled(! $databaseReady || ! $canWriteSpatial)>Save / Update Route</button>
                                </form>
                            </div>
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('project-corridors.store') }}" id="corridor-form">
                                    @csrf
                                    <div class="mb-3"><label class="form-label">Project</label><select name="project_id" class="form-select" required @disabled(! $databaseReady || $projects->isEmpty())>@foreach ($projects as $item)<option value="{{ $item['db_id'] }}">{{ $item['id'] }} - {{ $item['name'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" required @disabled(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty())>@foreach ($clusters->whereNotNull('db_id') as $cluster)<option value="{{ $cluster['db_id'] }}">{{ $cluster['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Reference Route</label><select name="reference_route_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($referenceRoutes as $route)<option value="{{ $route['db_id'] }}">{{ $route['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Corridor ID</label><input name="corridor_code" class="form-control" placeholder="COR-PDG-001" required @disabled(! $databaseReady)></div>
                                    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required @disabled(! $databaseReady)></div>
                                    <div class="mb-3"><label class="form-label">Path Coordinates</label><textarea name="path_coordinates" class="form-control spatial-coordinate-input" placeholder="[[-0.92,100.36],[-0.91,100.38]]" @disabled(! $databaseReady)></textarea></div>
                                    <input type="hidden" name="status" value="Planned">
                                    <button class="btn btn-primary" @disabled(! $databaseReady || ! $canWriteSpatial)>Save / Update Corridor</button>
                                </form>
                            </div>
                        </div>
                        <div class="table-responsive mt-4">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>Corridor</th><th>Workspace</th><th>Route</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    @forelse ($corridors as $corridor)
                                        <tr>
                                            <td>{{ $corridor['id'] }} - {{ $corridor['name'] }}</td><td>{{ $corridor['workspace_id'] }}</td><td>{{ $corridor['reference_route_id'] ?? '-' }}</td><td><span class="badge bg-info">{{ $corridor['status'] }}</span></td>
                                            <td class="text-end">
                                                @isset($corridor['db_id'])
                                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                                        data-edit-form="#corridor-form"
                                                        data-edit-fields="{{ base64_encode(json_encode([
                                                            'project_id' => $corridor['project_db_id'] ?? '',
                                                            'workspace_id' => $corridor['workspace_db_id'] ?? '',
                                                            'reference_route_id' => $corridor['reference_route_db_id'] ?? '',
                                                            'corridor_code' => $corridor['id'] ?? '',
                                                            'name' => $corridor['name'] ?? '',
                                                            'path_coordinates' => json_encode($corridor['path_coordinates'] ?? []),
                                                            'status' => $corridor['status'] ?? 'Planned',
                                                        ])) }}">Edit</button>
                                                @endisset
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">Belum ada corridor monitoring.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Reference Points & Station Spatial Placement</h4>
                        <div class="row">
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('project-reference-points.store') }}" id="reference-point-form">
                                    @csrf
                                    <div class="mb-3"><label class="form-label">Project</label><select name="project_id" class="form-select" required @disabled(! $databaseReady || $projects->isEmpty())>@foreach ($projects as $item)<option value="{{ $item['db_id'] }}">{{ $item['id'] }} - {{ $item['name'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" @disabled(! $databaseReady)><option value="">Project Reference Point</option>@foreach ($clusters->whereNotNull('db_id') as $cluster)<option value="{{ $cluster['db_id'] }}">{{ $cluster['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Corridor</label><select name="corridor_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($corridors as $corridor)<option value="{{ $corridor['db_id'] }}">{{ $corridor['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Point ID</label><input name="point_code" class="form-control" placeholder="REF-PDG-001" required @disabled(! $databaseReady)></div>
                                    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required @disabled(! $databaseReady)></div>
                                    <div class="mb-3"><label class="form-label">Point Type</label><input name="point_type" class="form-control" value="reference" required @disabled(! $databaseReady)></div>
                                    <div class="mb-3"><label class="form-label">Coordinate</label><input name="coordinate" class="form-control" placeholder="-0.9200, 100.3600" @disabled(! $databaseReady)></div>
                                    <input type="hidden" name="status" value="Active">
                                    <button class="btn btn-primary" @disabled(! $databaseReady || ! $canWriteSpatial)>Save / Update Point</button>
                                </form>
                            </div>
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('project-station-spatial-references.store') }}" id="station-spatial-reference-form">
                                    @csrf
                                    <div class="mb-3"><label class="form-label">Project</label><select name="project_id" class="form-select" required @disabled(! $databaseReady || $projects->isEmpty())>@foreach ($projects as $item)<option value="{{ $item['db_id'] }}">{{ $item['id'] }} - {{ $item['name'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" required @disabled(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty())>@foreach ($clusters->whereNotNull('db_id') as $cluster)<option value="{{ $cluster['db_id'] }}">{{ $cluster['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Corridor</label><select name="corridor_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($corridors as $corridor)<option value="{{ $corridor['db_id'] }}">{{ $corridor['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Reference Route</label><select name="reference_route_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($referenceRoutes as $route)<option value="{{ $route['db_id'] }}">{{ $route['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Reference Point</label><select name="reference_point_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($referencePoints as $point)<option value="{{ $point['db_id'] }}">{{ $point['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Monitoring Station</label><select name="monitoring_station_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($monitoringStations->whereNotNull('db_id') as $station)<option value="{{ $station['db_id'] }}">{{ $station['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Warning Station</label><select name="warning_station_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($warningStations->whereNotNull('db_id') as $station)<option value="{{ $station['db_id'] }}">{{ $station['id'] }}</option>@endforeach</select></div>
                                    <div class="mb-3"><label class="form-label">Placement Role</label><input name="placement_role" class="form-control" value="corridor_reference" required @disabled(! $databaseReady)></div>
                                    <div class="mb-3"><label class="form-label">Station Offset</label><input name="station_offset" class="form-control" placeholder="KM 12+400 / upstream" @disabled(! $databaseReady)></div>
                                    <input type="hidden" name="status" value="Active">
                                    <button class="btn btn-primary" @disabled(! $databaseReady || ! $canWriteSpatial)>Save / Update Placement</button>
                                </form>
                            </div>
                        </div>
                        <div class="table-responsive mt-4">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>Station</th><th>Workspace</th><th>Corridor</th><th>Reference</th><th>Role</th></tr></thead>
                                <tbody>
                                    @forelse ($stationSpatialReferences as $reference)
                                        <tr><td>{{ $reference['monitoring_station_id'] ?? $reference['warning_station_id'] ?? '-' }}</td><td>{{ $reference['workspace_id'] }}</td><td>{{ $reference['corridor_id'] ?? '-' }}</td><td>{{ $reference['reference_point_id'] ?? $reference['reference_route_id'] ?? '-' }}</td><td>{{ $reference['placement_role'] }}</td></tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">Belum ada station spatial reference.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canMutateAssetRegistry)
    <div class="tab-pane fade" id="monitoring-tab-pane" role="tabpanel" aria-labelledby="monitoring-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Monitoring Station Registry</h4>
                        <form method="POST" action="{{ route('project-monitoring-stations.store') }}" id="monitoring-form">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Geospatial Workspace</label>
                                <select name="workspace_id" class="form-select" required @disabled(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty())>
                                    @foreach ($clusters->whereNotNull('db_id') as $cluster)
                                        <option value="{{ $cluster['db_id'] }}">{{ $cluster['id'] }} - {{ $cluster['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Corridor</label>
                                <select name="corridor_id" class="form-select" @disabled(! $databaseReady)>
                                    <option value="">-</option>
                                    @foreach ($corridors as $corridor)
                                        <option value="{{ $corridor['db_id'] }}">{{ $corridor['id'] }} - {{ $corridor['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3"><label class="form-label">Station ID</label><input name="station_code" class="form-control" placeholder="MS-PDG-001" required @disabled(! $databaseReady)></div>
                            <div class="mb-3"><label class="form-label">Station Name</label><input name="name" class="form-control" required @disabled(! $databaseReady)></div>
                            <div class="mb-3"><label class="form-label">Station Type</label><input name="station_type" class="form-control" value="environmental_monitoring" required @disabled(! $databaseReady)></div>
                            <div class="mb-3"><label class="form-label">Coordinate</label><input name="coordinate" class="form-control" placeholder="-0.9200, 100.3600" @disabled(! $databaseReady)></div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Logger ID</label><input name="logger_id" class="form-control" @disabled(! $databaseReady)></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Connectivity</label><select name="connectivity_status" class="form-select" @disabled(! $databaseReady)><option>Online</option><option>Offline</option></select></div>
                            </div>
                            <input type="hidden" name="logger_status" value="Active">
                            <input type="hidden" name="registration_status" value="registered">
                            <input type="hidden" name="status" value="Normal">
                            <button type="submit" class="btn btn-primary" @disabled(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty())>Save / Update Monitoring</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Monitoring Station List</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Project</th><th>Workspace</th><th>Corridor</th><th>Type</th><th>Logger</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($monitoringStations as $station)
                                        <tr>
                                            <td>{{ $station['id'] }}</td><td>{{ $station['name'] }}</td><td>{{ $station['project_id'] ?? '-' }}</td><td>{{ $station['cluster_id'] }}</td><td>{{ $station['corridor_id'] ?? '-' }}</td><td>{{ $station['station_type'] ?? '-' }}</td><td>{{ $station['logger_id'] }}</td>
                                            <td><span class="badge {{ $station['status'] === 'Danger' ? 'bg-danger' : 'bg-success' }}">{{ $station['status'] }}</span></td>
                                            <td class="text-end">
                                                @isset($station['db_id'])
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#monitoring-form"
                                                            data-edit-fields="{{ base64_encode(json_encode([
                                                                'workspace_id' => $station['workspace_db_id'] ?? '',
                                                                'project_id' => $station['project_db_id'] ?? '',
                                                                'corridor_id' => $station['corridor_db_id'] ?? '',
                                                                'station_code' => $station['id'] ?? '',
                                                                'name' => $station['name'] ?? '',
                                                                'station_type' => $station['station_type'] ?? 'environmental_monitoring',
                                                                'coordinate' => $station['coordinate'] ?? '',
                                                                'logger_id' => $station['logger_id'] ?? '',
                                                                'connectivity_status' => $station['connectivity_status'] ?? 'Online',
                                                                'logger_status' => $station['logger_status'] ?? 'Active',
                                                                'registration_status' => $station['registration_status'] ?? 'registered',
                                                                'registered_at' => $station['registered_at'] ?? '',
                                                                'status' => $station['status'] ?? 'Normal',
                                                            ])) }}">Edit</button>
                                                        <form method="POST" action="{{ route('project-setup.destroy', ['type' => 'monitoring', 'id' => $station['db_id']]) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form>
                                                    </div>
                                                @endisset
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="warning-tab-pane" role="tabpanel" aria-labelledby="warning-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Warning Station Registry</h4>
                        <form method="POST" action="{{ route('project-warning-stations.store') }}" id="warning-form">
                            @csrf
                            <div class="mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" required @disabled(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty())>@foreach ($clusters->whereNotNull('db_id') as $cluster)<option value="{{ $cluster['db_id'] }}">{{ $cluster['id'] }} - {{ $cluster['name'] }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label">Source Monitoring</label><select name="monitoring_station_id" class="form-select" @disabled(! $databaseReady || $monitoringStations->whereNotNull('db_id')->isEmpty())><option value="">-</option>@foreach ($monitoringStations->whereNotNull('db_id') as $station)<option value="{{ $station['db_id'] }}">{{ $station['id'] }} - {{ $station['name'] }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label">Warning Station ID</label><input name="station_code" class="form-control" placeholder="WS-PDG-001" required @disabled(! $databaseReady)></div>
                            <div class="mb-3"><label class="form-label">Station Name</label><input name="name" class="form-control" required @disabled(! $databaseReady)></div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Zone ID</label><input name="zone_id" class="form-control" @disabled(! $databaseReady)></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Controller ID</label><input name="controller_id" class="form-control" @disabled(! $databaseReady)></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Coordinate</label><input name="coordinate" class="form-control" @disabled(! $databaseReady)></div>
                            <div class="mb-3"><label class="form-label">Controller Model</label><input name="controller_model" class="form-control" @disabled(! $databaseReady)></div>
                            <div class="mb-3"><label class="form-label">Vendor</label><input name="controller_vendor" class="form-control" @disabled(! $databaseReady)></div>
                            <div class="mb-3">
                                @foreach (['Siren', 'Audio System', 'LED Display', 'Beacon Lamp'] as $device)
                                    <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="output_devices[]" value="{{ $device }}" id="warningDevice{{ Str::slug($device) }}" @disabled(! $databaseReady)><label class="form-check-label" for="warningDevice{{ Str::slug($device) }}">{{ $device }}</label></div>
                                @endforeach
                            </div>
                            <input type="hidden" name="controller_status" value="Standby">
                            <input type="hidden" name="status" value="Normal">
                            <button type="submit" class="btn btn-primary" @disabled(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty())>Save / Update Warning</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Warning Station List</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Workspace</th><th>Source MS</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($warningStations as $station)
                                        <tr>
                                            <td>{{ $station['id'] }}</td><td>{{ $station['name'] }}</td><td>{{ $station['cluster_id'] }}</td><td>{{ $station['source_monitoring_station_id'] }}</td>
                                            <td><span class="badge {{ $station['status'] === 'Danger' ? 'bg-danger' : 'bg-success' }}">{{ $station['status'] }}</span></td>
                                            <td class="text-end">
                                                @isset($station['db_id'])
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#warning-form"
                                                            data-edit-fields="{{ base64_encode(json_encode([
                                                                'workspace_id' => $station['workspace_db_id'] ?? '',
                                                                'monitoring_station_id' => $station['monitoring_station_db_id'] ?? '',
                                                                'station_code' => $station['id'] ?? '',
                                                                'name' => $station['name'] ?? '',
                                                                'zone_id' => $station['zone_id'] ?? '',
                                                                'controller_id' => $station['controller_id'] ?? '',
                                                                'coordinate' => $station['coordinate'] ?? '',
                                                                'controller_model' => $station['controller_model'] ?? '',
                                                                'controller_vendor' => $station['controller_vendor'] ?? '',
                                                                'output_devices' => $station['output_devices'] ?? [],
                                                                'controller_status' => $station['controller_status'] ?? 'Standby',
                                                                'status' => $station['status'] ?? 'Normal',
                                                            ])) }}">Edit</button>
                                                        <form method="POST" action="{{ route('project-setup.destroy', ['type' => 'warning', 'id' => $station['db_id']]) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form>
                                                    </div>
                                                @endisset
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="data-tab-pane" role="tabpanel" aria-labelledby="data-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-12">
                <div class="accordion mb-3" id="sensor-data-actions">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="data-logger-configuration-heading">
                            <button class="accordion-button {{ $loggerSectionOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#data-logger-configuration-collapse" aria-expanded="{{ $loggerSectionOpen ? 'true' : 'false' }}" aria-controls="data-logger-configuration-collapse">
                                <span class="fw-semibold">Tambah / Edit Data Logger</span>
                                <span class="badge bg-primary-subtle text-primary ms-2">{{ $dataLoggers->count() }}</span>
                            </button>
                        </h2>
                        <div id="data-logger-configuration-collapse" class="accordion-collapse collapse {{ $loggerSectionOpen ? 'show' : '' }}" aria-labelledby="data-logger-configuration-heading">
                            <div class="accordion-body">
                        <form method="POST" action="{{ route('data-loggers.store') }}" id="project-data-logger-form">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Monitoring Station <span class="text-muted fw-normal">— opsional</span></label>
                                <select name="monitoring_station_id" class="form-select" @disabled(! $databaseReady)>
                                    <option value="">— Tanpa Monitoring Station —</option>
                                    @foreach ($monitoringStations->whereNotNull('db_id') as $station)
                                        <option value="{{ $station['db_id'] }}">{{ $station['id'] }} - {{ $station['name'] }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Pilih monitoring station agar logger terdeteksi di Start Monitoring. Bisa diisi nanti.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logger ID</label>
                                <input name="logger_code" class="form-control" placeholder="DL-PDG-001" required @disabled(! $databaseReady)>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Serial Number</label><input name="serial_number" class="form-control" @disabled(! $databaseReady)></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Model</label><input name="logger_model" class="form-control" @disabled(! $databaseReady)></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Vendor</label><input name="vendor" class="form-control" @disabled(! $databaseReady)></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Firmware</label><input name="firmware_version" class="form-control" @disabled(! $databaseReady)></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Device Label / QR</label><input name="device_label" class="form-control" @disabled(! $databaseReady)></div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="logger_status" class="form-select" required @disabled(! $databaseReady)>
                                        <option>Active</option>
                                        <option>Inactive</option>
                                        <option>Maintenance</option>
                                        <option>Fault</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Polling Interval (ms)</label>
                                    <input type="number" name="poll_interval_ms" class="form-control" value="2000" min="500" max="300000" step="500" @disabled(! $databaseReady)>
                                    <small class="text-muted">Interval baca sensor & refresh monitoring (500ms – 5 menit)</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" @disabled(! $databaseReady)>Save / Update Logger</button>
                        </form>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="mqtt-configuration-heading">
                            <button class="accordion-button {{ $mqttSectionOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#mqtt-configuration-collapse" aria-expanded="{{ $mqttSectionOpen ? 'true' : 'false' }}" aria-controls="mqtt-configuration-collapse">
                                <span class="fw-semibold">Tambah / Edit MQTT Configuration</span>
                                <span class="badge bg-info-subtle text-info ms-2">{{ $mqttConfigurations->count() }}</span>
                            </button>
                        </h2>
                        <div id="mqtt-configuration-collapse" class="accordion-collapse collapse {{ $mqttSectionOpen ? 'show' : '' }}" aria-labelledby="mqtt-configuration-heading">
                            <div class="accordion-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <p class="text-muted mb-0">Hubungkan broker ke project, lalu pilih konfigurasi consumer tersebut saat menambahkan sensor.</p>
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#mqtt-inline-guide" aria-expanded="false">
                                        <i class="bx bx-book-open me-1"></i> Panduan
                                    </button>
                                </div>
                                <div class="collapse mb-3" id="mqtt-inline-guide">
                                    <div class="border rounded p-3 bg-light" style="max-height:400px;overflow-y:auto;font-size:0.85rem">
                                        @include('modules.mqtt-configurations._guide-content')
                                    </div>
                                </div>
                                @include('modules.mqtt-configurations._form', [
                                    'mqttFormId' => 'project-mqtt-configuration-form',
                                    'mqttProjects' => $mqttProjects,
                                    'mqttCanonicalParameters' => $canonicalParameters ?? collect(),
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="sensor-configuration-heading">
                            <button class="accordion-button {{ $sensorSectionOpen ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#sensor-configuration-collapse" aria-expanded="{{ $sensorSectionOpen ? 'true' : 'false' }}" aria-controls="sensor-configuration-collapse">
                                <span class="fw-semibold">Tambah / Edit Sensor</span>
                                <span class="badge bg-danger-subtle text-danger ms-2">{{ $sensors->count() }}</span>
                            </button>
                        </h2>
                        <div id="sensor-configuration-collapse" class="accordion-collapse collapse {{ $sensorSectionOpen ? 'show' : '' }}" aria-labelledby="sensor-configuration-heading">
                            <div class="accordion-body">
                        <form method="POST" action="{{ route('project-sensors.store') }}" id="sensor-form">
                            @csrf
                            <div class="mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" required @disabled(! $databaseReady || $clusters->whereNotNull('db_id')->isEmpty())>@foreach ($clusters->whereNotNull('db_id') as $cluster)<option value="{{ $cluster['db_id'] }}" data-project-id="{{ $cluster['project_db_id'] ?? '' }}">{{ $cluster['id'] }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label">Monitoring Station</label><select name="monitoring_station_id" class="form-select" required @disabled(! $databaseReady || $monitoringStations->whereNotNull('db_id')->isEmpty())>@foreach ($monitoringStations->whereNotNull('db_id') as $station)<option value="{{ $station['db_id'] }}">{{ $station['id'] }} - {{ $station['name'] }}</option>@endforeach</select></div>
	                            <div class="row">
	                                <div class="col-md-4 mb-3"><label class="form-label">Input Source</label><select name="input_source" class="form-select" id="sensor-input-source"><option value="data_logger">Data Logger</option><option value="mqtt">MQTT Configuration</option></select></div>
	                                <div class="col-md-4 mb-3" id="sensor-data-logger-wrap"><label class="form-label">Data Logger</label><select name="data_logger_id" class="form-select"><option value="">-</option>@foreach($dataLoggers->whereNotNull('db_id') as $logger)<option value="{{ $logger['db_id'] }}">{{ $logger['id'] }}</option>@endforeach</select></div>
	                                <div class="col-md-4 mb-3 d-none" id="sensor-mqtt-wrap"><label class="form-label">MQTT Configuration</label><select name="mqtt_configuration_id" class="form-select"><option value="">-</option>@foreach($mqttConfigurations->where('consumer_enabled', true) as $config)<option value="{{ $config->id }}" data-project-id="{{ $config->project_id }}">{{ $config->configuration_code }} - {{ $config->name }}</option>@endforeach</select><small class="text-muted"><a href="#mqtt-configuration-collapse" data-bs-toggle="collapse" aria-controls="mqtt-configuration-collapse">Tambah / kelola MQTT di tab ini</a></small></div>
	                            </div>
	                            <div class="mb-3"><label class="form-label">Warning Station</label><select name="warning_station_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($warningStations->whereNotNull('db_id') as $station)<option value="{{ $station['db_id'] }}">{{ $station['id'] }}</option>@endforeach</select></div>
	                            <div class="mb-3"><label class="form-label">Sensor ID</label><input name="sensor_code" class="form-control" placeholder="PS-PDG-01" required @disabled(! $databaseReady)></div>
	                            <div class="row">
	                                <div class="col-md-4 mb-3">
		                                    <label class="form-label">Prefix Sensors</label>
		                                    <select name="mst_prefix_id" class="form-select" required @disabled(! $databaseReady || $mstPrefixes->whereNotNull('id')->isEmpty())>
	                                        <option value="">-</option>
	                                        @foreach ($mstPrefixes as $prefix)
	                                            @php
	                                                $prefixDbId = data_get($prefix, 'db_id', data_get($prefix, 'id'));
	                                                $prefixCode = data_get($prefix, 'prefix_code', data_get($prefix, 'id'));
	                                                $prefixName = data_get($prefix, 'name');
	                                            @endphp
	                                            <option value="{{ $prefixDbId }}">{{ $prefixCode }}{{ $prefixName ? ' - ' . $prefixName : '' }}</option>
	                                        @endforeach
	                                    </select>
	                                </div>
		                                <div class="col-md-4 mb-3"><label class="form-label">Slave ID</label><input name="slave_id" class="form-control" placeholder="1" required @disabled(! $databaseReady)></div>
		                                <div class="col-md-4 mb-3"><label class="form-label">Start Address</label><input name="address" class="form-control" placeholder="0" required @disabled(! $databaseReady)></div>
	                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Function Code</label>
                                    <select name="function_code" class="form-select" required @disabled(! $databaseReady)>
                                        <option value="FC03">FC03 - Holding Register</option>
                                        <option value="FC04">FC04 - Input Register</option>
                                        <option value="FC01">FC01 - Coils</option>
                                        <option value="FC02">FC02 - Discrete Inputs</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="quantity" class="form-control" value="1" min="1" max="125" required @disabled(! $databaseReady)>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Poll Interval (ms)</label>
                                    <input type="number" name="poll_interval_ms" class="form-control" value="1000" min="250" max="60000" required @disabled(! $databaseReady)>
                                </div>
                            </div>
	                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Sensor Type</label>
                                    <select name="type" class="form-select" required @disabled(! $databaseReady)>
                                        @foreach ($sensorTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Data Logger Type</label>
                                    <select name="data_type" class="form-select" required @disabled(! $databaseReady)>
                                        @foreach ($dataLoggerTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Parameter</label><input name="parameter" class="form-control" @disabled(! $databaseReady)></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Unit</label><input name="unit" class="form-control" @disabled(! $databaseReady)></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Sentinel Parameter Mapping</label>
                                    <select name="canonical_parameter_id" class="form-select" @disabled(! $databaseReady || collect($canonicalParameters ?? [])->isEmpty())>
                                        <option value="">Map later in Canonical Database</option>
                                        @foreach ($canonicalParameters ?? [] as $parameter)
                                            <option value="{{ $parameter->id }}">{{ $parameter->field_identity }} - {{ $parameter->domain }}{{ $parameter->canonical_unit ? ' / ' . $parameter->canonical_unit : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Source Parameter / JSON Path</label>
                                    <input name="source_parameter" class="form-control" placeholder="register name atau data.temperature" @disabled(! $databaseReady)>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3"><label class="form-label">Source Unit</label><input name="source_unit" class="form-control" @disabled(! $databaseReady)></div>
                                <div class="col-md-4 mb-3"><label class="form-label">Byte Order</label><input name="byte_order" class="form-control" placeholder="ABCD / CDAB" @disabled(! $databaseReady)></div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Value Origin</label>
                                    <select name="value_origin" class="form-select" @disabled(! $databaseReady)>
                                        <option value="direct_measurement">Direct Measurement</option>
                                        <option value="device_processed">Device Processed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 d-none" id="weather-parameters-wrap">
                                <label class="form-label">Parameter Sensor Cuaca</label>
                                <div class="row g-2">
                                    @foreach ($weatherParameters as $value => $label)
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="weather_parameters[]" value="{{ $value }}" id="weatherParam{{ Str::studly($value) }}" @disabled(! $databaseReady)>
                                                <label class="form-check-label" for="weatherParam{{ Str::studly($value) }}">{{ $label }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Scale Factor</label><input type="number" step="0.0001" name="scale_factor" class="form-control" value="1" required @disabled(! $databaseReady)></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Offset</label><input type="number" step="0.0001" name="offset" class="form-control" value="0" required @disabled(! $databaseReady)></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Threshold / Rule</label>
                                    <input name="threshold" class="form-control" @disabled(! $databaseReady)>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Alert Level</label>
                                    <select name="alert_level" class="form-select" required @disabled(! $databaseReady)>
                                        <option>Waspada</option>
                                        <option>Siaga</option>
                                        <option>Awas</option>
                                        <option>Normal</option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="reading_method" value="Absolute">
                            <input type="hidden" name="status" value="Normal">
	                            <button type="submit" class="btn btn-primary" @disabled(! $databaseReady || $monitoringStations->whereNotNull('db_id')->isEmpty() || $mstPrefixes->whereNotNull('id')->isEmpty())>Save / Update Sensor</button>
                        </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Sensor & Data Registry</h4>
                        @include('modules.mqtt-configurations._monitor', [
                            'mqttConfigurations' => $mqttConfigurations,
                            'mqttEditForm' => '#project-mqtt-configuration-form',
                        ])
                        <h5 class="font-size-14 mb-3">Data Logger Registry</h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light"><tr><th>Logger ID</th><th>Monitoring</th><th>Serial</th><th>Model</th><th>Poll</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    @forelse ($dataLoggers as $logger)
                                        <tr>
                                            <td>{{ $logger['id'] ?? '-' }}</td>
                                            <td>{{ $logger['monitoring_station_id'] ?? '-' }}</td>
                                            <td>{{ $logger['serial_number'] ?? '-' }}</td>
                                            <td>{{ $logger['logger_model'] ?? '-' }}</td>
                                            <td><span class="text-muted">{{ number_format(($logger['poll_interval_ms'] ?? 2000) / 1000, 1) }}s</span></td>
                                            <td><span class="badge {{ ($logger['logger_status'] ?? '') === 'Active' ? 'bg-success' : 'bg-secondary' }}">{{ $logger['logger_status'] ?? '-' }}</span></td>
                                            <td class="text-end">
                                                @isset($logger['db_id'])
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#project-data-logger-form"
                                                            data-edit-fields="{{ base64_encode(json_encode([
                                                                'monitoring_station_id' => $logger['monitoring_station_db_id'] ?? '',
                                                                'logger_code' => $logger['id'] ?? '',
                                                                'serial_number' => $logger['serial_number'] ?? '',
                                                                'logger_model' => $logger['logger_model'] ?? '',
                                                                'vendor' => $logger['vendor'] ?? '',
                                                                'firmware_version' => $logger['firmware_version'] ?? '',
                                                                'device_label' => $logger['device_label'] ?? '',
                                                                'logger_status' => $logger['logger_status'] ?? 'Active',
                                                                'poll_interval_ms' => $logger['poll_interval_ms'] ?? 2000,
                                                            ])) }}">Edit</button>
                                                        <form method="POST" action="{{ route('device-setup.destroy', ['type' => 'data-logger', 'id' => $logger['db_id']]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                        </form>
                                                    </div>
                                                @endisset
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada data logger.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
	                                <thead class="table-light"><tr><th>ID</th><th>Type</th><th>Data Type</th><th>Prefix</th><th>Slave</th><th>Start Address</th><th>FC</th><th>Qty</th><th>Poll</th><th>Monitoring</th><th>Warning</th><th>Canonical</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($sensors as $sensor)
                                        @php
                                            $sensorWeatherParameters = collect($sensor['weather_parameters'] ?? [])
                                                ->map(fn ($parameter) => $weatherParameters[$parameter] ?? Str::headline($parameter))
                                                ->filter()
                                                ->implode(', ');
                                        @endphp
                                        <tr>
	                                            <td>{{ $sensor['id'] }}</td><td><div>{{ $sensorTypes[$sensor['type'] ?? ''] ?? ($sensor['type'] ?? '-') }}</div>@if($sensorWeatherParameters)<small class="text-muted">{{ $sensorWeatherParameters }}</small>@elseif(! empty($sensor['parameter']))<small class="text-muted">{{ $sensor['parameter'] }}</small>@endif</td><td>{{ $dataLoggerTypes[$sensor['data_type'] ?? ''] ?? ($sensor['data_type'] ?? '-') }}</td><td>{{ $sensor['mst_prefix'] ?? '-' }}</td><td>{{ $sensor['slave_id'] ?? '-' }}</td><td>{{ $sensor['address'] ?? '-' }}</td><td>{{ $sensor['function_code'] ?? 'FC03' }}</td><td>{{ $sensor['quantity'] ?? 1 }}</td><td>{{ $sensor['poll_interval_ms'] ?? 1000 }} ms</td><td>{{ $sensor['monitoring_station_id'] }}</td><td>{{ $sensor['warning_station_id'] }}</td>
                                            <td>
                                                @if($sensor['is_canonical_mapped'] ?? false)
                                                    <span class="badge bg-success-subtle text-success">Sudah Mapping</span>
                                                @else
                                                    <div class="d-flex flex-column align-items-start gap-2">
                                                        <span class="badge bg-danger-subtle text-danger">Belum Mapping Canonical</span>
                                                        <a href="{{ route('canonical-database.index') }}#mapping" class="btn btn-outline-danger btn-sm">
                                                            Mapping
                                                        </a>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @isset($sensor['db_id'])
                                                    <div class="d-inline-flex gap-1">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                            data-edit-form="#sensor-form"
                                                            data-edit-fields="{{ base64_encode(json_encode([
                                                                'workspace_id' => $sensor['workspace_db_id'] ?? '',
                                                                'monitoring_station_id' => $sensor['monitoring_station_db_id'] ?? '',
                                                                'input_source' => $sensor['input_source'] ?? 'data_logger',
                                                                'data_logger_id' => $sensor['data_logger_db_id'] ?? '',
                                                                'mqtt_configuration_id' => $sensor['mqtt_configuration_db_id'] ?? '',
                                                                'warning_station_id' => $sensor['warning_station_db_id'] ?? '',
                                                                'mst_prefix_id' => $sensor['mst_prefix_db_id'] ?? '',
                                                                'sensor_code' => $sensor['id'] ?? '',
                                                                'slave_id' => $sensor['slave_id'] ?? '',
                                                                'address' => $sensor['address'] ?? '',
                                                                'function_code' => $sensor['function_code'] ?? 'FC03',
                                                                'quantity' => $sensor['quantity'] ?? 1,
                                                                'poll_interval_ms' => $sensor['poll_interval_ms'] ?? 1000,
                                                                'type' => $sensor['type'] ?? 'soil_moisture',
                                                                'data_type' => $sensor['data_type'] ?? 'uint16',
                                                                'parameter' => $sensor['parameter'] ?? '',
                                                                'weather_parameters' => $sensor['weather_parameters'] ?? [],
                                                                'canonical_parameter_id' => $sensor['canonical_parameter_db_id'] ?? '',
                                                                'source_parameter' => $sensor['source_parameter'] ?? '',
                                                                'source_unit' => $sensor['source_unit'] ?? '',
                                                                'byte_order' => $sensor['byte_order'] ?? '',
                                                                'value_origin' => $sensor['value_origin'] ?? 'direct_measurement',
                                                                'unit' => $sensor['unit'] ?? '',
                                                                'scale_factor' => $sensor['scale_factor'] ?? 1,
                                                                'offset' => $sensor['offset'] ?? 0,
                                                                'threshold' => $sensor['threshold'] ?? '',
                                                                'alert_level' => $sensor['alert_level'] ?? 'Normal',
                                                                'reading_method' => $sensor['reading_method'] ?? 'Absolute',
                                                                'status' => $sensor['status'] ?? 'Normal',
                                                            ])) }}">Edit</button>
                                                        <form method="POST" action="{{ route('project-setup.destroy', ['type' => 'sensor', 'id' => $sensor['db_id']]) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form>
                                                    </div>
                                                @endisset
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="tab-pane fade" id="canonical-tab-pane" role="tabpanel" aria-labelledby="canonical-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                            <div>
                                <h4 class="card-title mb-1">Canonical Data Mapping</h4>
                                <p class="text-muted mb-0">
                                    Konfigurasi mapping di halaman ini ditarik dari Canonical Database. Project Setup hanya menampilkan status pemetaan per sensor.
                                </p>
                            </div>
                            <a href="{{ route('canonical-database.index') }}#mapping" class="btn btn-primary">
                                <i class="bx bx-cog me-1"></i> Open Canonical Mapping
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Sensor Mapping Coverage</h4>
                        @php
                            $profilesBySensor = collect($sensorMappingProfiles ?? [])->groupBy('sensor_id');
                        @endphp
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sensor</th>
                                        <th>Type</th>
                                        <th>Monitoring</th>
                                        <th>Mapped Parameters</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sensors->whereNotNull('db_id') as $sensor)
                                        @php
                                            $sensorProfiles = $profilesBySensor->get($sensor['db_id'], collect());
                                            $activeCount = $sensorProfiles->where('status', 'active')->count();
                                            $parameterNames = $sensorProfiles
                                                ->map(fn ($profile) => $profile->canonicalParameter?->field_identity)
                                                ->filter()
                                                ->values();
                                        @endphp
                                        <tr>
                                            <td>{{ $sensor['id'] }}</td>
                                            <td>{{ $sensorTypes[$sensor['type'] ?? ''] ?? ($sensor['type'] ?? '-') }}</td>
                                            <td>{{ $sensor['monitoring_station_id'] ?? '-' }}</td>
                                            <td>
                                                @if($parameterNames->isNotEmpty())
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($parameterNames as $name)
                                                            <span class="badge bg-light text-dark">{{ $name }}</span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted">Belum ada mapping</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($activeCount > 0)
                                                    <span class="badge bg-success">{{ $activeCount }} active mapping</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Belum Mapping Canonical</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('canonical-database.index') }}#mapping" class="btn btn-outline-primary btn-sm">
                                                    Configure
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Belum ada sensor.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Mapping Profiles from Canonical Database</h4>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Profile Code</th>
                                        <th>Sensor</th>
                                        <th>Source</th>
                                        <th>Register</th>
                                        <th>Canonical Target</th>
                                        <th>Origin</th>
                                        <th>Scale/Offset</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sensorMappingProfiles ?? [] as $profile)
                                    <tr>
                                        <td>{{ $profile->profile_code }}</td>
                                        <td>{{ $profile->sensor->sensor_code ?? '-' }}</td>
                                        <td>
                                            {{ $profile->source_parameter }}
                                            @if($profile->source_unit)
                                                <small class="text-muted d-block">{{ $profile->source_unit }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $profile->register_address ?? '-' }}
                                            @if($profile->value_type || $profile->data_length)
                                                <small class="text-muted d-block">{{ $profile->value_type ?? '-' }} / len {{ $profile->data_length ?? '-' }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $profile->canonicalParameter->field_identity ?? '-' }}</td>
                                        <td>{{ str_replace('_', ' ', Str::title($profile->value_origin)) }}</td>
                                        <td>{{ $profile->scale_factor ?? 1 }} / {{ $profile->offset ?? 0 }}</td>
                                        <td>
                                            @if($profile->status === 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('canonical-database.index') }}#mapping" class="btn btn-outline-primary btn-sm">Edit in Canonical DB</a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">Belum ada mapping Canonical Data.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canMutateAssetRegistry)
    <div class="tab-pane fade" id="operation-tab-pane" role="tabpanel" aria-labelledby="operation-tab" tabindex="0">
        <div class="row">
            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Response Plan / Act</h4>
                        <form method="POST" action="{{ route('project-response-plans.store') }}">
                            @csrf
                            <div class="mb-3"><label class="form-label">Workspace</label><select name="workspace_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($clusters->whereNotNull('db_id') as $cluster)<option value="{{ $cluster['db_id'] }}">{{ $cluster['id'] }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label">Sensor</label><select name="sensor_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($sensors->whereNotNull('db_id') as $sensor)<option value="{{ $sensor['db_id'] }}">{{ $sensor['id'] }}</option>@endforeach</select></div>
                            <div class="mb-3"><label class="form-label">Warning Station</label><select name="warning_station_id" class="form-select" @disabled(! $databaseReady)><option value="">-</option>@foreach ($warningStations->whereNotNull('db_id') as $station)<option value="{{ $station['db_id'] }}">{{ $station['id'] }}</option>@endforeach</select></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="dashboard_notif" value="1" checked @disabled(! $databaseReady)><label class="form-check-label">Dashboard Notif</label></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="sms_blasting" value="1" @disabled(! $databaseReady)><label class="form-check-label">SMS Blasting</label></div>
                            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="warning_station_act" value="1" @disabled(! $databaseReady)><label class="form-check-label">Warning Station</label></div>
                            <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3" @disabled(! $databaseReady)></textarea></div>
                            <button type="submit" class="btn btn-danger" @disabled(! $databaseReady)>Save Response Plan</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Operational Rules</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3"><label class="form-label">Alert Level</label><select class="form-select"><option>Normal</option><option>Waspada</option><option>Siaga</option><option>Awas</option></select></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Reading Method</label><select class="form-select"><option>Absolute</option><option>Accumulative</option><option>Moving Average</option><option>Probability</option></select></div>
                            <div class="col-md-4 mb-3"><label class="form-label">Rule</label><input class="form-control" value="{{ $selectedSensor['threshold'] ?? '' }}"></div>
                        </div>
                        <p class="text-muted mb-0">Rule detail tersimpan bersama data sensor dan response plan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="tab-pane fade" id="user-setup-tab-pane" role="tabpanel" aria-labelledby="user-setup-tab" tabindex="0">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">User Setup</h4>
                <p class="text-muted mb-0">Account setup masih memakai modul Admin yang sudah ada. Data project scope dapat diarahkan ke project/workspace yang dibuat di tab sebelumnya.</p>
                <a href="{{ route('admins.index') }}" class="btn btn-primary mt-3">Open Account Registry</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/leaflet/leaflet.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/sentinel-spatial-map.js') }}"></script>
<script>
    window.SentinelProjectSpatialResources = @json($spatialResources);

    document.addEventListener('DOMContentLoaded', function () {
        if (window.SentinelSpatialMap) {
            window.SentinelSpatialMap.createConfigurationMap('project-spatial-config-map', window.SentinelProjectSpatialResources || {});
        }
    });

    (function () {
        const source = document.getElementById('sensor-input-source');
        const workspace = document.querySelector('#sensor-form [name="workspace_id"]');
        const loggerWrap = document.getElementById('sensor-data-logger-wrap');
        const mqttWrap = document.getElementById('sensor-mqtt-wrap');
        const syncSource = () => {
            const mqtt = source?.value === 'mqtt';
            loggerWrap?.classList.toggle('d-none', mqtt);
            mqttWrap?.classList.toggle('d-none', !mqtt);
            const logger = loggerWrap?.querySelector('select');
            const config = mqttWrap?.querySelector('select');
            if (logger) logger.required = !mqtt;
            if (config) config.required = mqtt;
        };
        const filterMqttByProject = () => {
            const projectId = workspace?.selectedOptions[0]?.dataset.projectId || '';
            const config = mqttWrap?.querySelector('select');
            Array.from(config?.options || []).forEach(option => {
                if (!option.value) return;
                option.hidden = Boolean(projectId) && option.dataset.projectId !== projectId;
                option.disabled = option.hidden;
            });
            if (config?.selectedOptions[0]?.disabled) config.value = '';
        };
        source?.addEventListener('change', syncSource);
        workspace?.addEventListener('change', filterMqttByProject);
        syncSource();
        filterMqttByProject();

        const sensorType = document.querySelector('#sensor-form [name="type"]');
        const quantity = document.querySelector('#sensor-form [name="quantity"]');
        const parameter = document.querySelector('#sensor-form [name="parameter"]');
        const weatherWrap = document.getElementById('weather-parameters-wrap');

        if (!sensorType || !weatherWrap) {
            return;
        }

        function defaultWeatherParameterValues() {
            const base = ['temperature', 'humidity', 'pressure', 'wind_speed', 'wind_direction', 'rainfall', 'solar_radiation', 'battery_voltage'];
            const hint = String(parameter?.value || '').toLowerCase();

            if (hint.includes('angin') || hint.includes('wind')) {
                return ['wind_speed', 'wind_direction'].concat(base.filter((item) => !['wind_speed', 'wind_direction'].includes(item)));
            }

            if (hint.includes('hujan') || hint.includes('rain')) {
                return ['rainfall'].concat(base.filter((item) => item !== 'rainfall'));
            }

            return base;
        }

        function applyDefaultWeatherChecks() {
            const checks = Array.from(weatherWrap.querySelectorAll('input[type="checkbox"]'));
            const selected = checks.filter((check) => check.checked);

            if (sensorType.value !== 'weather_station' || selected.length) {
                return;
            }

            const limit = Math.max(parseInt(quantity?.value || '1', 10) || 1, 1);
            const defaults = defaultWeatherParameterValues().slice(0, limit);
            checks.forEach((check) => {
                check.checked = defaults.includes(check.value);
            });
        }

        function syncWeatherParameters() {
            weatherWrap.classList.toggle('d-none', sensorType.value !== 'weather_station');
            applyDefaultWeatherChecks();
        }

        sensorType.addEventListener('change', syncWeatherParameters);
        quantity?.addEventListener('change', applyDefaultWeatherChecks);
        parameter?.addEventListener('input', applyDefaultWeatherChecks);
        syncWeatherParameters();
    })();

</script>
@include('modules.mqtt-configurations._scripts')
@endsection
