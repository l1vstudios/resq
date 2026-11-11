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
            padding: 8px 14px;
            background: rgba(22, 33, 28, 0.82);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.28);
            color: #ffffff;
        }
        .dash-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .dash-back-btn, .dash-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #ffffff;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .dash-back-btn:hover, .dash-action-btn:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
        }
        .dash-select-container {
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        .dash-select {
            appearance: none;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 6px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 26px 6px 10px;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .dash-select:hover, .dash-select:focus {
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 255, 255, 0.3);
        }
        .dash-select option {
            background: #1e293b;
            color: #ffffff;
        }
        .dash-select-container i {
            position: absolute;
            right: 8px;
            pointer-events: none;
            font-size: 14px;
            color: #9ca3af;
        }
        .dash-weather-timeline {
            display: flex;
            align-items: center;
            gap: 4px;
            background: rgba(0, 0, 0, 0.25);
            padding: 3px 6px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.08);
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
        }
        .hydro-card {
            background: rgba(22, 33, 28, 0.82);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 6px;
            padding: 10px 12px;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.22);
        }
        .hydro-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
            padding-bottom: 5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .hydro-station-name {
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
        }
        .hydro-time {
            font-size: 10.5px;
            color: #9ca3af;
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
            font-size: 10px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .hydro-metric-val {
            display: flex;
            align-items: baseline;
            gap: 3px;
        }
        .hydro-metric-val .val-num {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.1;
        }
        .hydro-metric-val .val-unit {
            font-size: 11px;
            color: #9ca3af;
            font-weight: 500;
        }
        .hydro-trend {
            font-size: 10.5px;
            font-weight: 600;
            margin-top: 3px;
        }
        .trend-up {
            color: #34d399;
        }
        .trend-down {
            color: #f87171;
        }
        .hydro-meta-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 10.5px;
            color: #9ca3af;
            margin-top: 4px;
        }
        .dash-legend-card {
            position: absolute;
            z-index: 900;
            bottom: 16px;
            left: 560px;
            width: 220px;
            background: rgba(22, 33, 28, 0.82);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 6px;
            padding: 10px 14px;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.22);
        }
        .legend-card-header {
            font-size: 12.5px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
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
        .map-badge-tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
            padding: 3px 8px;
            background: #ffffff;
            color: #1e293b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(0, 0, 0, 0.15);
        }
        .hulu-tag {
            color: #0f766e;
            border-color: #0f766e;
        }
        .hilir-tag {
            color: #b45309;
            border-color: #b45309;
        }
        .cfpe-route-marker-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            background: rgba(22, 33, 28, 0.85);
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 700;
            border-radius: 4px;
            border: 1px solid rgba(239, 68, 68, 0.6);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }
        .cfpe-route-marker-tag .route-dash {
            color: #ef4444;
            font-weight: 900;
        }
        .map-station-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            background: rgba(20, 30, 25, 0.9);
            border-radius: 4px;
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .pill-ms {
            border-color: rgba(59, 130, 246, 0.6);
        }
        .pill-ws {
            border-color: rgba(245, 158, 11, 0.6);
        }
        .warning-station-icon { display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; background: #fff; border: 2px solid #b7791f; border-radius: 50%; color: #854d0e; box-shadow: 0 3px 12px rgba(18,38,63,.25); font-size: 22px; }
        .warning-station-marker:focus-visible { outline: 3px solid #1849a9; outline-offset: 5px; border-radius: 50%; }
        @media (max-width: 1200px) {
            .dash-legend-card { display: none; }
        }
        @media (max-width: 991px) {
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

    <header class="project-map-header">
        <div class="dash-header-left">
            <a href="{{ route('dashboard') }}" class="dash-back-btn" aria-label="Kembali ke daftar project" title="Kembali ke daftar project">
                <i class="bx bx-arrow-back" aria-hidden="true"></i>
            </a>
            <div class="dash-select-container">
                <select class="dash-select" id="dash-corridor-select" aria-label="Pilih Koridor">
                    <option value="kali-mujur" selected>Kali Mujur</option>
                    <option value="kali-leprak">Kali Leprak</option>
                    <option value="kali-lengkong">Kali Lengkong</option>
                    <option value="kali-glidik">Kali Glidik</option>
                </select>
                <i class="bx bx-chevron-down" aria-hidden="true"></i>
            </div>
            <div class="dash-select-container">
                <select class="dash-select" id="dash-cluster-select" aria-label="Pilih Cluster">
                    <option value="cluster-01" selected>Cluster 01</option>
                    <option value="cluster-02">Cluster 02</option>
                    <option value="cluster-03">Cluster 03</option>
                </select>
                <i class="bx bx-chevron-down" aria-hidden="true"></i>
            </div>
        </div>

        <div class="dash-weather-timeline">
            <div class="dash-timeline-item active">
                <span class="t-date">09 Nov</span>
                <i class="bx bx-sun text-warning" aria-hidden="true"></i>
            </div>
            <div class="dash-timeline-item">
                <span class="t-date">10 Nov</span>
                <i class="bx bx-sun text-warning" aria-hidden="true"></i>
            </div>
            <div class="dash-timeline-item">
                <span class="t-date">11 Nov</span>
                <i class="bx bx-cloud text-light" aria-hidden="true"></i>
            </div>
            <div class="dash-timeline-item">
                <span class="t-date">12 Nov</span>
                <i class="bx bx-cloud-rain text-info" aria-hidden="true"></i>
            </div>
            <div class="dash-timeline-item">
                <span class="t-date">13 Nov</span>
                <i class="bx bx-cloud text-light" aria-hidden="true"></i>
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

    <div class="dash-hydrology-container" id="dash-hydrology-panel" aria-label="Pemantauan Hidrologi">
        <div class="hydro-card">
            <div class="hydro-card-header">
                <span class="hydro-station-name">Hidrologi - MS-005</span>
                <span class="hydro-time">14:20</span>
            </div>
            <div class="hydro-card-body">
                <div class="hydro-metric-col">
                    <span class="hydro-metric-label">TMA</span>
                    <div class="hydro-metric-val">
                        <span class="val-num">2.45</span>
                        <span class="val-unit">m</span>
                    </div>
                    <span class="hydro-trend trend-up">+3 cm / 430m</span>
                </div>
                <div class="hydro-metric-col">
                    <span class="hydro-metric-label">Kecepatan Arus</span>
                    <div class="hydro-metric-val">
                        <span class="val-num">3.20</span>
                        <span class="val-unit">m/s</span>
                    </div>
                    <div class="hydro-meta-info">
                        <span><i class="bx bxs-battery me-1" aria-hidden="true"></i>87%</span>
                        <span><i class="bx bx-signal-5 me-1" aria-hidden="true"></i>4/4</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="hydro-card">
            <div class="hydro-card-header">
                <span class="hydro-station-name">Hidrologi - MS-006</span>
                <span class="hydro-time">14:22</span>
            </div>
            <div class="hydro-card-body">
                <div class="hydro-metric-col">
                    <span class="hydro-metric-label">TMA</span>
                    <div class="hydro-metric-val">
                        <span class="val-num">1.92</span>
                        <span class="val-unit">m</span>
                    </div>
                    <span class="hydro-trend trend-up">+5 cm / 430m</span>
                </div>
                <div class="hydro-metric-col">
                    <span class="hydro-metric-label">Kecepatan Arus</span>
                    <div class="hydro-metric-val">
                        <span class="val-num">2.60</span>
                        <span class="val-unit">m/s</span>
                    </div>
                    <div class="hydro-meta-info">
                        <span><i class="bx bxs-battery me-1" aria-hidden="true"></i>78%</span>
                        <span><i class="bx bx-signal-4 me-1" aria-hidden="true"></i>3/4</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="hydro-card">
            <div class="hydro-card-header">
                <span class="hydro-station-name">Hidrologi - MS-007</span>
                <span class="hydro-time">14:21</span>
            </div>
            <div class="hydro-card-body">
                <div class="hydro-metric-col">
                    <span class="hydro-metric-label">TMA</span>
                    <div class="hydro-metric-val">
                        <span class="val-num">2.18</span>
                        <span class="val-unit">m</span>
                    </div>
                    <span class="hydro-trend trend-up">+2 cm / 430m</span>
                </div>
                <div class="hydro-metric-col">
                    <span class="hydro-metric-label">Kecepatan Arus</span>
                    <div class="hydro-metric-val">
                        <span class="val-num">2.85</span>
                        <span class="val-unit">m/s</span>
                    </div>
                    <div class="hydro-meta-info">
                        <span><i class="bx bxs-battery me-1" aria-hidden="true"></i>91%</span>
                        <span><i class="bx bx-signal-5 me-1" aria-hidden="true"></i>4/4</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="hydro-card">
            <div class="hydro-card-header">
                <span class="hydro-station-name">Hidrologi - MS-008</span>
                <span class="hydro-time">14:19</span>
            </div>
            <div class="hydro-card-body">
                <div class="hydro-metric-col">
                    <span class="hydro-metric-label">TMA</span>
                    <div class="hydro-metric-val">
                        <span class="val-num">1.76</span>
                        <span class="val-unit">m</span>
                    </div>
                    <span class="hydro-trend trend-down">-1 cm / 430m</span>
                </div>
                <div class="hydro-metric-col">
                    <span class="hydro-metric-label">Kecepatan Arus</span>
                    <div class="hydro-metric-val">
                        <span class="val-num">1.94</span>
                        <span class="val-unit">m/s</span>
                    </div>
                    <div class="hydro-meta-info">
                        <span><i class="bx bxs-battery-low me-1" aria-hidden="true"></i>68%</span>
                        <span><i class="bx bx-signal-3 me-1" aria-hidden="true"></i>2/4</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-legend-card" id="dash-legend-card" aria-label="Legenda Peta">
        <div class="legend-card-header">
            <span>Legenda Peta</span>
        </div>
        <div class="legend-card-body">
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
                if (stationDanger) {
                    L.circle([station.lat, station.lng], {
                        radius: 150000,
                        color: '#f46a6a',
                        fillColor: '#f46a6a',
                        fillOpacity: 0.24,
                        weight: 5,
                        className: 'danger-map-radius',
                        interactive: false
                    }).addTo(warningLayer);
                }

                var warningIcon = L.divIcon({
                    className: 'sensor-live-icon warning-station-marker',
                    html: stationDanger
                        ? '<span class="danger-warning-icon"><i class="bx bxs-error" aria-hidden="true"></i></span>'
                        : '<span class="warning-station-icon"><i class="bx bxs-bell-ring" aria-hidden="true"></i></span>',
                    iconSize: [36, 36],
                    iconAnchor: [18, 18]
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

                L.circleMarker([station.lat, station.lng], {
                    radius: stationDanger ? 16 : 11,
                    color: color,
                    fillColor: stationDanger ? color : '#ffffff',
                    fillOpacity: stationDanger ? 0.45 : 0.2,
                    weight: stationDanger ? 4 : 3,
                    className: stationDanger ? 'danger-map-radius' : '',
                    interactive: false
                }).addTo(warningLayer);
            });

            applyMapFocus();
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
                        renderMapData(data);
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
        setInterval(refreshMapData, 1000);

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

                // Warning stations use the live warningLayer so every icon opens the same details.
                // Overlay geometry must never expand the locked Semeru viewport.
                applyMapFocus();
            })
            .catch(function () {});
        }

        var huluIcon = L.divIcon({
            className: 'map-hulu-marker-wrap',
            html: '<div class="map-badge-tag hulu-tag"><span>HULU</span> <i class="bx bx-down-arrow-alt"></i></div>',
            iconSize: [64, 26],
            iconAnchor: [32, 13]
        });
        L.marker([-8.125, 112.930], { icon: huluIcon, interactive: false }).addTo(map);

        var hilirIcon = L.divIcon({
            className: 'map-hilir-marker-wrap',
            html: '<div class="map-badge-tag hilir-tag"><span>HILIR</span></div>',
            iconSize: [54, 26],
            iconAnchor: [27, 13]
        });
        L.marker([-8.235, 113.020], { icon: hilirIcon, interactive: false }).addTo(map);

        var cfpeRouteIcon = L.divIcon({
            className: 'map-cfpe-tag-wrap',
            html: '<span class="cfpe-route-marker-tag"><span class="route-dash">---</span> CFPE Route</span>',
            iconSize: [110, 22],
            iconAnchor: [55, 11]
        });
        L.marker([-8.170, 112.985], { icon: cfpeRouteIcon, interactive: false }).addTo(map);

        var stationTagLayer = L.layerGroup().addTo(map);
        var stationTags = [
            { name: 'MS-001', lat: -8.138, lng: 112.942, type: 'ms' },
            { name: 'WS-001', lat: -8.156, lng: 112.956, type: 'ws' },
            { name: 'MS-002', lat: -8.178, lng: 112.972, type: 'ms' },
            { name: 'WS-002', lat: -8.192, lng: 112.988, type: 'ws' },
            { name: 'MS-003', lat: -8.208, lng: 113.002, type: 'ms' },
            { name: 'MS-004', lat: -8.220, lng: 113.015, type: 'ms' }
        ];

        stationTags.forEach(function (st) {
            var tagIcon = L.divIcon({
                className: 'map-station-tag-wrap',
                html: '<div class="map-station-pill ' + (st.type === 'ms' ? 'pill-ms' : 'pill-ws') + '">' +
                      '<span class="st-icon">' + (st.type === 'ms' ? '🗼' : '🔔') + '</span>' +
                      '<span class="st-name">' + st.name + '</span>' +
                      '</div>',
                iconSize: [76, 24],
                iconAnchor: [38, 12]
            });
            L.marker([st.lat, st.lng], { icon: tagIcon, interactive: true }).bindPopup('<strong>' + st.name + '</strong>').addTo(stationTagLayer);
        });

        var chkLabels = document.getElementById('chk-labels');
        if (chkLabels) {
            chkLabels.addEventListener('change', function () {
                if (this.checked) map.addLayer(stationTagLayer);
                else map.removeLayer(stationTagLayer);
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

        [chkCorridor, chkMs, chkRiver, chkBoundary, chkCfpe].forEach(function (chk) {
            if (chk) {
                chk.addEventListener('change', function () {
                    var anyChecked = (chkCorridor && chkCorridor.checked) ||
                                     (chkMs && chkMs.checked) ||
                                     (chkRiver && chkRiver.checked) ||
                                     (chkBoundary && chkBoundary.checked) ||
                                     (chkCfpe && chkCfpe.checked);
                    if (anyChecked && !map.hasLayer(spatialLayer)) {
                        map.addLayer(spatialLayer);
                    } else if (!anyChecked && map.hasLayer(spatialLayer)) {
                        map.removeLayer(spatialLayer);
                    }
                });
            }
        });

        setTimeout(loadSpatialOverlay, 2000);

    });
</script>

</body>
</html>
