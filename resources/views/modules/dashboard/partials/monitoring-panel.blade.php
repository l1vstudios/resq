{{-- <style>
    .map-monitor-panel {
        position: absolute; z-index: 1000; top: 16px; right: 16px; bottom: 26px;
        width: clamp(360px, 32vw, 460px); display: flex; flex-direction: column; overflow: hidden;
        color: #fff;
        background: rgba(0,0,0,.6);
        -webkit-backdrop-filter: blur(2px);
        backdrop-filter: blur(2px);
        border: 1px solid rgba(255,255,255,.25); border-radius: 5px;
        box-shadow: none;
        text-shadow: none;
        font: 13px/1.5 system-ui, sans-serif;
    }
    .map-monitor-panel, .sensor-detail-dialog { --bs-heading-color: #fff; --bs-body-color: #fff; color-scheme: dark; }
    .map-monitor-panel h2, .map-monitor-panel h3, .map-monitor-panel h4 { color: inherit; }
    .map-monitor-panel[hidden], .monitor-open-button[hidden] { display: none; }
    .monitor-open-button { position: absolute; z-index: 1000; top: 16px; right: 16px; width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; padding: 0; border: 1px solid rgba(255,255,255,.3); border-radius: 5px; background: rgba(0,0,0,.75); color: #fff; box-shadow: 0 4px 18px rgba(18,38,63,.16); font-size: 23px; }
    .monitor-open-button:hover { background: rgba(0,0,0,.9); }
    .monitor-open-button:focus-visible { outline: 2px solid #556ee6; outline-offset: 3px; }
    .monitor-header { position: relative; flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px 20px; padding: 12px 16px; }
    .monitor-header-main { flex: 1 1 100%; min-width: 0; }
    .monitor-heading { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; min-height: 34px; padding-right: 88px; }
    .monitor-minimize { position: absolute; top: 12px; right: 16px; }
    .monitor-header h2 { display: inline; font-size: 15px; font-weight: 700; margin: 0 10px 0 0; }
    .monitor-project-identity { display: flex; align-items: center; flex-wrap: wrap; gap: 3px 12px; margin-top: 5px; overflow-wrap: anywhere; }
    .monitor-project-identity strong { font-size: 14px; }
    .monitor-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .monitor-actions button, .monitor-minimize { border: 1px solid rgba(255,255,255,.3); border-radius: 5px; background: rgba(0,0,0,.4); color: #fff; padding: 6px 10px; font: inherit; font-weight: 600; box-shadow: none; }
    .monitor-actions button:hover, .monitor-minimize:hover { background: rgba(0,0,0,.7); }
    .monitor-actions button:focus-visible, .monitor-minimize:focus-visible { outline: 2px solid #556ee6; outline-offset: 2px; }
    .monitor-actions .siren-button {
        color: #fff; background: #dc2626; border: 1px solid #dc2626;
        box-shadow: none;
    }
    .monitor-actions .siren-button:hover { background: #b91c1c; border-color: #b91c1c; }
    .monitor-actions .siren-active { background: #991b1b; border-color: #991b1b; }
    .monitor-actions .siren-active:hover { background: #7f1d1d; border-color: #7f1d1d; }
    .monitor-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; overflow-x: hidden; overscroll-behavior: contain; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.5) transparent; border-top: 1px solid rgba(255,255,255,.2); }
    .monitor-body[hidden] { display: none; }
    .monitor-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 12px; padding: 12px; }
    .monitor-section { min-width: 0; padding: 4px; background: transparent; border: 0; border-radius: 5px; box-shadow: none; }
    .monitor-section-title { display: flex; align-items: center; flex-wrap: wrap; justify-content: space-between; gap: 4px; margin-bottom: 8px; }
    .monitor-section-title h3 { margin: 0; font-size: 13px; font-weight: 700; }
    .monitor-muted { color: #fff; font-size: 11px; }
    .monitor-scroll { overflow: visible; max-height: none; }
    .monitor-sensor-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .monitor-sensor-card { position: relative; min-width: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 12px; min-height: 126px; padding: 16px; border-radius: 5px; background: rgba(0,0,0,.38); border: 1px solid rgba(255,255,255,.25); color: #fff; text-shadow: none; }
    .monitor-sensor-icon { font-size: 20px; color: #fff; }
    .monitor-sensor-card h4 { flex: 1; margin: 0; font-size: 14px; line-height: 1.4; font-weight: 700; overflow-wrap: anywhere; color: inherit; }
    .monitor-sensor-card .monitor-badge { max-width: 100%; white-space: normal; font-size: 12px; }
    .monitor-sensor-detail-button { position: absolute; top: 12px; right: 12px; display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; padding: 0; border: 1px solid rgba(255,255,255,.35); border-radius: 50%; background: rgba(0,0,0,.5); color: #fff; }
    .monitor-sensor-detail-button i { font-size: 23px; transform: rotate(45deg); }
    .monitor-sensor-detail-button:hover { background: rgba(0,0,0,.8); }
    .monitor-sensor-detail-button:focus-visible, .sensor-detail-close:focus-visible { outline: 2px solid #556ee6; outline-offset: 3px; }
    .sensor-detail-dialog { width: min(520px, calc(100vw - 32px)); max-height: 80vh; max-height: 80dvh; padding: 0; border: 1px solid rgba(255,255,255,.25); border-radius: 5px; background: rgba(0,0,0,.9); color: #fff; color-scheme: dark; box-shadow: 0 20px 60px rgba(18,38,63,.25); overflow-y: auto; }
    .sensor-detail-dialog::backdrop { background: rgba(18,38,63,.35); }
    .sensor-detail-header { position: sticky; top: 0; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px; background: #111; border-bottom: 1px solid rgba(255,255,255,.2); }
    .sensor-detail-header h2 { color: inherit; margin: 0; font-size: 18px; font-weight: 700; overflow-wrap: anywhere; }
    .sensor-detail-close { flex: 0 0 auto; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; padding: 0; border: 1px solid rgba(255,255,255,.25); border-radius: 50%; background: #222; color: #fff; font-size: 22px; }
    .sensor-detail-content { padding: 18px; overflow-wrap: anywhere; }
    .sensor-detail-parameters { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin: 16px 0; }
    .sensor-detail-reading { padding: 14px; background: rgba(0,0,0,.5); border: 1px solid rgba(255,255,255,.25); border-radius: 5px; }
    .sensor-detail-reading small { display: block; color: #fff; font-size: 12px; margin-bottom: 8px; }
    .sensor-detail-reading strong { display: block; font-size: 21px; line-height: 1.3; }
    .sensor-detail-meta { display: grid; grid-template-columns: 140px minmax(0, 1fr); gap: 10px 14px; margin: 16px 0 0; font-size: 13px; }
    .sensor-detail-meta dt { color: #fff; font-weight: 500; }
    .sensor-detail-meta dd { margin: 0; }
    .monitor-badge { display: inline-block; border-radius: 5px; padding: 3px 7px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .tone-normal { color: #fff; background: rgba(6,118,71,.9); }
    .tone-waspada { color: #fff; background: rgba(133,77,14,.95); }
    .tone-siaga { color: #fff; background: rgba(154,52,18,.95); }
    .tone-awas { color: #fff; background: rgba(180,35,24,.95); }
    .tone-info { color: #fff; background: rgba(24,73,169,.9); }
    .tone-unknown { color: #fff; background: rgba(71,84,103,.9); }
    .monitor-sensor-grid > .monitor-empty { grid-column: 1 / -1; }
    .monitor-empty { color: #fff; text-align: center; padding: 20px 8px; margin: 0; }
    .monitor-tde-summary { border: 1px solid rgba(255,255,255,.25); border-radius: 5px; background: rgba(0,0,0,.38); color: #fff; padding: 16px; margin-bottom: 10px; text-shadow: none; }
    .monitor-tde-summary:last-child { margin-bottom: 0; }
    .monitor-tde-summary h4 { margin: 0 0 14px; color: #fff; font-size: 11px; font-weight: 600; letter-spacing: .04em; }
    .monitor-tde-metrics { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,.8fr) minmax(0,1.4fr); gap: 10px; margin: 0; }
    .monitor-tde-metrics dt { color: #fff; font-size: 11px; font-weight: 500; margin-bottom: 6px; }
    .monitor-tde-metrics dd { color: #fff; font-size: 14px; font-weight: 700; margin: 0; line-height: 1.4; overflow-wrap: anywhere; }
    .monitor-station-details { grid-column: 1 / -1; }
    .monitor-station-details[hidden] { display: none; }
    .monitor-station-details h4 { font-size: 15px; margin: 0 0 6px; overflow-wrap: anywhere; }
    .monitor-station-details dl { display: grid; grid-template-columns: 110px minmax(0, 1fr); gap: 6px 10px; margin: 12px 0; }
    .monitor-station-details dt { color: #fff; font-weight: 500; }
    .monitor-station-details dd { margin: 0; overflow-wrap: anywhere; }
    .monitor-station-close { border: 0; border-radius: 5px; background: rgba(0,0,0,.5); color: #fff; padding: 4px 8px; }
    .monitor-station-close:focus-visible { outline: 2px solid #556ee6; outline-offset: 2px; }
    .monitor-station-sensor { padding: 8px 0; border-top: 1px solid rgba(255,255,255,.2); overflow-wrap: anywhere; }
    @media (max-width: 767px) {
        .map-monitor-panel { top: 80px; right: 8px; bottom: 24px; width: min(360px, calc(100vw - 80px)); }
        .monitor-open-button { top: 10px; right: 8px; }
        .monitor-header { padding: 8px 10px; gap: 6px; }
        .monitor-minimize { top: 8px; right: 10px; }
        .monitor-actions { gap: 5px; }
        .monitor-actions > small { flex-basis: 100%; }
        .monitor-grid { grid-template-columns: 1fr; gap: 8px; padding: 8px; }
        .monitor-section { padding: 10px; }
    }
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .map-monitor-panel { background: rgba(0,0,0,.7); }
    }
    @media (prefers-reduced-transparency: reduce) {
        .map-monitor-panel { background: #111; -webkit-backdrop-filter: none; backdrop-filter: none; }
        .monitor-sensor-card, .monitor-tde-summary { background: #080808; }
    }
</style>
@php
    $monitoringUser = auth()->user();
    $monitoringRoleLabels = $monitoringUser
        ? $monitoringUser->roles()->get()->merge($monitoringUser->projectRoles()->get())
            ->map(fn ($role) => $role->display_name ?: $role->name)->filter()->unique()->values()
        : collect();
    $monitoringRoleLabel = $monitoringRoleLabels->first()
        ?: ($monitoringUser?->isClientUser() ? 'Client' : 'Internal');
@endphp
<button type="button" id="map-monitor-open" class="monitor-open-button" aria-label="Buka sidebar monitoring" title="Buka sidebar monitoring" aria-controls="map-monitor-panel" aria-expanded="false" hidden><i class="bx bx-menu-alt-right" aria-hidden="true"></i></button>
<section id="map-monitor-panel" class="map-monitor-panel" aria-label="Informasi monitoring project">
    <div class="monitor-header">
        <div class="monitor-header-main">
            <div class="monitor-heading"><h2>Monitoring</h2><span class="monitor-badge tone-info" data-monitor="sensor-count">Memuat sensor…</span><span class="monitor-badge tone-unknown" data-account-role-badge title="Role akun: {{ $monitoringRoleLabels->implode(', ') ?: $monitoringRoleLabel }}"><i class="bx bx-shield-quarter me-1" aria-hidden="true"></i>{{ $monitoringRoleLabel }}</span></div>
            <div class="monitor-project-identity">@if($project->displayName())<strong>{{ $project->displayName() }}</strong>@endif<small class="monitor-muted">{{ $project->project_code }}</small><small class="monitor-muted">ID Monitoring Station: {{ ($projectMonitoringStations ?? collect())->implode(', ') ?: 'Belum ada monitoring station' }}</small></div>
        </div>
        <button type="button" class="monitor-minimize" data-monitor="panel-toggle" aria-expanded="true" aria-controls="map-monitor-panel">Minimize</button>
        <div class="monitor-actions">
            <small class="monitor-muted" data-monitor="siren-status" role="status">Suara di perangkat ini nonaktif</small>
            <button type="button" class="siren-button" data-monitor="siren-toggle" aria-pressed="false">Aktifkan sirene</button>
        </div>
    </div>
    <div id="map-monitor-panel-body" class="monitor-body" data-monitor="panel-body">
        <div class="monitor-grid">
            @include('modules.dashboard.partials.weather-widget', ['compact' => true])
            <section class="monitor-section monitor-station-details" data-monitor="station-details" aria-label="Detail warning station" hidden>
                <div class="monitor-section-title"><h3>Detail Warning Station</h3><button type="button" class="monitor-station-close" data-monitor="station-close" aria-label="Tutup detail warning station">Tutup</button></div>
                <div data-monitor="station-content"></div>
            </section>
            <section class="monitor-section" aria-label="Nama dan status sensor">
                <div class="monitor-section-title"><h3><i class="bx bxs-flame me-1" aria-hidden="true"></i>Sensor &amp; Status</h3><small class="monitor-muted" data-monitor="sensor-update" role="status">Menunggu pembaruan sensor</small></div>
                <div class="monitor-sensor-grid" data-monitor="sensor-rows" role="list" aria-label="Daftar sensor"><p class="monitor-empty">Memuat data sensor…</p></div>
            </section>
            <section class="monitor-section" aria-label="Hasil TDE">
                <div class="monitor-section-title"><h3><i class="bx bx-line-chart me-1" aria-hidden="true"></i>Hasil TDE</h3><small class="monitor-muted" data-monitor="tde-update" role="status">Memuat hasil TDE…</small></div>
                <div class="monitor-scroll" data-monitor="tde-results" tabindex="0" aria-label="Hasil TDE per station"><p class="monitor-empty">Memuat evaluasi station…</p></div>
            </section>
        </div>
    </div>
</section>
<dialog id="map-sensor-detail" class="sensor-detail-dialog" aria-labelledby="map-sensor-detail-title">
    <div class="sensor-detail-header">
        <h2 id="map-sensor-detail-title" data-monitor="sensor-detail-title" tabindex="-1">Detail Sensor</h2>
        <button type="button" class="sensor-detail-close" data-monitor="sensor-detail-close" aria-label="Tutup detail sensor"><i class="bx bx-x" aria-hidden="true"></i></button>
    </div>
    <div class="sensor-detail-content" data-monitor="sensor-detail-content"></div>
</dialog> --}}
