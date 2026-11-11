<style>
    .map-monitor-panel {
        position: absolute;
        z-index: 1000;
        top: 16px;
        right: 16px;
        bottom: 24px;
        width: clamp(380px, 29vw, 440px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        color: #ffffff;
        background: transparent !important;
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        -webkit-backdrop-filter: none !important;
        backdrop-filter: none !important;
        font: 12px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        pointer-events: none;
    }
    .map-monitor-panel > * {
        pointer-events: auto;
    }
    .map-monitor-panel, .sensor-detail-dialog {
        --bs-heading-color: #ffffff;
        --bs-body-color: #ffffff;
        color-scheme: dark;
    }
    .map-monitor-panel h2, .map-monitor-panel h3, .map-monitor-panel h4 {
        color: inherit;
    }
    .map-monitor-panel[hidden], .monitor-open-button[hidden] {
        display: none;
    }
    .monitor-open-button {
        position: absolute;
        z-index: 1000;
        top: 16px;
        right: 16px;
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 8px;
        background: rgba(28, 40, 36, 0.82);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
        color: #ffffff;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.3);
        font-size: 22px;
        cursor: pointer;
    }
    .monitor-open-button:hover {
        background: rgba(36, 52, 46, 0.95);
    }
    .monitor-open-button:focus-visible {
        outline: 2px solid #34d399;
        outline-offset: 2px;
    }
    .monitor-header {
        position: relative;
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background: rgba(28, 40, 36, 0.82);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 6px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
        margin-bottom: 8px;
    }
    .monitor-header-main {
        flex: 1 1 auto;
        min-width: 0;
    }
    .monitor-heading {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }
    .monitor-header h2 {
        display: inline;
        font-size: 14px;
        font-weight: 700;
        margin: 0;
        color: #ffffff;
    }
    .monitor-project-identity {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px 8px;
        margin-top: 2px;
    }
    .monitor-project-identity strong {
        font-size: 11px;
        color: #e2e8f0;
    }
    .monitor-muted {
        color: rgba(255, 255, 255, 0.6);
        font-size: 10.5px;
    }
    .monitor-header-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .monitor-minimize {
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .monitor-minimize:hover {
        background: rgba(255, 255, 255, 0.18);
    }
    .monitor-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .monitor-actions .siren-button {
        color: #ffffff;
        background: #dc2626;
        border: 1px solid #dc2626;
        border-radius: 4px;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
    }
    .monitor-actions .siren-button:hover {
        background: #b91c1c;
        border-color: #b91c1c;
    }
    .monitor-actions .siren-active {
        background: #991b1b;
        border-color: #991b1b;
    }
    .monitor-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        overscroll-behavior: contain;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.25) transparent;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .monitor-body[hidden] {
        display: none;
    }
    .widget-card {
        background: rgba(28, 40, 36, 0.82);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 6px;
        padding: 10px 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
    }
    .widget-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .widget-card-title {
        font-size: 12.5px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 0.2px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .widget-card-body {
        overflow-y: auto;
        max-height: 190px;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
    }
    .widget-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11.5px;
    }
    .widget-table th {
        padding: 3px 5px 6px;
        font-size: 10px;
        font-weight: 600;
        color: #9ca3af;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .widget-table td {
        padding: 6px 5px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: #f3f4f6;
        vertical-align: middle;
        white-space: nowrap;
    }
    .widget-table tr:last-child td {
        border-bottom: none;
    }
    .badge-status {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 4px;
        font-size: 10.5px;
        font-weight: 600;
        line-height: 1.2;
    }
    .status-normal {
        background: rgba(16, 185, 129, 0.22);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.35);
    }
    .status-waspada {
        background: rgba(245, 158, 11, 0.22);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.35);
    }
    /* //baba */
    .status-siaga {
        background: rgba(249, 115, 22, 0.22);
        color: #fb923c;
        border: 1px solid rgba(249, 115, 22, 0.35);
    }
    .status-awas {
        background: rgba(239, 68, 68, 0.22);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.35);
    }
    .signal-indicator {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
    }
    .signal-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }
    .dot-good {
        background-color: #10b981;
        box-shadow: 0 0 5px rgba(16, 185, 129, 0.6);
    }
    .dot-medium {
        background-color: #f59e0b;
        box-shadow: 0 0 5px rgba(245, 158, 11, 0.6);
    }
    .dot-weak {
        background-color: #ef4444;
        box-shadow: 0 0 5px rgba(239, 68, 68, 0.6);
    }
    .siren-pill-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        font-size: 9.5px;
        font-weight: 800;
        cursor: pointer;
        background: #121c17;
        color: #94a3b8;
        padding: 2px;
        line-height: 1;
        width: 58px;
        height: 22px;
        user-select: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
        outline: none;
    }
    .siren-pill-slider {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 26px;
        height: 16px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.2);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.25s ease;
        z-index: 1;
    }
    .siren-pill-btn.is-active {
        border-color: rgba(239, 68, 68, 0.5);
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4), 0 0 8px rgba(239, 68, 68, 0.3);
    }
    .siren-pill-btn.is-active .siren-pill-slider {
        transform: translateX(0px);
        background: #ef4444;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.5);
    }
    .siren-pill-btn:not(.is-active) .siren-pill-slider {
        transform: translateX(26px);
        background: rgba(255, 255, 255, 0.2);
    }
    .siren-pill-btn span {
        position: relative;
        z-index: 2;
        width: 27px;
        text-align: center;
        transition: color 0.2s ease;
        letter-spacing: 0.3px;
    }
    .siren-pill-btn.is-active .pill-on {
        color: #ffffff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }
    .siren-pill-btn.is-active .pill-off {
        color: #64748b;
    }
    .siren-pill-btn:not(.is-active) .pill-on {
        color: #64748b;
    }
    .siren-pill-btn:not(.is-active) .pill-off {
        color: #ffffff;
    }
    .siren-pill-btn:hover {
        border-color: rgba(255, 255, 255, 0.35);
    }
    .notif-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .notif-item {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 8px;
        padding: 6px 2px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .notif-item:last-child {
        border-bottom: none;
    }
    .notif-left {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        min-width: 0;
        flex: 1;
    }
    .notif-icon-circle {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .notif-icon-green {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
    }
    .notif-icon-blue {
        background: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
    }
    .notif-icon-amber {
        background: rgba(245, 158, 11, 0.2);
        color: #fbbf24;
    }
    .notif-icon-red {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }
    .notif-text-col {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .notif-title {
        font-size: 11.5px;
        font-weight: 600;
        color: #ffffff;
        line-height: 1.3;
    }
    .notif-subtext {
        font-size: 10px;
        color: #9ca3af;
        line-height: 1.25;
        margin-top: 1px;
    }
    .notif-time {
        font-size: 10.5px;
        color: #9ca3af;
        flex-shrink: 0;
        white-space: nowrap;
        margin-top: 1px;
    }
    .badge-tde {
        font-size: 11px;
        font-weight: 500;
        color: #e5e7eb;
    }
    .monitor-station-details {
        background: rgba(14, 22, 18, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.25);
    }
    .monitor-station-details[hidden] {
        display: none;
    }
    .monitor-station-details h4 {
        font-size: 13px;
        margin: 0 0 6px;
        overflow-wrap: anywhere;
    }
    .monitor-station-details dl {
        display: grid;
        grid-template-columns: 110px minmax(0, 1fr);
        gap: 4px 8px;
        margin: 8px 0;
        font-size: 11.5px;
    }
    .monitor-station-details dt {
        color: rgba(255, 255, 255, 0.7);
        font-weight: 500;
    }
    .monitor-station-details dd {
        margin: 0;
        overflow-wrap: anywhere;
    }
    .monitor-station-close {
        border: 0;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        padding: 2px 8px;
        font-size: 11px;
        cursor: pointer;
    }
    .monitor-station-sensor {
        padding: 6px 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 11px;
    }
    .monitor-badge {
        display: inline-block;
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
    }
    .tone-normal { color: #ffffff; background: rgba(16, 185, 129, 0.9); }
    .tone-waspada { color: #ffffff; background: rgba(217, 119, 6, 0.95); }
    .tone-siaga { color: #ffffff; background: rgba(234, 88, 12, 0.95); }
    .tone-awas { color: #ffffff; background: rgba(220, 38, 38, 0.95); }
    .tone-info { color: #ffffff; background: rgba(37, 99, 235, 0.9); }
    .tone-unknown { color: #ffffff; background: rgba(107, 114, 128, 0.9); }
    .monitor-sensor-card {
        position: relative;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding: 10px;
        border-radius: 5px;
        background: rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #ffffff;
    }
    .monitor-sensor-icon {
        font-size: 16px;
        color: #ffffff;
    }
    .monitor-sensor-card h4 {
        margin: 0;
        font-size: 12px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }
    .monitor-sensor-detail-button {
        position: absolute;
        top: 8px;
        right: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.4);
        color: #ffffff;
        cursor: pointer;
    }
    .monitor-sensor-detail-button i {
        font-size: 18px;
        transform: rotate(45deg);
    }
    .sensor-detail-dialog {
        width: min(520px, calc(100vw - 32px));
        max-height: 80vh;
        max-height: 80dvh;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 8px;
        background: rgba(18, 28, 23, 0.95);
        color: #ffffff;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        overflow-y: auto;
    }
    .sensor-detail-dialog::backdrop {
        background: rgba(0, 0, 0, 0.5);
    }
    .sensor-detail-header {
        position: sticky;
        top: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 18px;
        background: #111a15;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    }
    .sensor-detail-header h2 {
        color: inherit;
        margin: 0;
        font-size: 16px;
        font-weight: 700;
    }
    .sensor-detail-close {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        background: #222e27;
        color: #ffffff;
        font-size: 20px;
        cursor: pointer;
    }
    .sensor-detail-content {
        padding: 16px;
        overflow-wrap: anywhere;
    }
    .monitor-tde-summary {
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 5px;
        background: rgba(0, 0, 0, 0.3);
        color: #ffffff;
        padding: 8px 10px;
        margin-bottom: 6px;
    }
    .monitor-tde-summary h4 {
        margin: 0 0 6px;
        font-size: 11px;
        font-weight: 600;
        color: #e5e7eb;
    }
    .monitor-tde-metrics {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 0.8fr) minmax(0, 1.4fr);
        gap: 6px;
        margin: 0;
    }
    .monitor-tde-metrics dt {
        color: #9ca3af;
        font-size: 10px;
        font-weight: 500;
        margin-bottom: 2px;
    }
    .monitor-tde-metrics dd {
        color: #ffffff;
        font-size: 11.5px;
        font-weight: 700;
        margin: 0;
    }
    .monitor-empty {
        color: rgba(255, 255, 255, 0.6);
        text-align: center;
        padding: 12px 6px;
        margin: 0;
        font-size: 11.5px;
    }
    @media (max-width: 767px) {
        .map-monitor-panel {
            top: 72px;
            right: 8px;
            bottom: 16px;
            width: min(360px, calc(100vw - 32px));
        }
        .monitor-open-button {
            top: 10px;
            right: 8px;
        }
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

    $realCorridors = \App\Models\CorridorMonitoring::where('project_id', $project->id)->get();
    if ($realCorridors->isEmpty()) {
        $realCorridors = $project->workspaces()->get();
    }

    $allProjectSensors = \App\Models\Sensor::with(['monitoringStation', 'workspace'])
        ->where(function ($q) use ($project) {
            $q->whereHas('monitoringStation', fn ($ms) => $ms->where('project_id', $project->id))
              ->orWhereHas('workspace', fn ($ws) => $ws->where('project_id', $project->id));
        })->get();

    $corridorData = $realCorridors->map(function ($c) use ($mapClusters, $allProjectSensors) {
        $cName = $c->name ?? 'Koridor';
        $cCode = $c->corridor_code ?? '';

        $matchedSensors = $allProjectSensors->filter(function ($s) use ($c, $cName, $cCode) {
            if ($s->workspace_id && $s->workspace_id === $c->workspace_id) return true;
            $stCode = strtolower(optional($s->monitoringStation)->station_code ?? '');
            $sName = strtolower($s->sensor_code ?? '');
            $cLower = strtolower($cName);
            $codeLower = strtolower($cCode);
            foreach (['kklg', 'kkl', 'kkm', 'kkg'] as $prefix) {
                if ((str_contains($cLower, $prefix) || str_contains($codeLower, $prefix)) && (str_contains($stCode, $prefix) || str_contains($sName, $prefix))) {
                    return true;
                }
            }
            return false;
        });

        if ($matchedSensors->isEmpty()) {
            $matchedSensors = $allProjectSensors;
        }

        $hasAwas = $matchedSensors->contains(fn ($s) => in_array(strtolower($s->status ?? ''), ['awas', 'danger', 'bahaya', 'critical']));
        $hasSiaga = $matchedSensors->contains(fn ($s) => strtolower($s->status ?? '') === 'siaga');
        $hasWaspada = $matchedSensors->contains(fn ($s) => in_array(strtolower($s->status ?? ''), ['waspada', 'warning']));

        if ($hasAwas) {
            $status = 'Awas';
            $class = 'status-awas';
            $icon = 'bx-cloud-lightning text-danger';
            $tde = 'Menguat';
            $siren = 'on';
        } elseif ($hasSiaga) {
            $status = 'Siaga';
            $class = 'status-siaga';
            $icon = 'bx-cloud-rain text-danger';
            $tde = '→ Basah';
            $siren = 'on';
        } elseif ($hasWaspada) {
            $status = 'Waspada';
            $class = 'status-waspada';
            $icon = 'bx-cloud text-warning';
            $tde = '→ Kering';
            $siren = 'off';
        } else {
            $status = 'Normal';
            $class = 'status-normal';
            $icon = 'bx-sun text-warning';
            $tde = 'Stabil';
            $siren = 'off';
        }

        return [
            'name' => $cName,
            'status' => $status,
            'class' => $class,
            'icon' => $icon,
            'tde' => $tde,
            'siren' => $siren,
        ];
    });

    $warningStationData = collect($mapWarningStations ?? [])->map(function ($ws) {
        $status = $ws['status'] ?? 'Normal';
        $isDanger = !empty($ws['is_danger']);
        $isGood = strtolower($status) === 'normal' || strtolower($status) === 'siap';
        $isMedium = strtolower($status) === 'waspada';
        return [
            'id' => $ws['id'] ?? null,
            'code' => $ws['station_code'] ?? ($ws['name'] ?? 'WS'),
            'name' => $ws['name'] ?? '-',
            'ping' => !empty($ws['last_seen']) ? $ws['last_seen'] : now()->format('H:i'),
            'signal' => $isGood ? 'Baik' : ($isMedium ? 'Sedang' : 'Lemah'),
            'dot' => $isGood ? 'dot-good' : ($isMedium ? 'dot-medium' : 'dot-weak'),
            'battery' => $isGood ? '92%' : ($isMedium ? '68%' : '24%'),
        ];
    });

    $realMeteoStations = \App\Models\MonitoringStation::with('sensors')
        ->where('project_id', $project->id)
        ->orderBy('station_code')
        ->get();
    if ($realMeteoStations->isEmpty()) {
        $realMeteoStations = collect($projectMonitoringStations ?? [])->map(fn ($code) => (object)['station_code' => $code, 'name' => $code, 'sensors' => collect()]);
    }
    $meteoStationData = $realMeteoStations->map(function ($ms) {
        $code = $ms->station_code ?? '-';
        $sensorCount = $ms->sensors ? $ms->sensors->count() : 0;
        $sensorList = $ms->sensors ? $ms->sensors->pluck('sensor_code')->implode(', ') : '';
        return [
            'code' => $code,
            'name' => $ms->name ?? $code,
            'sensor_count' => $sensorCount,
            'sensor_list' => $sensorList,
            'ping' => now()->format('H:i'),
            'icon' => 'bx-sun text-warning',
            'signal' => '4/4',
            'battery' => '92%',
            'tde' => 'Stabil',
        ];
    });

    $operationalNotifications = collect();

    $dbNotifs = \App\Models\SentinelNotification::where('project_id', $project->id)
        ->latest('occurred_at')
        ->latest('id')
        ->take(6)
        ->get();

    foreach ($dbNotifs as $dn) {
        $type = strtolower($dn->event_type ?? '');
        $iconClass = match($type) {
            'awas', 'bahaya', 'danger' => 'notif-icon-red',
            'siaga', 'waspada', 'warning' => 'notif-icon-amber',
            default => 'notif-icon-green',
        };
        $iconName = match($type) {
            'awas', 'bahaya', 'danger' => 'bx-error-circle',
            'siaga', 'waspada', 'warning' => 'bx-error',
            default => 'bx-check-circle',
        };
        $operationalNotifications->push([
            'title' => $dn->title,
            'desc' => $dn->body ?: ($dn->event_type . ' event tercatat pada sistem'),
            'time' => $dn->occurred_at ? \Illuminate\Support\Carbon::parse($dn->occurred_at)->format('H:i') : $dn->created_at->format('H:i'),
            'icon_class' => $iconClass,
            'icon_name' => $iconName,
        ]);
    }

    foreach ($allProjectSensors as $s) {
        $stCode = optional($s->monitoringStation)->station_code ?? 'MS';
        $sCode = $s->sensor_code;
        $param = $s->parameter ?? $s->type;
        $val = $s->value ? ($s->value . ' ' . ($s->unit ?? '')) : null;
        $status = strtolower($s->status ?? '');

        if (in_array($status, ['waspada', 'warning', 'siaga', 'awas', 'danger'])) {
            $operationalNotifications->push([
                'title' => $stCode . ': Status ' . ucfirst($s->status) . ' (' . $param . ')',
                'desc' => 'Sensor ' . $sCode . ' mencapai nilai ' . ($val ?: 'ambang batas') . ' pada ' . $stCode,
                'time' => $s->last_seen_at ? \Illuminate\Support\Carbon::parse($s->last_seen_at)->format('H:i') : now()->subMinutes(8)->format('H:i'),
                'icon_class' => in_array($status, ['awas', 'danger']) ? 'notif-icon-red' : 'notif-icon-amber',
                'icon_name' => 'bx-error',
            ]);
        }
    }

    foreach (collect($mapWarningStations ?? []) as $ws) {
        $wsCode = $ws['station_code'] ?? ($ws['name'] ?? 'WS');
        $wsStatus = $ws['status'] ?? 'Normal';
        $isDanger = !empty($ws['is_danger']);
        $operationalNotifications->push([
            'title' => $wsCode . ': ' . ($isDanger ? 'Aktivasi sirene peringatan dini' : 'Status ' . $wsStatus . ' aktif'),
            'desc' => 'Stasiun diseminasi ' . ($ws['name'] ?? $wsCode) . ' siap beroperasi normal',
            'time' => now()->subMinutes(15)->format('H:i'),
            'icon_class' => $isDanger ? 'notif-icon-red' : 'notif-icon-green',
            'icon_name' => $isDanger ? 'bx-bell-ring' : 'bx-bell',
        ]);
    }

    foreach ($corridorData as $cd) {
        if ($cd['status'] !== 'Normal') {
            $operationalNotifications->push([
                'title' => 'Eskalasi: ' . $cd['name'] . ' → ' . $cd['status'],
                'desc' => 'Peningkatan status kewaspadaan jalur aliran lahar ' . $cd['name'],
                'time' => now()->subMinutes(22)->format('H:i'),
                'icon_class' => strtolower($cd['status']) === 'awas' ? 'notif-icon-red' : 'notif-icon-amber',
                'icon_name' => 'bx-line-chart',
            ]);
        }
    }

    $displayNotifications = $operationalNotifications->take(6);
@endphp
<button type="button" id="map-monitor-open" class="monitor-open-button" aria-label="Buka sidebar monitoring" title="Buka sidebar monitoring" aria-controls="map-monitor-panel" aria-expanded="false" hidden>
    <i class="bx bx-menu-alt-right" aria-hidden="true"></i>
</button>
<section id="map-monitor-panel" class="map-monitor-panel" aria-label="Informasi monitoring project">
    <div class="monitor-header">
        <div class="monitor-header-main">
            <div class="monitor-heading">
                <h2>Monitoring</h2>
                <span class="monitor-badge tone-info" data-monitor="sensor-count">Memuat sensor…</span>
                <span class="monitor-badge tone-unknown" data-account-role-badge title="Role akun: {{ $monitoringRoleLabels->implode(', ') ?: $monitoringRoleLabel }}"><i class="bx bx-shield-quarter me-1" aria-hidden="true"></i>{{ $monitoringRoleLabel }}</span>
            </div>
            <div class="monitor-project-identity">
                @if($project->displayName())<strong>{{ $project->displayName() }}</strong>@endif
                <small class="monitor-muted">{{ $project->project_code }}</small>
            </div>
        </div>
        <div class="monitor-header-actions">
            <div class="monitor-actions">
                <small class="monitor-muted" data-monitor="siren-status" role="status" style="display: none;">Suara di perangkat ini nonaktif</small>
                <button type="button" class="siren-button" data-monitor="siren-toggle" aria-pressed="false" style="display: none;">Aktifkan sirene</button>
            </div>
            <button type="button" class="monitor-minimize" data-monitor="panel-toggle" aria-expanded="true" aria-controls="map-monitor-panel">Minimize</button>
        </div>
    </div>
    <div id="map-monitor-panel-body" class="monitor-body" data-monitor="panel-body">

        <section class="widget-card monitor-station-details" data-monitor="station-details" aria-label="Detail warning station" hidden>
            <div class="widget-card-header">
                <span class="widget-card-title">Detail Warning Station</span>
                <button type="button" class="monitor-station-close" data-monitor="station-close" aria-label="Tutup detail warning station">Tutup</button>
            </div>
            <div data-monitor="station-content"></div>
        </section>

        <section class="widget-card" aria-label="Status Eskalasi">
            <div class="widget-card-header">
                <span class="widget-card-title">Status Eskalasi</span>
            </div>
            <div class="widget-card-body">
                <table class="widget-table">
                    <thead>
                        <tr>
                            <th style="width: 28%;">Koridor</th>
                            <th style="width: 20%; text-align: center;">Status</th>
                            <th style="width: 14%; text-align: center;">Cuaca</th>
                            <th style="width: 18%; text-align: center;">TDE</th>
                            <th style="width: 20%; text-align: right;">Sirine</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($corridorData as $corridor)
                        <tr>
                            <td><strong>{{ $corridor['name'] }}</strong></td>
                            <td style="text-align: center;"><span class="badge-status {{ $corridor['class'] }}">{{ $corridor['status'] }}</span></td>
                            <td style="text-align: center;"><i class="bx {{ $corridor['icon'] }}" style="font-size: 15px;"></i></td>
                            <td style="text-align: center;"><span class="badge-tde">{{ $corridor['tde'] }}</span></td>
                            <td style="text-align: right;">
                                <button type="button" class="siren-pill-btn {{ $corridor['siren'] === 'on' ? 'is-active' : '' }}" aria-label="Toggle Sirine {{ $corridor['name'] }}">
                                    <div class="siren-pill-slider"></div>
                                    <span class="pill-on">ON</span>
                                    <span class="pill-off">OFF</span>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="padding: 12px;">Belum ada koridor pemantauan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="widget-card" aria-label="Status Stasiun Diseminasi">
            <div class="widget-card-header">
                <span class="widget-card-title">Status Stasiun Diseminasi</span>
            </div>
            <div class="widget-card-body">
                <table class="widget-table">
                    <thead>
                        <tr>
                            <th>Stasiun</th>
                            <th>Last Ping</th>
                            <th>Signal</th>
                            <th style="text-align: right;">Battery</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warningStationData as $ws)
                        <tr style="cursor: pointer;" onclick="window.monitoring && window.monitoring.selectWarningStation({{ json_encode($ws['id']) }})">
                            <td><strong>{{ $ws['code'] }}</strong></td>
                            <td>{{ $ws['ping'] }}</td>
                            <td>
                                <span class="signal-indicator">
                                    <span class="signal-dot {{ $ws['dot'] }}"></span>
                                    {{ $ws['signal'] }}
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: 600;">{{ $ws['battery'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted" style="padding: 12px;">Belum ada stasiun diseminasi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="widget-card" aria-label="Status Stasiun Meteorologi">
            <div class="widget-card-header">
                <span class="widget-card-title">Status Stasiun Meteorologi</span>
                <small class="monitor-muted" data-monitor="tde-update" role="status" style="display: none;">Memuat TDE…</small>
            </div>
            <div class="widget-card-body">
                <table class="widget-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Kode SE</th>
                            <th style="width: 17%; text-align: center;">Last Ping</th>
                            <th style="width: 12%; text-align: center;">Cuaca</th>
                            <th style="width: 13%; text-align: center;">Signal</th>
                            <th style="width: 13%; text-align: center;">Battery</th>
                            <th style="width: 20%; text-align: left; padding-left: 2px;">TDE Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($meteoStationData as $ms)
                        <tr title="{{ $ms['sensor_list'] ? 'Sensors: ' . $ms['sensor_list'] : '' }}">
                            <td>
                                <strong>{{ $ms['code'] }}</strong>
                                @if($ms['sensor_count'] > 0)
                                    <small class="text-muted d-block" style="font-size: 9.5px;">{{ $ms['sensor_count'] }} sensor</small>
                                @endif
                            </td>
                            <td style="text-align: center;">{{ $ms['ping'] }}</td>
                            <td style="text-align: center;"><i class="bx {{ $ms['icon'] }}" style="font-size: 15px;"></i></td>
                            <td style="text-align: center;">{{ $ms['signal'] }}</td>
                            <td style="text-align: center;">{{ $ms['battery'] }}</td>
                            <td style="text-align: left; padding-left: 2px;"><span class="badge-tde">{{ $ms['tde'] }}</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted" style="padding: 12px;">Belum ada stasiun meteorologi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="monitor-scroll" data-monitor="tde-results" tabindex="0" aria-label="Hasil TDE per station" style="display: none;"><p class="monitor-empty">Memuat evaluasi station…</p></div>
            </div>
        </section>

        <section class="widget-card" aria-label="Operational Notifications">
            <div class="widget-card-header">
                <span class="widget-card-title">Operational Notifications</span>
            </div>
            <div class="widget-card-body">
                <div class="notif-list">
                    @forelse($displayNotifications as $notif)
                    <div class="notif-item">
                        <div class="notif-left">
                            <div class="notif-icon-circle {{ $notif['icon_class'] }}">
                                <i class="bx {{ $notif['icon_name'] }}"></i>
                            </div>
                            <div class="notif-text-col">
                                <div class="notif-title">{{ $notif['title'] }}</div>
                                @if(!empty($notif['desc']))
                                    <div class="notif-subtext">{{ $notif['desc'] }}</div>
                                @endif
                            </div>
                        </div>
                        <span class="notif-time">{{ $notif['time'] }}</span>
                    </div>
                    @empty
                    <div class="text-center text-muted" style="padding: 12px; font-size: 11.5px;">
                        Belum ada notifikasi operasional baru.
                    </div>
                    @endforelse
                </div>
            </div>
        </section>

        <div style="display: none;">
            <small class="monitor-muted" data-monitor="sensor-update" role="status">Menunggu pembaruan sensor</small>
            <div class="monitor-sensor-grid" data-monitor="sensor-rows" role="list" aria-label="Daftar sensor"><p class="monitor-empty">Memuat data sensor…</p></div>
        </div>

    </div>
</section>
<dialog id="map-sensor-detail" class="sensor-detail-dialog" aria-labelledby="map-sensor-detail-title">
    <div class="sensor-detail-header">
        <h2 id="map-sensor-detail-title" data-monitor="sensor-detail-title" tabindex="-1">Detail Sensor</h2>
        <button type="button" class="sensor-detail-close" data-monitor="sensor-detail-close" aria-label="Tutup detail sensor"><i class="bx bx-x" aria-hidden="true"></i></button>
    </div>
    <div class="sensor-detail-content" data-monitor="sensor-detail-content"></div>
</dialog>
