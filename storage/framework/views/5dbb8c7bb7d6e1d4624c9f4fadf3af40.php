<style>
    :root {
        --emp-navy: #071f49;
        --emp-blue: #0f4ea2;
        --emp-teal: #087b78;
        --emp-green: #168a43;
        --emp-purple: #6726a4;
        --emp-orange: #d86500;
        --emp-red: #c9362b;
        --emp-gold: #d9a223;
        --emp-ink: #112342;
        --emp-muted: #65758b;
        --emp-line: #d8e3f0;
        --emp-soft: #f5f9fc;
        --emp-panel: #ffffff;
    }

    .emp-ui {
        color: var(--emp-ink);
    }

    .emp-ui [id] {
        scroll-margin-top: 96px;
    }

    .emp-ui .card {
        border: 1px solid var(--emp-line);
        border-radius: 8px;
        box-shadow: 0 12px 28px rgba(7, 31, 73, 0.07);
        overflow: hidden;
    }

    .emp-ui .card-title {
        color: var(--emp-navy);
        font-weight: 800;
        letter-spacing: 0;
    }

    .emp-page-hero {
        background:
            linear-gradient(125deg, rgba(255,255,255,0.96) 0%, rgba(255,255,255,0.92) 55%, rgba(8,123,120,0.12) 100%),
            radial-gradient(circle at top right, rgba(15,78,162,0.16), transparent 38%);
        border: 1px solid var(--emp-line);
        border-radius: 8px;
        box-shadow: 0 14px 32px rgba(7, 31, 73, 0.08);
        margin-bottom: 18px;
        overflow: hidden;
        padding: 20px 22px;
        position: relative;
    }

    .emp-page-hero::before {
        background: linear-gradient(180deg, var(--emp-navy), var(--emp-teal));
        content: "";
        height: 100%;
        left: 0;
        position: absolute;
        top: 0;
        width: 6px;
    }

    .emp-page-eyebrow {
        color: var(--emp-teal);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .12em;
        margin-bottom: 4px;
        text-transform: uppercase;
    }

    .emp-page-title {
        color: var(--emp-navy);
        font-size: 26px;
        font-weight: 900;
        letter-spacing: 0;
        line-height: 1.15;
        margin: 0;
    }

    .emp-page-subtitle {
        color: var(--emp-muted);
        margin: 8px 0 0;
        max-width: 760px;
    }

    .emp-hero-actions,
    .ops-filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .emp-flow {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .emp-flow-step {
        align-items: center;
        background: #fff;
        border: 1px solid #bad3ec;
        border-radius: 8px;
        color: var(--emp-navy);
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        gap: 7px;
        min-height: 34px;
        padding: 7px 11px;
        text-transform: uppercase;
    }

    a.emp-flow-step {
        text-decoration: none;
    }

    a.emp-flow-step:hover {
        background: #eef8fb;
        color: var(--emp-navy);
        text-decoration: none;
    }

    .emp-flow-step i {
        color: var(--emp-teal);
        font-size: 16px;
    }

    .emp-flow-step.active {
        background: var(--emp-navy);
        border-color: var(--emp-navy);
        color: #fff;
    }

    .emp-flow-step.active i {
        color: #7de0d4;
    }

    .ops-kpi {
        min-height: 112px;
        position: relative;
    }

    .ops-kpi::before {
        background: linear-gradient(90deg, var(--emp-blue), var(--emp-teal));
        content: "";
        height: 4px;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
    }

    .ops-kpi .card-body {
        padding: 18px;
    }

    .ops-kpi .text-muted {
        color: var(--emp-muted) !important;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .05em;
        margin-bottom: 7px;
        text-transform: uppercase;
    }

    .ops-kpi h4 {
        color: var(--emp-navy);
        font-weight: 900;
        margin-bottom: 0;
    }

    .ops-kpi-action {
        flex: 0 0 auto;
        margin-top: 2px;
        white-space: nowrap;
    }

    .ops-context-line {
        color: var(--emp-muted);
        font-size: 13px;
        margin-top: 5px;
    }

    .ops-context-switcher {
        margin-bottom: 16px;
    }

    .ops-context-title {
        align-items: flex-start;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .ops-readonly-field {
        align-items: center;
        background: #f8fbfe;
        border: 1px solid #d8e3f0;
        border-radius: 7px;
        color: var(--emp-ink);
        display: flex;
        font-weight: 700;
        min-height: 38px;
        padding: 8px 12px;
    }

    .ops-map {
        background:
            linear-gradient(rgba(255,255,255,.80), rgba(255,255,255,.80)),
            linear-gradient(135deg, #d9eef0, #f2f7ec);
        border: 1px solid #c7d7e7;
        border-radius: 8px;
        min-height: 360px;
        overflow: hidden;
        width: 100%;
    }

    .ops-map-sm {
        min-height: 280px;
    }

    .ops-map-lg {
        min-height: 560px;
    }

    .ops-map-terrain-3d {
        background:
            linear-gradient(145deg, rgba(10, 42, 71, .12), rgba(255,255,255,.04)),
            #eaf3ed;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.8),
            inset 0 -18px 40px rgba(7,31,73,.08),
            0 18px 36px rgba(7,31,73,.10);
    }

    .ops-map-terrain-3d .leaflet-tile-pane {
        filter: saturate(1.08) contrast(1.06);
    }

    .ops-map-terrain-3d .leaflet-overlay-pane {
        filter: drop-shadow(0 5px 4px rgba(7,31,73,.22));
    }

    .sentinel-map-mode {
        background: rgba(7,31,73,.88);
        border: 1px solid rgba(255,255,255,.28);
        border-radius: 8px;
        box-shadow: 0 10px 22px rgba(7,31,73,.20);
        color: #fff;
        display: grid;
        gap: 2px;
        padding: 8px 10px;
    }

    .sentinel-map-mode strong {
        font-size: 11px;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .sentinel-map-mode span {
        color: #cfe7f7;
        font-size: 11px;
        font-weight: 700;
    }

    .sentinel-map-legend {
        background: rgba(255,255,255,.94);
        border: 1px solid var(--emp-line);
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(7, 31, 73, .12);
        color: var(--emp-muted);
        display: grid;
        gap: 5px;
        padding: 8px 10px;
    }

    .sentinel-map-legend span {
        align-items: center;
        display: flex;
        font-size: 11px;
        font-weight: 800;
        gap: 6px;
        white-space: nowrap;
    }

    .sentinel-map-legend i {
        border-radius: 999px;
        display: inline-block;
        height: 10px;
        width: 10px;
    }

    .ops-table {
        border: 1px solid #e4ebf4;
        border-radius: 8px;
        overflow: hidden;
    }

    .ops-table td,
    .ops-table th {
        border-color: #e8eef5;
        vertical-align: middle;
    }

    .ops-table thead th,
    .emp-ui .table-light th {
        background: #f4f8fc;
        color: var(--emp-navy);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0;
    }

    .ops-station-list {
        max-height: 420px;
        overflow: auto;
    }

    .ops-station-list .list-group-item {
        border-color: #dfe8f2;
        padding: 14px 15px;
    }

    .ops-station-list .list-group-item:hover {
        background: #f5fbfb;
        border-color: #9bcfc8;
    }

    .ops-sensor-block {
        background: #f8fbfe;
        border: 1px solid #dfe8f2;
        border-radius: 8px;
        padding: 12px;
    }

    .ops-sensor-block-head {
        align-items: center;
        display: flex;
        gap: 10px;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .ops-parameter-grid {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(auto-fit, minmax(138px, 1fr));
    }

    .ops-parameter-card {
        background: #fff;
        border: 1px solid #d9e5ef;
        border-radius: 8px;
        min-height: 92px;
        padding: 10px;
    }

    .ops-parameter-label {
        color: var(--emp-muted);
        font-size: 11px;
        font-weight: 800;
        line-height: 1.2;
        min-height: 28px;
        text-transform: uppercase;
    }

    .ops-parameter-value {
        color: var(--emp-navy);
        font-size: 22px;
        font-weight: 900;
        line-height: 1.1;
        margin-top: 4px;
        overflow-wrap: anywhere;
    }

    .ops-value-pulse {
        animation: opsValuePulse .9s ease-out;
    }

    @keyframes opsValuePulse {
        0% {
            background: #d7fff4;
            box-shadow: 0 0 0 0 rgba(8, 123, 120, .32);
        }
        100% {
            background: #fff;
            box-shadow: 0 0 0 12px rgba(8, 123, 120, 0);
        }
    }

    .ops-parameter-unit {
        color: var(--emp-teal);
        font-size: 12px;
        font-weight: 700;
        margin-top: 4px;
    }

    .ops-audit-row {
        align-items: center;
        background: #f8fbfe;
        border: 1px solid #dfe8f2;
        border-radius: 8px;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 8px;
        padding: 10px;
    }

    .ops-audit-select {
        max-width: 220px;
    }

    .ops-empty-state {
        align-items: center;
        background: #f8fbfe;
        border: 1px dashed #c7d7e7;
        border-radius: 8px;
        color: var(--emp-muted);
        display: flex;
        font-weight: 700;
        justify-content: center;
        min-height: 120px;
        padding: 14px;
        text-align: center;
    }

    .ops-hazard-modal .modal-content {
        border: 1px solid var(--emp-line);
        border-radius: 8px;
    }

    .ops-hazard-detail-list {
        max-height: 420px;
        overflow: auto;
    }

    .ops-hazard-map-wrap {
        border: 1px solid #d8e3f0;
        border-radius: 8px;
        min-height: 420px;
        overflow: hidden;
        position: relative;
    }

    .ops-hazard-map {
        min-height: 420px;
        width: 100%;
    }

    .ops-map-empty {
        align-items: center;
        background: rgba(255,255,255,.92);
        color: var(--emp-muted);
        display: flex;
        font-weight: 800;
        inset: 0;
        justify-content: center;
        padding: 20px;
        position: absolute;
        text-align: center;
        z-index: 420;
    }

    .ops-hazard-marker {
        align-items: center;
        background: var(--emp-red);
        border: 3px solid #fff;
        border-radius: 999px;
        box-shadow: 0 6px 18px rgba(201, 54, 43, .34);
        color: #fff;
        display: flex;
        font-size: 14px;
        height: 28px;
        justify-content: center;
        width: 28px;
    }

    .ops-spark {
        align-items: end;
        display: flex;
        gap: 4px;
        height: 44px;
        min-width: 130px;
    }

    .ops-spark-bar {
        background: linear-gradient(180deg, #15a6a1, var(--emp-blue));
        border-radius: 3px 3px 0 0;
        flex: 1 1 0;
        min-width: 4px;
    }

    .ops-series-header {
        align-items: flex-start;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .ops-series-toolbar {
        display: flex;
        flex: 0 1 520px;
        gap: 8px;
        justify-content: flex-end;
    }

    .ops-series-board {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .ops-series-sensor-block {
        background: #f8fbfe;
        border: 1px solid #dfe8f2;
        border-radius: 8px;
        padding: 12px;
    }

    .ops-series-sensor-head {
        align-items: center;
        border-bottom: 1px solid #e6edf5;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
    }

    .ops-series-parameter-row {
        align-items: center;
        background: #fff;
        border: 1px solid #e4ebf4;
        border-radius: 8px;
        display: grid;
        gap: 12px;
        grid-template-columns: minmax(160px, 220px) 1fr minmax(110px, 180px);
        margin-top: 8px;
        min-height: 102px;
        padding: 10px 12px;
    }

    .ops-series-label strong {
        color: var(--emp-navy);
        display: block;
        font-weight: 900;
    }

    .ops-series-label span {
        color: var(--emp-muted);
        font-size: 12px;
        font-weight: 700;
    }

    .ops-series-bars {
        align-items: end;
        display: flex;
        gap: 4px;
        height: 80px;
        min-width: 0;
    }

    .ops-series-value {
        color: var(--emp-ink);
        font-size: 18px;
        font-weight: 900;
        text-align: right;
    }

    .ops-status {
        align-items: center;
        border: 1px solid transparent;
        border-radius: 7px;
        display: inline-flex;
        font-size: 11px;
        font-weight: 800;
        gap: 5px;
        letter-spacing: .01em;
        min-height: 24px;
        padding: 4px 8px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .ops-status::before {
        border-radius: 999px;
        content: "";
        height: 7px;
        width: 7px;
    }

    .ops-status-success {
        background: #e9f8ee;
        border-color: #b9e6c7;
        color: var(--emp-green);
    }

    .ops-status-success::before {
        background: var(--emp-green);
    }

    .ops-status-warning {
        background: #fff7e6;
        border-color: #f2d49a;
        color: #a46100;
    }

    .ops-status-warning::before {
        background: var(--emp-gold);
    }

    .ops-status-danger {
        background: #fdecec;
        border-color: #f2b6b1;
        color: var(--emp-red);
    }

    .ops-status-danger::before {
        background: var(--emp-red);
    }

    .ops-status-muted {
        background: #eef2f6;
        border-color: #d3dce7;
        color: #526273;
    }

    .ops-status-muted::before {
        background: #7a8899;
    }

    .ops-status-info {
        background: #e9f3ff;
        border-color: #bad7fb;
        color: var(--emp-blue);
    }

    .ops-status-info::before {
        background: var(--emp-blue);
    }

    .emp-ui .btn {
        border-radius: 7px;
        font-weight: 700;
    }

    .emp-ui .btn-primary {
        background: var(--emp-navy);
        border-color: var(--emp-navy);
    }

    .emp-ui .btn-outline-primary {
        border-color: var(--emp-blue);
        color: var(--emp-blue);
    }

    .emp-ui .btn-outline-primary:hover,
    .emp-ui .btn-outline-primary.active {
        background: var(--emp-blue);
        color: #fff;
    }

    .ops-station-tabs {
        background: #fff;
        border: 1px solid var(--emp-line);
        border-radius: 8px;
        box-shadow: 0 12px 24px rgba(7, 31, 73, .06);
        display: flex;
        flex-wrap: wrap;
        gap: 0;
        margin-bottom: 16px;
        overflow: hidden;
    }

    .ops-station-tabs .nav-item {
        flex: 1 1 180px;
    }

    .ops-station-tabs .nav-link,
    .ops-station-tabs .nav-link.disabled {
        align-items: center;
        border: 0;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        color: var(--emp-navy);
        display: flex;
        font-weight: 800;
        gap: 8px;
        justify-content: center;
        min-height: 58px;
        padding: 12px;
        text-align: center;
    }

    .ops-station-tabs .nav-link.active {
        background: #f7fbff;
        border-bottom-color: var(--emp-teal);
        color: var(--emp-teal);
    }

    .ops-station-tabs .nav-link.disabled {
        color: #9aa8b8;
    }

    .emp-info-strip {
        align-items: center;
        background: #eef7ff;
        border: 1px solid #b9d9f4;
        border-radius: 8px;
        color: var(--emp-blue);
        display: flex;
        font-weight: 700;
        gap: 10px;
        margin-top: 16px;
        padding: 12px 14px;
    }

    .emp-info-strip i {
        font-size: 22px;
    }

    @media (max-width: 767.98px) {
        .emp-page-title {
            font-size: 22px;
        }

        .emp-page-hero {
            padding: 17px 18px;
        }

        .ops-table {
            min-width: 720px;
        }

        .ops-context-title,
        .ops-series-header,
        .ops-series-toolbar,
        .ops-series-sensor-head {
            align-items: stretch;
            flex-direction: column;
        }

        .ops-series-toolbar {
            flex-basis: auto;
        }

        .ops-series-parameter-row {
            grid-template-columns: 1fr;
        }

        .ops-series-value {
            text-align: left;
        }
    }
</style>
<?php /**PATH /Users/brainsoft/kerjaan/resq/resources/views/modules/platform-operations/partials/styles.blade.php ENDPATH**/ ?>