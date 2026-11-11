<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $project->displayLabel() }} | Peta Project</title>
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
        html, body { width: 100%; height: 100%; margin: 0; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        #sensor-cluster-map { height: 100vh; height: 100dvh; min-height: 0; border-radius: 0; }
        .project-map-header {
            position: absolute;
            z-index: 1000;
            top: 14px;
            left: 16px;
            right: calc(clamp(380px, 29vw, 440px) + 24px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 7px 14px;
            background: rgba(28, 40, 36, 0.82);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            color: #ffffff;
            transition: right 0.3s ease;
        }
        .project-map-header.sidebar-collapsed {
            right: 76px;
        }
        .dash-header-left {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            min-width: 0;
        }
        .dash-back-btn, .dash-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: rgba(18, 28, 24, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .dash-back-btn:hover, .dash-action-btn:hover {
            background: rgba(36, 52, 46, 0.95);
            color: #ffffff;
        }
        .dash-select-container {
            position: relative;
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
        }
        .dash-select {
            appearance: none;
            background: rgba(18, 28, 24, 0.85);
            -webkit-backdrop-filter: blur(6px);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 600;
            padding: 5px 24px 5px 9px;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .dash-select:hover, .dash-select:focus {
            background: rgba(28, 42, 36, 0.95);
            border-color: #34d399;
        }
        .dash-select option {
            background: #141f19;
            color: #ffffff;
        }
        .dash-select-container i {
            position: absolute;
            right: 8px;
            pointer-events: none;
            font-size: 14px;
            color: #95a5a0;
        }
        .dash-weather-timeline {
            display: flex;
            align-items: center;
            gap: 4px;
            background: rgba(18, 28, 24, 0.85);
            -webkit-backdrop-filter: blur(6px);
            backdrop-filter: blur(6px);
            padding: 3px 6px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            flex-shrink: 0;
            margin: 0 auto;
        }
        .dash-timeline-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: #9ca3af;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .dash-timeline-item:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
        }
        .dash-timeline-item.active {
            background: rgba(16, 185, 129, 0.25);
            color: #34d399;
            font-weight: 600;
        }
        .dash-timeline-item i {
            font-size: 13px;
        }
        .dash-header-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .dash-hydrology-container {
            position: absolute;
            z-index: 900;
            bottom: 16px;
            left: 16px;
            width: 530px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            max-height: 240px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.25) transparent;
        }
        .hydro-card {
            background: rgba(28, 40, 36, 0.82);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 6px;
            padding: 10px 12px;
            color: #ffffff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
        }
        .hydro-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
            padding-bottom: 5px;
            border-bottom: 1px solid #374b47;
        }
        .hydro-station-name {
            font-size: 11.5px;
            font-weight: 700;
            color: #ffffff;
        }
        .hydro-badge-live {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            font-size: 9.5px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }
        .hydro-card-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .hydro-metric-col {
            display: flex;
            flex-direction: column;
        }
        .hydro-metric-label {
            font-size: 9.5px;
            font-weight: 600;
            color: #95a5a0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .hydro-sub-label {
            font-size: 9.5px;
            color: #7b8e88;
            margin-top: 1px;
        }
        .hydro-status-dot {
            font-size: 10.5px;
            font-weight: 500;
            color: #34d399;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .hydro-metric-val {
            display: flex;
            align-items: baseline;
            gap: 3px;
        }
        .hydro-metric-val .val-num {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.1;
        }
        .hydro-metric-val .val-unit {
            font-size: 11px;
            color: #95a5a0;
            font-weight: 500;
        }
        .hydro-meta-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 10px;
            color: #95a5a0;
            margin-top: 4px;
        }
        .dash-legend-card {
            position: absolute;
            z-index: 900;
            bottom: 24px;
            right: calc(clamp(380px, 29vw, 440px) + 24px);
            width: 220px;
            background: rgba(28, 40, 36, 0.82);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 6px;
            padding: 10px 14px;
            color: #ffffff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
            transition: right 0.3s ease;
        }
        .dash-legend-card.sidebar-collapsed {
            right: 20px;
        }
        .legend-card-header {
            font-size: 12.5px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #374b47;
        }
        .legend-card-body {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .legend-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: #e2e8f0;
            cursor: pointer;
            margin: 0;
            user-select: none;
        }
        .legend-checkbox-item input[type="checkbox"] {
            width: 13px;
            height: 13px;
            accent-color: #10b981;
            cursor: pointer;
            margin: 0;
        }
        .legend-symbol {
            display: inline-block;
            width: 18px;
            height: 3px;
            border-radius: 2px;
        }
        .sym-corridor { background: #0D47A1; height: 3px; }
        .sym-river { background: #38bdf8; height: 2px; }
        .sym-boundary { background: #94a3b8; height: 2px; border-top: 2px dashed #cbd5e1; }
        .sym-cfpe { background: #ef4444; height: 2px; border-top: 2px dashed #f87171; }
        .legend-symbol-icon { font-size: 12px; width: 18px; text-align: center; }
        .legend-symbol-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            width: 16px;
            height: 14px;
            color: #ffffff;
        }
        .map-station-permanent-label {
            background: rgba(22, 33, 28, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
            padding: 1px 5px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            white-space: nowrap;
        }
        .hide-station-labels .map-station-permanent-label {
            display: none !important;
        }
        .map-badge-marker-wrap {
            pointer-events: auto;
        }
        .map-badge-marker {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            pointer-events: auto;
            cursor: pointer;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.4));
        }
        .map-marker-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background-color: #10b981;
            border: 1.5px solid #ffffff;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.5);
            flex-shrink: 0;
        }
        .map-marker-badge {
            background: #000000;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            letter-spacing: 0.2px;
            line-height: 1.2;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .map-marker-badge small {
            font-size: 9.5px;
            font-weight: 500;
            color: #cbd5e1;
            margin-left: 2px;
        }
        .leaflet-top.leaflet-left {
            top: 74px !important;
            left: 16px !important;
        }
        .leaflet-control-zoom {
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 6px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25) !important;
        }
        .leaflet-control-zoom a {
            background-color: rgba(14, 22, 18, 0.65) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            backdrop-filter: blur(10px) !important;
            color: #ffffff !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        .leaflet-control-zoom a:hover {
            background-color: rgba(24, 36, 30, 0.85) !important;
            color: #ffffff !important;
        }
        .warning-station-marker:focus-visible { outline: 3px solid #1849a9; outline-offset: 5px; border-radius: 50%; }
        @media (max-width: 991px) {
            .dash-legend-card { display: none; }
            .project-map-header {
                right: 16px;
                top: 10px;
                flex-wrap: wrap;
                gap: 8px;
            }
            .dash-weather-timeline { display: none; }
            .dash-hydrology-container {
                width: calc(100vw - 32px);
                max-width: 530px;
            }
        }
        @media (max-width: 575px) {
            .dash-hydrology-container { display: none; }
        }
    </style>
</head>
<body>
    <div id="sensor-cluster-map" role="region" aria-label="Peta {{ $project->displayLabel() }}"></div>

@php
    $realCorridors = \App\Models\CorridorMonitoring::where('project_id', $project->id)->get();
    if ($realCorridors->isEmpty()) {
        $realCorridors = $project->workspaces()->get();
    }
    $realClusters = $project->workspaces()->get();

    $corridorsJsonData = $realCorridors->map(function ($c) {
        $coords = is_array($c->path_coordinates) ? $c->path_coordinates : (json_decode($c->path_coordinates, true) ?: []);
        return [
            'id' => $c->id,
            'name' => $c->name,
            'code' => $c->corridor_code,
            'status' => $c->status,
            'coordinates' => $coords,
        ];
    })->values()->all();

    $realProjectSensors = \App\Models\Sensor::with(['monitoringStation', 'dataLogger'])
        ->where(function ($q) use ($project) {
            $q->whereHas('monitoringStation', fn ($ms) => $ms->where('project_id', $project->id))
              ->orWhereHas('workspace', fn ($ws) => $ws->where('project_id', $project->id));
        })
        ->orderBy('monitoring_station_id')
        ->orderBy('sensor_code')
        ->get();

    if ($realProjectSensors->isEmpty() && !empty($mapSensors)) {
        $realProjectSensors = collect($mapSensors)->map(function ($s) {
            return (object) [
                'sensor_code' => $s['name'] ?? 'SENSOR',
                'parameter' => $s['parameter'] ?? 'Parameter',
                'type' => $s['type'] ?? 'sensor',
                'value' => $s['value'] ?? '-',
                'unit' => $s['unit'] ?? '',
                'status' => $s['status'] ?? 'Normal',
                'last_seen_at' => null,
                'monitoringStation' => (object) ['station_code' => $s['station'] ?? 'MS-DEMO'],
                'dataLogger' => null,
            ];
        });
    }

    $timelineDates = collect([0, 1, 2, 3, 4])->map(function ($dayOffset) {
        $dt = now()->addDays($dayOffset);
        $icons = ['bx-sun text-warning', 'bx-sun text-warning', 'bx-cloud text-light', 'bx-cloud-rain text-info', 'bx-cloud text-light'];
        return [
            'date_label' => $dt->format('d M'),
            'is_today' => $dayOffset === 0,
            'icon' => $icons[$dayOffset % 5],
        ];
    });
@endphp

    <header class="project-map-header">
        <div class="dash-header-left">
            <a href="{{ route('dashboard') }}" class="dash-back-btn" aria-label="Kembali ke daftar project" title="Kembali ke daftar project">
                <i class="bx bx-arrow-back" aria-hidden="true"></i>
            </a>
            <div class="dash-select-container">
                <select class="dash-select" id="dash-corridor-select" aria-label="Pilih Koridor">
                    <option value="all">Semua Koridor</option>
                    @foreach($realCorridors as $corridor)
                        <option value="{{ $corridor->name }}">{{ $corridor->name }}</option>
                    @endforeach
                </select>
                <i class="bx bx-chevron-down" aria-hidden="true"></i>
            </div>
            <div class="dash-select-container">
                <select class="dash-select" id="dash-cluster-select" aria-label="Pilih Cluster">
                    <option value="all">Semua Cluster</option>
                    @foreach($realClusters as $cluster)
                        <option value="{{ $cluster->name }}">{{ $cluster->name }}</option>
                    @endforeach
                </select>
                <i class="bx bx-chevron-down" aria-hidden="true"></i>
            </div>
        </div>

        <div class="dash-weather-timeline" id="dash-weather-timeline">
            @foreach($timelineDates as $td)
            <div class="dash-timeline-item {{ $td['is_today'] ? 'active' : '' }}" data-date="{{ $td['date_label'] }}">
                <span class="t-date">{{ $td['date_label'] }}</span>
                <i class="bx {{ $td['icon'] }}" aria-hidden="true"></i>
            </div>
            @endforeach
            <div class="dash-current-clock" id="dash-live-clock" style="padding: 2px 8px; border-left: 1px solid rgba(255,255,255,0.15); font-size: 11px; font-weight: 700; color: #34d399; display: flex; align-items: center; gap: 4px;">
                <i class="bx bx-time-five" aria-hidden="true"></i>
                <span id="live-time-str">{{ now()->format('H:i') }}</span>
            </div>
        </div>

        <div class="dash-header-actions">
            <button type="button" id="map-fit-filtered" class="dash-action-btn" aria-label="Kembali ke area Semeru" title="Kembali ke area Semeru">
                <i class="bx bx-target-lock" aria-hidden="true"></i>
            </button>
            <button type="button" class="dash-action-btn" id="btn-ews-config" aria-label="Pengaturan EWS" title="Hydromet EWS Configuration">
                <i class="bx bx-cog" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <div class="dash-hydrology-container" id="dash-hydrology-panel" aria-label="Pemantauan Sensor Realtime">
        @forelse($realProjectSensors as $sensor)
            @php
                $stCode = $sensor->monitoringStation?->station_code ?: ($sensor->station ?? 'MS');
                $valStr = $sensor->value ?? '-';
                $unitStr = $sensor->unit ?? '';
                $paramName = $sensor->parameter ?: ucwords(str_replace('_', ' ', $sensor->type ?: 'Sensor'));
                $loggerCode = $sensor->dataLogger?->logger_code ?: ($sensor->dataLogger?->serial_number ?: ($sensor->data_logger ?? ''));
                $sensorStatus = $sensor->status ?: 'Normal';
                $isAwas = in_array(strtolower($sensorStatus), ['awas', 'danger', 'bahaya', 'critical']);
                $isWaspada = in_array(strtolower($sensorStatus), ['waspada', 'warning', 'siaga']);
                $statusBadgeClass = $isAwas ? 'status-awas' : ($isWaspada ? 'status-waspada' : 'status-normal');
            @endphp
            <div class="hydro-card" data-station="{{ $stCode }}" data-sensor="{{ $sensor->sensor_code }}">
                <div class="hydro-card-header">
                    <span class="hydro-station-name">{{ $stCode }}_{{ strtoupper(str_replace('-', '_', $sensor->sensor_code)) }}</span>
                    <span class="hydro-badge-live">Live</span>
                </div>
                <div class="hydro-card-body">
                    <div class="hydro-metric-col">
                        <span class="hydro-metric-label">{{ $paramName }}</span>
                        <div class="hydro-sub-label">{{ $loggerCode ?: 'REDNODE-BLIIOT' }}</div>
                        <div class="hydro-status-dot" style="margin-top: 6px;">
                            <span style="display:inline-block; width:6px; height:6px; border-radius:50%; background:{{ $isAwas ? '#ef4444' : '#10b981' }};"></span>
                            <span>{{ $sensorStatus }}</span>
                        </div>
                    </div>
                    <div class="hydro-metric-col" style="justify-content: flex-end; align-items: flex-end; text-align: right;">
                        <div class="hydro-metric-val">
                            <span class="val-num">{{ $valStr }}</span>
                            @if($unitStr)<span class="val-unit">{{ $unitStr }}</span>@endif
                        </div>
                        <div class="hydro-sub-label">3.110</div>
                        <div class="hydro-meta-info" style="margin-top: 6px;">
                            <span><i class="bx bxs-battery me-1"></i>92%</span>
                            <span><i class="bx bx-signal-5 me-1"></i>4/4</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="hydro-card">
                <div class="hydro-card-header">
                    <span class="hydro-station-name">{{ $project->displayName() }}</span>
                    <span class="hydro-time">{{ now()->format('H:i') }}</span>
                </div>
                <div class="hydro-card-body">
                    <div class="hydro-metric-col">
                        <span class="hydro-metric-label">Status</span>
                        <div class="hydro-metric-val"><span class="val-num" style="font-size: 15px;">Belum ada sensor</span></div>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="dash-legend-card" id="dash-legend-card" aria-label="Legenda Peta">
        <div class="legend-card-header">
            <span>Legenda Peta</span>
        </div>
        <div class="legend-card-body">
            <label class="legend-checkbox-item">
                <input type="checkbox" id="chk-sensor" checked>
                <span class="legend-symbol-icon" style="color: #34d399; font-size: 14px;">●</span>
                <span class="legend-text">Sensor Pemantauan</span>
            </label>
            <label class="legend-checkbox-item">
                <input type="checkbox" id="chk-corridor" checked>
                <span class="legend-symbol sym-corridor"></span>
                <span class="legend-text">Monitoring Corridor</span>
            </label>
            <label class="legend-checkbox-item">
                <input type="checkbox" id="chk-ms" checked>
                <span class="legend-symbol-icon sym-ms">🗼</span>
                <span class="legend-text">Monitoring Station</span>
            </label>
            <label class="legend-checkbox-item">
                <input type="checkbox" id="chk-ws" checked>
                <span class="legend-symbol-icon sym-ws">🔔</span>
                <span class="legend-text">Warning Station</span>
            </label>
            <label class="legend-checkbox-item">
                <input type="checkbox" id="chk-river" checked>
                <span class="legend-symbol sym-river"></span>
                <span class="legend-text">Jaringan Sungai</span>
            </label>
            <label class="legend-checkbox-item">
                <input type="checkbox" id="chk-boundary" checked>
                <span class="legend-symbol sym-boundary"></span>
                <span class="legend-text">Batas Administrasi</span>
            </label>
            <label class="legend-checkbox-item">
                <input type="checkbox" id="chk-labels" checked>
                <span class="legend-symbol-badge">Aa</span>
                <span class="legend-text">Label Stasiun</span>
            </label>
            <label class="legend-checkbox-item">
                <input type="checkbox" id="chk-cfpe" checked>
                <span class="legend-symbol sym-cfpe"></span>
                <span class="legend-text">CFPE Reference Route</span>
            </label>
        </div>
    </div>
@include('modules.dashboard.partials.monitoring-panel')
<script src="{{ URL::asset('build/libs/leaflet/leaflet.js') }}"></script>
<script src="{{ asset('build/js/pages/dashboard-weather-widget.js') }}?v={{ filemtime(public_path('build/js/pages/dashboard-weather-widget.js')) }}"></script>
<script src="{{ asset('build/js/pages/project-map-monitoring.js') }}?v={{ filemtime(public_path('build/js/pages/project-map-monitoring.js')) }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        DashboardWeatherWidget.mount(document.getElementById('dashboard-weather-widget'), {
            projectLocations: @json($projectWeatherLocations)
        });
        var mapElement = document.getElementById('sensor-cluster-map');

        if (!mapElement || typeof L === 'undefined') {
            return;
        }

        var clusters = @json($mapClusters ?? []);
        var sensorPoints = @json($mapSensors ?? []);
        var warningStations = @json($mapWarningStations ?? []);
        var initialAlertActive = @json($dashboardAlertActive ?? false);
        var corridorsData = {!! json_encode($corridorsJsonData) !!};
        var spatialCorridors = [];
        var corridorPolylines = [];
        var mapDataUrl = @json(route('dashboard.projects.map-data', $project, false));
        var hasAppliedInitialFocus = false;
        var latestMapData = {
            clusters: clusters,
            sensors: sensorPoints,
            warningStations: warningStations,
            alert_active: initialAlertActive
        };
        var fitFilteredButton = document.getElementById('map-fit-filtered');
        // Keep the same Semeru region and zoom limits as the original monitoring map.
        var mapMinZoom = 12;
        var initialMapZoom = 12;
        var userAdjustedView = false;
        var isApplyingMapFocus = false;
        var mapMaxZoom = 16;
        var defaultMapCenter = [-8.1724, 112.9716];
        var semeruMapBounds = L.latLngBounds(
            [-8.371469032, 112.783198121],
            [-8.055529857, 113.249208404]
        );
        var semeruFocusBounds = L.latLngBounds(
            [-8.2390, 112.9220],
            [-8.1080, 113.0185]
        );
        var defaultMapZoom = initialMapZoom;


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

        function hasCoordinate(item) {
            return item.lat != null && item.lng != null &&
                Number.isFinite(Number(item.lat)) && Number.isFinite(Number(item.lng)) &&
                Math.abs(Number(item.lat)) <= 90 && Math.abs(Number(item.lng)) <= 180;
        }

        function applyMapFocus() {
            if (hasAppliedInitialFocus) {
                return;
            }

            var padding = projectMapViewportPadding(
                mapElement.getBoundingClientRect(),
                document.getElementById('map-monitor-panel').getBoundingClientRect(),
                document.querySelector('.project-map-header').getBoundingClientRect()
            );
            isApplyingMapFocus = true;
            map.fitBounds(semeruFocusBounds, Object.assign(padding, { maxZoom: initialMapZoom, animate: false }));
            isApplyingMapFocus = false;
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
            maxZoom: mapMaxZoom,
            maxBounds: semeruMapBounds,
            maxBoundsViscosity: 1
        }).setView(defaultMapCenter, defaultMapZoom);
        map.createPane('warning-stations').style.zIndex = 625;
        map.on('movestart', function () {
            if (!isApplyingMapFocus) userAdjustedView = true;
        });
        map.on('resize', function () {
            if (!userAdjustedView) {
                hasAppliedInitialFocus = false;
                applyMapFocus();
            }
        });

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
        var corridorLayer = L.layerGroup().addTo(map);
        var cfpeRouteLayer = L.layerGroup().addTo(map);
        var riverLayer = L.layerGroup().addTo(map);
        var boundaryLayer = L.layerGroup().addTo(map);
        var monitoringStationLayer = L.layerGroup().addTo(map);
        var spatialLayer = L.featureGroup().addTo(map);
        var tdeLayer = L.layerGroup().addTo(map);
        var monitoring = new ProjectMapMonitoring({
            panel: document.getElementById('map-monitor-panel'),
            openButton: document.getElementById('map-monitor-open'),
            sensorDialog: document.getElementById('map-sensor-detail'),
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
            // Keep colocated marker offsets stable while the sensor listing changes priority.
            sensorPoints.filter(hasCoordinate).sort(function (a, b) {
                return String(a.name).localeCompare(String(b.name), 'id', { numeric: true });
            }).forEach(function (sensor) {
                var sensorDanger = isDanger(sensor);
                var color = sensorDanger ? '#f46a6a' : (statusColors[sensor.status] || '#50a5f1');
                var statusClass = sensorDanger
                    ? 'danger'
                    : (sensor.status === 'Waspada' ? 'warning' : 'live');
                var displayPoint = sensorDisplayPoint(sensor, sensorIndexByCoordinate);
                var labelValue = markerLabelValue(sensor);
                var sensorDotColor = sensorDanger ? '#ef4444' : (color || '#10b981');
                var sensorIcon = L.divIcon({
                    className: 'map-badge-marker-wrap',
                    html: '<div class="map-badge-marker">' +
                        '<span class="map-marker-dot" style="background-color: ' + sensorDotColor + ';"></span>' +
                        '<span class="map-marker-badge">' +
                            escapeHtml(sensor.name || '-') +
                            (labelValue && labelValue !== '-' ? '<small>' + escapeHtml(labelValue) + '</small>' : '') +
                        '</span>' +
                    '</div>',
                    iconSize: [80, 24],
                    iconAnchor: [5, 12],
                    popupAnchor: [40, -12]
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

            warningStations.filter(hasCoordinate).forEach(function (station) {
                var stationDanger = Boolean(station.is_danger);
                var color = stationDanger ? '#f46a6a' : (statusColors[station.status] || '#f1b44c');
                var warningDotColor = stationDanger ? '#ef4444' : (color || '#f59e0b');

                var warningIcon = L.divIcon({
                    className: 'map-badge-marker-wrap',
                    html: '<div class="map-badge-marker">' +
                        '<span class="map-marker-dot" style="background-color: ' + warningDotColor + ';"></span>' +
                        '<span class="map-marker-badge">' +
                            escapeHtml(station.station_code || station.name || '-') +
                        '</span>' +
                    '</div>',
                    iconSize: [80, 24],
                    iconAnchor: [5, 12],
                    popupAnchor: [40, -12]
                });

                L.marker([station.lat, station.lng], {
                    title: 'Detail Warning Station: ' + station.name,
                    alt: 'Detail Warning Station: ' + station.name,
                    pane: 'warning-stations',
                    icon: warningIcon,
                    keyboard: true
                }).on('click', function () {
                    monitoring.selectWarningStation(station.id);
                }).addTo(warningLayer);
            });
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
            collapsed: true,
            position: 'topleft'
        }).addTo(map);

        fitFilteredButton.addEventListener('click', function () {
            userAdjustedView = false;
            hasAppliedInitialFocus = false;
            applyMapFocus();
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
                        sensorPoints = data.sensors || [];
                        warningStations = data.warningStations || [];
                        clusters = data.clusters || [];
                        initialAlertActive = data.alert_active;
                        latestMapData = data;
                        applyDashboardFilters();
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
        setInterval(refreshMapData, 3000);

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
                spatialCorridors = data.corridors || [];

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
                            var poly = L.polyline(latLngs, { color: '#0D47A1', weight: 3, opacity: 0.7 })
                                .bindPopup('<strong>Corridor: ' + escapeHtml(corridor.name) + '</strong>')
                                .addTo(corridorLayer);
                            corridorPolylines.push({
                                name: corridor.name,
                                code: corridor.corridor_code,
                                polyline: poly
                            });
                        }
                    });
                });

                // Draw CFPE routes
                (data.routes || []).forEach(function (route) {
                    if (!route.path_coordinates || !route.path_coordinates.length) return;
                    if (route.route_type === 'monitoring_corridor') return;
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
                                .addTo(cfpeRouteLayer);
                        }
                    });

                    // BM points
                    (route.points || []).forEach(function (p) {
                        if (!p.latitude || !p.longitude) return;
                        L.circleMarker([p.latitude, p.longitude], {
                            radius: 4, fillColor: '#e74c3c', color: '#fff', weight: 1, fillOpacity: 0.8
                        }).bindPopup('<strong>' + escapeHtml(p.point_code) + '</strong>' +
                            (p.chainage ? '<br>Chainage: ' + p.chainage.toFixed(0) + ' m' : ''))
                        .addTo(cfpeRouteLayer);
                    });
                });

                // Draw information layers
                (data.information_layers || []).forEach(function (layer) {
                    if (!layer.layer_payload || !layer.layer_payload.features) return;
                    var color = layer.style_color || '#4CAF50';
                    var layerName = (layer.name || '').toLowerCase();
                    var targetLayer = (layerName.indexOf('sungai') >= 0 || layerName.indexOf('river') >= 0 || layerName.indexOf('flood') >= 0 || layerName.indexOf('lahar') >= 0)
                        ? riverLayer
                        : ((layerName.indexOf('batas') >= 0 || layerName.indexOf('admin') >= 0 || layerName.indexOf('boundary') >= 0)
                            ? boundaryLayer
                            : spatialLayer);

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
                        }).addTo(targetLayer);
                    } catch (e) {}
                });

                // Draw monitoring stations
                (data.monitoring_stations || []).forEach(function (station) {
                    if (!station.latitude || !station.longitude) return;
                    var msIcon = L.divIcon({
                        className: 'map-badge-marker-wrap',
                        html: '<div class="map-badge-marker">' +
                            '<span class="map-marker-dot dot-info"></span>' +
                            '<span class="map-marker-badge">' +
                                escapeHtml(station.station_code || station.name) +
                            '</span>' +
                        '</div>',
                        iconSize: [80, 24],
                        iconAnchor: [5, 12],
                        popupAnchor: [40, -12]
                    });
                    var msMarker = L.marker([station.latitude, station.longitude], {
                        icon: msIcon
                    }).bindPopup(
                        '<strong>📡 Monitoring Station</strong><br>' +
                        escapeHtml(station.station_code) + '<br>' +
                        escapeHtml(station.name) + '<br>' +
                        '<span class="badge bg-info">' + escapeHtml(station.status || 'Active') + '</span>'
                    );
                    msMarker.addTo(monitoringStationLayer);
                });

                // Warning stations use the live warningLayer so every icon opens the same details.
                // Overlay geometry must never expand the locked Semeru viewport.
                applyMapFocus();
            })
            .catch(function () {});
        }

        var huluIcon = L.divIcon({
            className: 'map-badge-marker-wrap',
            html: '<div class="map-badge-marker">' +
                  '<span class="map-marker-dot" style="background-color:#0f766e;"></span>' +
                  '<span class="map-marker-badge">HULU <i class="bx bx-down-arrow-alt" style="color:#34d399;"></i></span>' +
                  '</div>',
            iconSize: [75, 24],
            iconAnchor: [5, 12]
        });
        L.marker([-8.125, 112.930], { icon: huluIcon, interactive: false }).addTo(map);

        var hilirIcon = L.divIcon({
            className: 'map-badge-marker-wrap',
            html: '<div class="map-badge-marker">' +
                  '<span class="map-marker-dot" style="background-color:#b45309;"></span>' +
                  '<span class="map-marker-badge">HILIR</span>' +
                  '</div>',
            iconSize: [65, 24],
            iconAnchor: [5, 12]
        });
        L.marker([-8.235, 113.020], { icon: hilirIcon, interactive: false }).addTo(map);

        var chkSensor = document.getElementById('chk-sensor');
        if (chkSensor) {
            chkSensor.addEventListener('change', function () {
                if (this.checked) map.addLayer(sensorLayer);
                else map.removeLayer(sensorLayer);
            });
        }

        var chkWs = document.getElementById('chk-ws');
        if (chkWs) {
            chkWs.addEventListener('change', function () {
                if (this.checked) map.addLayer(warningLayer);
                else map.removeLayer(warningLayer);
            });
        }

        var chkCorridor = document.getElementById('chk-corridor');
        var chkMs = document.getElementById('chk-ms');
        var chkRiver = document.getElementById('chk-river');
        var chkBoundary = document.getElementById('chk-boundary');
        var chkCfpe = document.getElementById('chk-cfpe');
        var chkLabels = document.getElementById('chk-labels');

        if (chkCorridor) {
            chkCorridor.addEventListener('change', function () {
                if (this.checked) map.addLayer(corridorLayer);
                else map.removeLayer(corridorLayer);
            });
        }
        if (chkMs) {
            chkMs.addEventListener('change', function () {
                if (this.checked) map.addLayer(monitoringStationLayer);
                else map.removeLayer(monitoringStationLayer);
            });
        }
        if (chkRiver) {
            chkRiver.addEventListener('change', function () {
                if (this.checked) map.addLayer(riverLayer);
                else map.removeLayer(riverLayer);
            });
        }
        if (chkBoundary) {
            chkBoundary.addEventListener('change', function () {
                if (this.checked) map.addLayer(boundaryLayer);
                else map.removeLayer(boundaryLayer);
            });
        }
        if (chkCfpe) {
            chkCfpe.addEventListener('change', function () {
                if (this.checked) map.addLayer(cfpeRouteLayer);
                else map.removeLayer(cfpeRouteLayer);
            });
        }
        if (chkLabels) {
            chkLabels.addEventListener('change', function () {
                var mapContainer = document.getElementById('sensor-cluster-map');
                if (mapContainer) {
                    if (this.checked) {
                        mapContainer.classList.remove('hide-station-labels');
                    } else {
                        mapContainer.classList.add('hide-station-labels');
                    }
                }
            });
        }

        var panelToggleBtn = document.querySelector('[data-monitor="panel-toggle"]');
        var openBtn = document.getElementById('map-monitor-open');
        var legendCard = document.getElementById('dash-legend-card');
        var mapHeader = document.querySelector('.project-map-header');

        if (panelToggleBtn) {
            panelToggleBtn.addEventListener('click', function () {
                setTimeout(function () {
                    var panel = document.getElementById('map-monitor-panel');
                    var isHidden = panel && panel.hidden;
                    if (legendCard) {
                        if (isHidden) legendCard.classList.add('sidebar-collapsed');
                        else legendCard.classList.remove('sidebar-collapsed');
                    }
                    if (mapHeader) {
                        if (isHidden) mapHeader.classList.add('sidebar-collapsed');
                        else mapHeader.classList.remove('sidebar-collapsed');
                    }
                    if (isHidden && typeof map !== 'undefined' && map.panBy) {
                        map.panBy([-180, 0], { duration: 0.4 });
                    }
                }, 50);
            });
        }
        if (openBtn) {
            openBtn.addEventListener('click', function () {
                setTimeout(function () {
                    if (legendCard) legendCard.classList.remove('sidebar-collapsed');
                    if (mapHeader) mapHeader.classList.remove('sidebar-collapsed');
                    if (typeof map !== 'undefined' && map.panBy) {
                        map.panBy([180, 0], { duration: 0.4 });
                    }
                }, 50);
            });
        }

        var corridorSelect = document.getElementById('dash-corridor-select');
        var clusterSelect = document.getElementById('dash-cluster-select');

        function applyDashboardFilters(shouldFly) {
            var selectedCorridor = corridorSelect ? corridorSelect.value.trim() : 'all';
            var selectedCluster = clusterSelect ? clusterSelect.value.trim() : 'all';
            var doFly = (shouldFly !== false);

            // 1. Zoom/Pan & Highlight Corridor
            if (selectedCorridor !== 'all') {
                var foundMatch = false;

                // Check corridorPolylines
                var matchingPolys = corridorPolylines.filter(function (cp) {
                    return (cp.name && cp.name.toLowerCase() === selectedCorridor.toLowerCase()) ||
                           (cp.code && cp.code.toLowerCase() === selectedCorridor.toLowerCase());
                });

                if (matchingPolys.length > 0) {
                    var polyGroup = L.featureGroup(matchingPolys.map(function (p) { return p.polyline; }));
                    if (doFly) {
                        map.fitBounds(polyGroup.getBounds(), { padding: [60, 60], maxZoom: 15 });
                    }
                    foundMatch = true;

                    corridorPolylines.forEach(function (cp) {
                        if (matchingPolys.indexOf(cp) >= 0) {
                            cp.polyline.setStyle({ color: '#ffb300', weight: 5, opacity: 1 });
                            cp.polyline.bringToFront();
                        } else {
                            cp.polyline.setStyle({ color: '#0D47A1', weight: 2, opacity: 0.4 });
                        }
                    });
                } else {
                    var cObj = corridorsData.find(function (c) {
                        return (c.name && c.name.toLowerCase() === selectedCorridor.toLowerCase()) ||
                               (c.code && c.code.toLowerCase() === selectedCorridor.toLowerCase());
                    }) || spatialCorridors.find(function (c) {
                        return (c.name && c.name.toLowerCase() === selectedCorridor.toLowerCase()) ||
                               (c.corridor_code && c.corridor_code.toLowerCase() === selectedCorridor.toLowerCase());
                    });

                    if (cObj && cObj.coordinates && cObj.coordinates.length > 0) {
                        var pts = [];
                        function collectPoints(arr) {
                            if (!arr) return;
                            if (typeof arr.lat === 'number' && typeof arr.lng === 'number') {
                                pts.push([arr.lat, arr.lng]);
                            } else if (Array.isArray(arr)) {
                                if (typeof arr[0] === 'number' && typeof arr[1] === 'number') {
                                    pts.push(Math.abs(arr[0]) > Math.abs(arr[1]) ? [arr[1], arr[0]] : [arr[0], arr[1]]);
                                } else {
                                    arr.forEach(collectPoints);
                                }
                            }
                        }
                        collectPoints(cObj.coordinates);
                        if (pts.length === 1 && doFly) {
                            map.flyTo(pts[0], 15, { duration: 0.8 });
                            foundMatch = true;
                        } else if (pts.length > 1 && doFly) {
                            map.fitBounds(L.latLngBounds(pts), { padding: [60, 60], maxZoom: 15 });
                            foundMatch = true;
                        }
                    }
                }

                if (!foundMatch && doFly) {
                    var matchingWs = warningStations.filter(function (w) {
                        return (w.corridor || w.workspace || w.name || '').toLowerCase().indexOf(selectedCorridor.toLowerCase()) >= 0;
                    });
                    if (matchingWs.length > 0 && hasCoordinate(matchingWs[0])) {
                        map.flyTo([matchingWs[0].lat, matchingWs[0].lng], 14, { duration: 0.8 });
                    }
                }
            } else {
                corridorPolylines.forEach(function (cp) {
                    cp.polyline.setStyle({ color: '#0D47A1', weight: 3, opacity: 0.7 });
                });
            }

            // 2. Zoom to cluster if cluster selected and corridor is all
            if (doFly && selectedCorridor === 'all' && selectedCluster !== 'all') {
                var clMatch = clusters.find(function (c) {
                    return (c.name || '').toLowerCase() === selectedCluster.toLowerCase();
                });
                if (clMatch && hasCoordinate(clMatch)) {
                    map.flyTo([clMatch.lat, clMatch.lng], 13, { duration: 0.8 });
                }
            }

            // 3. Reset to Semeru focus if both are 'all'
            if (doFly && selectedCorridor === 'all' && selectedCluster === 'all' && userAdjustedView) {
                map.fitBounds(semeruFocusBounds, { maxZoom: initialMapZoom, animate: true });
            }

            // 4. Filter sensors and warning stations
            var filteredSensors = sensorPoints.filter(function (s) {
                if (selectedCorridor !== 'all') {
                    var sCorr = (s.corridor || s.workspace || s.station || '').toLowerCase();
                    if (sCorr.indexOf(selectedCorridor.toLowerCase()) === -1 && selectedCorridor.toLowerCase().indexOf(sCorr) === -1) {
                        return false;
                    }
                }
                if (selectedCluster !== 'all') {
                    var sClust = (s.workspace || '').toLowerCase();
                    if (sClust.indexOf(selectedCluster.toLowerCase()) === -1) {
                        return false;
                    }
                }
                return true;
            });

            var filteredWarningStations = warningStations.filter(function (w) {
                if (selectedCorridor !== 'all') {
                    var wCorr = (w.corridor || w.workspace || w.name || '').toLowerCase();
                    if (wCorr.indexOf(selectedCorridor.toLowerCase()) === -1 && selectedCorridor.toLowerCase().indexOf(wCorr) === -1) {
                        return false;
                    }
                }
                if (selectedCluster !== 'all') {
                    var wClust = (w.workspace || '').toLowerCase();
                    if (wClust.indexOf(selectedCluster.toLowerCase()) === -1) {
                        return false;
                    }
                }
                return true;
            });

            var filteredClusters = clusters.filter(function (c) {
                if (selectedCluster !== 'all') {
                    var cName = (c.name || '').toLowerCase();
                    if (cName.indexOf(selectedCluster.toLowerCase()) === -1) {
                        return false;
                    }
                }
                return true;
            });

            renderMapData({
                clusters: filteredClusters,
                sensors: filteredSensors,
                warningStations: filteredWarningStations,
                alert_active: initialAlertActive
            });

            // 5. Highlight active row in Status Eskalasi table
            document.querySelectorAll('#map-monitor-panel-body .widget-table tbody tr').forEach(function (tr) {
                var firstTd = tr.querySelector('td strong');
                if (firstTd) {
                    var rowName = firstTd.textContent.trim().toLowerCase();
                    if (selectedCorridor !== 'all' && rowName === selectedCorridor.toLowerCase()) {
                        tr.style.backgroundColor = 'rgba(255, 179, 0, 0.2)';
                    } else {
                        tr.style.backgroundColor = '';
                    }
                }
            });
        }

        if (corridorSelect) {
            corridorSelect.addEventListener('change', function () { applyDashboardFilters(true); });
        }
        if (clusterSelect) {
            clusterSelect.addEventListener('change', function () { applyDashboardFilters(true); });
        }

        document.querySelectorAll('#map-monitor-panel-body .widget-table tbody tr').forEach(function (tr) {
            var firstTd = tr.querySelector('td strong');
            if (firstTd && corridorSelect) {
                tr.style.cursor = 'pointer';
                tr.addEventListener('click', function (e) {
                    if (e.target.closest('.siren-pill-btn')) return;
                    var name = firstTd.textContent.trim();
                    var matchingOption = Array.from(corridorSelect.options).find(function (opt) {
                        return opt.text.trim().toLowerCase() === name.toLowerCase();
                    });
                    if (matchingOption) {
                        corridorSelect.value = matchingOption.value;
                        applyDashboardFilters(true);
                    }
                });
            }
        });

        document.querySelectorAll('.dash-timeline-item').forEach(function (item) {
            item.addEventListener('click', function () {
                document.querySelectorAll('.dash-timeline-item').forEach(function (el) { el.classList.remove('active'); });
                this.classList.add('active');
            });
        });

        function updateLiveClock() {
            var clockEl = document.getElementById('live-time-str');
            if (clockEl) {
                var d = new Date();
                var h = String(d.getHours()).padStart(2, '0');
                var m = String(d.getMinutes()).padStart(2, '0');
                clockEl.textContent = h + ':' + m;
            }
        }
        setInterval(updateLiveClock, 1000);

        setTimeout(loadSpatialOverlay, 2000);

    });
</script>

</body>
</html>
