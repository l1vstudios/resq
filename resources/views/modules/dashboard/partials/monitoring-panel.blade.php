<style>
    .map-monitor-panel { position: absolute; z-index: 1000; left: 16px; right: 16px; bottom: 26px; color: #24334a; background: rgba(255,255,255,.97); border: 1px solid #dde5ee; border-radius: 14px; box-shadow: 0 8px 32px rgba(18,38,63,.18); font: 13px/1.5 system-ui, sans-serif; }
    .monitor-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px 20px; padding: 12px 16px; }
    .monitor-header h2 { display: inline; font-size: 15px; font-weight: 700; margin: 0 10px 0 0; }
    .monitor-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .monitor-actions button { border: 1px solid #d5deea; border-radius: 7px; background: #fff; color: #25334b; padding: 6px 10px; font: inherit; font-weight: 600; }
    .monitor-actions button:focus-visible { outline: 2px solid #556ee6; outline-offset: 2px; }
    .monitor-actions .siren-button { color: #b42318; border-color: #f2b7b2; background: #fff5f4; }
    .monitor-actions .siren-active { color: #fff; background: #c9362b; border-color: #c9362b; }
    .monitor-body { border-top: 1px solid #e7ecf2; }
    .monitor-body[hidden] { display: none; }
    .monitor-grid { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); }
    .monitor-section { min-width: 0; padding: 12px 16px; }
    .monitor-section + .monitor-section { border-left: 1px solid #e7ecf2; }
    .monitor-section-title { display: flex; align-items: center; flex-wrap: wrap; justify-content: space-between; gap: 4px; margin-bottom: 8px; }
    .monitor-section-title h3 { margin: 0; font-size: 13px; font-weight: 700; }
    .monitor-muted { color: #68768a; font-size: 11px; }
    .monitor-scroll { overflow: auto; max-height: min(27vh, 245px); overscroll-behavior: contain; }
    .monitor-sensors { width: 100%; border-collapse: collapse; }
    .monitor-sensors th { position: sticky; top: 0; background: #f6f8fb; color: #68768a; font-size: 11px; font-weight: 600; text-align: left; }
    .monitor-sensors td, .monitor-sensors th { padding: 8px; border-bottom: 1px solid #edf0f4; vertical-align: top; }
    .monitor-sensors small, .monitor-tde-card > small { display: block; color: #68768a; font-size: 11px; margin-top: 3px; }
    .monitor-value { font-size: 17px; white-space: nowrap; }
    .monitor-badge { display: inline-block; border-radius: 5px; padding: 3px 7px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .tone-normal { color: #067647; background: #dcfae6; }
    .tone-waspada { color: #854d0e; background: #fef3c7; }
    .tone-siaga { color: #9a3412; background: #ffedd5; }
    .tone-awas { color: #b42318; background: #fee4e2; }
    .tone-info { color: #1849a9; background: #eaf2ff; }
    .tone-unknown { color: #475467; background: #eaecf0; }
    .monitor-empty { color: #68768a; text-align: center; padding: 20px 8px; margin: 0; }
    .monitor-tde-card { border: 1px solid #e6ebf1; border-radius: 8px; padding: 10px; margin-bottom: 8px; }
    .monitor-tde-heading { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 5px; }
    .monitor-tde-card h3 { font-size: 15px; font-weight: 700; margin: 8px 0 4px; }
    .monitor-tde-card p { font-size: 12px; margin: 0 0 8px; }
    .monitor-tde-card dl { display: flex; flex-wrap: wrap; gap: 8px 20px; margin: 8px 0 0; }
    .monitor-tde-card dt { font-size: 10px; color: #68768a; font-weight: 500; }
    .monitor-tde-card dd { font-size: 12px; margin: 0; font-weight: 600; }
    @media (max-width: 767px) {
        .map-monitor-panel { left: 8px; right: 8px; bottom: 24px; }
        .monitor-header { padding: 8px 10px; gap: 6px; }
        .monitor-actions { gap: 5px; }
        .monitor-actions > small { flex-basis: 100%; }
        .monitor-body { max-height: 35vh; overflow: auto; overscroll-behavior: contain; }
        .monitor-grid { grid-template-columns: 1fr; }
        .monitor-section { padding: 10px; }
        .monitor-section + .monitor-section { border-left: 0; border-top: 1px solid #e7ecf2; }
        .monitor-scroll { max-height: 23vh; }
    }
</style>
<section id="map-monitor-panel" class="map-monitor-panel" aria-label="Informasi monitoring project">
    <div class="monitor-header">
        <div><h2>Monitoring Project</h2><span class="monitor-badge tone-info" data-monitor="sensor-count">Memuat sensor…</span></div>
        <div class="monitor-actions">
            <small class="monitor-muted" data-monitor="siren-status" role="status">Suara di perangkat ini nonaktif</small>
            <button type="button" class="siren-button" data-monitor="siren-toggle" aria-pressed="false">Aktifkan sirene</button>
            <button type="button" data-monitor="panel-toggle" aria-expanded="true" aria-controls="map-monitor-panel-body">Ciutkan</button>
        </div>
    </div>
    <div id="map-monitor-panel-body" class="monitor-body" data-monitor="panel-body">
        <div class="monitor-grid">
            <section class="monitor-section" aria-label="Nilai dan status sensor">
                <div class="monitor-section-title"><h3>Sensor &amp; Status</h3><small class="monitor-muted" data-monitor="sensor-update" role="status">Menunggu pembaruan sensor</small></div>
                <div class="monitor-scroll" tabindex="0" aria-label="Daftar sensor">
                    <table class="monitor-sensors"><thead><tr><th scope="col">Sensor / Station</th><th scope="col">Nilai Terakhir</th><th scope="col">Status</th></tr></thead><tbody data-monitor="sensor-rows"><tr><td colspan="3" class="monitor-empty">Memuat data sensor…</td></tr></tbody></table>
                </div>
            </section>
            <section class="monitor-section" aria-label="Hasil TDE">
                <div class="monitor-section-title"><h3>Hasil TDE</h3><small class="monitor-muted" data-monitor="tde-update" role="status">Memuat hasil TDE…</small></div>
                <div class="monitor-scroll" data-monitor="tde-results" tabindex="0" aria-label="Hasil TDE per station"><p class="monitor-empty">Memuat evaluasi station…</p></div>
            </section>
        </div>
    </div>
</section>
