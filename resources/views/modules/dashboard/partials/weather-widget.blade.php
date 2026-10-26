@php $weatherCompact = $compact ?? false; @endphp
<style>
    #dashboard-weather-widget { border: 0; border-radius: 5px; box-shadow: none; background: transparent; }
    #dashboard-weather-location, #dashboard-weather-status { color: #000; }
    .weather-forecast-viewport { container-type: inline-size; width: 100%; overflow-x: auto; overflow-y: hidden; border-radius: 5px; overscroll-behavior-x: contain; scroll-snap-type: x mandatory; scrollbar-width: none; -ms-overflow-style: none; -webkit-overflow-scrolling: touch; }
    .weather-forecast-viewport::-webkit-scrollbar { display: none; width: 0; height: 0; }
    .weather-forecast-viewport:focus-visible { outline: 2px solid #556ee6; outline-offset: 3px; }
    .weather-forecast-track { position: relative; width: 350%; }
    @container (max-width: 340px) {
        .weather-forecast-track { width: 700%; }
    }
    /* Seven equal columns follow the provider's Forecast layout with labels hidden. */
    .weather-forecast-dividers { position: absolute; inset: 0; z-index: 1; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); pointer-events: none; }
    .weather-forecast-dividers > span { position: relative; scroll-snap-align: start; }
    .weather-forecast-navigation { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
    .weather-forecast-navigation[hidden], .weather-forecast-viewport[hidden] { display: none; }
    .weather-forecast-arrows { display: flex; gap: 6px; }
    .weather-forecast-arrows button { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: 1px solid rgba(82,97,119,.3); border-radius: 5px; background: rgba(255,255,255,.3); color: #24334a; font-size: 20px; }
    .weather-forecast-arrows button:hover { background: rgba(255,255,255,.65); }
    .weather-forecast-arrows button:focus-visible { outline: 2px solid #556ee6; outline-offset: 2px; }
    .monitor-weather-widget { min-width: 0; padding: 4px; }
    .monitor-weather-widget #dashboard-weather-location, .monitor-weather-widget #dashboard-weather-status,
    .monitor-weather-widget .form-label, .monitor-weather-widget .weather-widget-title,
    .monitor-weather-widget .weatherwidget-io { color: #fff; }
    .monitor-weather-widget .weather-forecast-viewport { border: 0; box-shadow: none; background: rgba(0,0,0,.38); }
    .monitor-weather-widget .weatherwidget-io,
    .monitor-weather-widget .weatherwidget-io-frame { border: 0; box-shadow: none; background: transparent; }
    /* Match the provider's scheme so the browser keeps its iframe canvas transparent. */
    .monitor-weather-widget .weatherwidget-io-frame { color-scheme: light; }
    .monitor-weather-widget .weather-forecast-arrows button, .monitor-weather-widget .btn-outline-primary { border-color: rgba(255,255,255,.35); background: rgba(0,0,0,.4); color: #fff; }
    .monitor-weather-widget .weather-forecast-arrows button:hover, .monitor-weather-widget .btn-outline-primary:hover { border-color: rgba(255,255,255,.6); background: rgba(0,0,0,.7); color: #fff; }
    .monitor-weather-widget .btn-outline-primary:disabled { color: #fff; }
    .monitor-weather-widget .form-select { color-scheme: dark; color: #fff; background-color: rgba(0,0,0,.7); border-color: rgba(255,255,255,.35); background-image: none; appearance: auto; }
    .monitor-weather-widget .form-select option { color: #fff; background: #111; }
    .monitor-weather-widget .weather-widget-title { font-size: 13px; font-weight: 700; margin-bottom: 3px; }
    .monitor-weather-widget .weather-widget-note { font-size: 11px; }
    .monitor-weather-widget .weather-location-settings { font-size: 11px; margin: 8px 0; }
    .monitor-weather-widget .weather-location-settings summary { cursor: pointer; font-weight: 600; }
    .monitor-weather-widget .weather-location-settings > div { padding-top: 8px; }
</style>
<section class="{{ $weatherCompact ? 'monitor-weather-widget' : 'card' }}" id="dashboard-weather-widget" aria-label="Cuaca Indonesia">
    <div class="{{ $weatherCompact ? 'weather-widget-body' : 'card-body' }}">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-2">
            <div>
                <h4 class="{{ $weatherCompact ? 'weather-widget-title' : 'card-title mb-1' }}"><i class="bx bx-cloud me-1" aria-hidden="true"></i>Prakiraan Cuaca</h4>
                <p class="small mb-0" id="dashboard-weather-location">Lokasi project</p>
            </div>
            @if ($weatherCompact)<details class="weather-location-settings"><summary>Lokasi cuaca</summary>@endif
            <div class="d-flex flex-wrap align-items-end gap-2">
                <div>
                    <label for="dashboard-weather-city" class="form-label small mb-1">Kota prakiraan</label>
                    <select id="dashboard-weather-city" class="form-select form-select-sm" disabled><option value="">Pilih kota</option></select>
                </div>
                <button type="button" id="dashboard-weather-project-location" class="btn btn-sm btn-outline-primary"><i class="bx bx-map-pin me-1" aria-hidden="true"></i>Gunakan lokasi project</button>
            </div>
            @if ($weatherCompact)</details>@endif
        </div>
        <p class="small weather-widget-note" id="dashboard-weather-status" role="status">Menentukan prakiraan berdasarkan lokasi project.</p>
        <div class="weather-forecast-navigation" id="dashboard-weather-navigation" hidden>
            <span class="small fw-semibold">Hari ini · 7 hari <small class="fw-normal">— geser ke samping</small></span>
            <div class="weather-forecast-arrows">
                <button type="button" id="dashboard-weather-previous" aria-label="Geser prakiraan ke hari sebelumnya" aria-controls="dashboard-weather-viewport"><i class="bx bx-chevron-left" aria-hidden="true"></i></button>
                <button type="button" id="dashboard-weather-next" aria-label="Geser prakiraan ke hari berikutnya" aria-controls="dashboard-weather-viewport"><i class="bx bx-chevron-right" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="weather-forecast-viewport" id="dashboard-weather-viewport" tabindex="0" role="region" aria-label="Prakiraan tujuh hari mulai hari ini, geser ke samping" hidden>
            <div class="weather-forecast-track" id="dashboard-weather-embed">
                <a class="weatherwidget-io" data-label_1="" data-label_2="" data-theme="{{ $weatherCompact ? 'pure' : 'hexellence' }}" data-mode="Forecast" data-days="7" @if ($weatherCompact) data-basecolor="rgba(0,0,0,0)" data-accent="rgba(0,0,0,0)" data-textcolor="#ffffff" data-highcolor="#ffffff" data-lowcolor="#ffffff" data-cloudcolor="#ffffff" data-cloudfill="#ffffff" @endif>Prakiraan tujuh hari</a>
                <div class="weather-forecast-dividers" aria-hidden="true">
                    @for ($day = 0; $day < 7; $day++)
                        <span></span>
                    @endfor
                </div>
            </div>
        </div>
        <noscript><p class="small mt-2 mb-0">Aktifkan JavaScript untuk menampilkan prakiraan cuaca project.</p></noscript>
    </div>
</section>
