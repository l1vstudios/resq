@php
    $context = $context ?? [];
    $capabilities = collect($context['capabilities'] ?? []);
    $readingMethods = $context['reading_methods'] ?? [];
    $referenceRoutes = collect($context['reference_routes'] ?? []);
    $referencePoints = collect($context['reference_points'] ?? []);
@endphp

<div class="row">
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Station</p><h4>{{ $station->station_code }}</h4><div class="ops-context-line">{{ $station->name }}</div></div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Project</p><h4>{{ $context['station']['project_code'] ?? '-' }}</h4><div class="ops-context-line">{{ $context['station']['workspace_code'] ?? '-' }}</div></div></div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card ops-kpi"><div class="card-body"><p class="text-muted">Configurable Functions</p><h4>{{ $capabilities->where('available', true)->count() }}</h4><div class="ops-context-line">Derived from station instrumentation</div></div></div>
    </div>
</div>

<div class="row">
    @foreach($capabilities as $capability)
        @continue(! ($capability['available'] ?? false))
        @php
            $function = $capability['function'];
            $config = $capability['configuration']['configuration'] ?? [];
            $saved = $capability['configuration'] ?? null;
        @endphp
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">{{ $function }}</h4>
                            <div class="text-muted small">{{ $capability['basis'] }}</div>
                        </div>
                        @if($saved)
                            @include('modules.platform-operations.partials.status-badge', ['status' => $saved['status']])
                        @endif
                    </div>

                    <form method="POST" action="{{ route('client-operations.function-configuration.store', [$station, $function]) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reading Method</label>
                                <select name="reading_method" class="form-select" required>
                                    @foreach($readingMethods as $method)
                                        <option value="{{ $method }}" @selected(($saved['reading_method'] ?? 'Absolute') === $method)>{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    @foreach(['draft', 'active', 'inactive'] as $status)
                                        <option value="{{ $status }}" @selected(($saved['status'] ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if($function === 'TDE')
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Data Window</label>
                                    <input name="data_window_value" type="number" min="1" max="10080" class="form-control" value="{{ $config['data_window']['value'] ?? 60 }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Window Unit</label>
                                    <select name="data_window_unit" class="form-select" required>
                                        @foreach(['minutes', 'hours', 'days'] as $unit)
                                            <option value="{{ $unit }}" @selected(($config['data_window']['unit'] ?? 'minutes') === $unit)>{{ ucfirst($unit) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @elseif($function === 'Discharge')
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cross-sectional Area</label>
                                    <input name="cross_sectional_area" type="number" min="0" step="0.0001" class="form-control" value="{{ $config['cross_sectional_area'] ?? '' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Manning's n</label>
                                    <input name="manning_n" type="number" min="0" step="0.0001" class="form-control" value="{{ $config['manning_n'] ?? '' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Coefficient Cd</label>
                                    <input name="coefficient_cd" type="number" min="0" step="0.0001" class="form-control" value="{{ $config['coefficient_cd'] ?? '' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Unit</label>
                                    <input name="unit" type="text" maxlength="30" class="form-control" value="{{ $config['unit'] ?? 'm3/s' }}" required>
                                </div>
                            </div>
                        @elseif($function === 'CFPE')
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reference Route</label>
                                    <select name="reference_route_id" class="form-select" required>
                                        <option value="">Select route</option>
                                        @foreach($referenceRoutes as $route)
                                            <option value="{{ $route['id'] }}" @selected((int) ($config['reference_route_id'] ?? 0) === (int) $route['id'])>{{ $route['route_code'] }} - {{ $route['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reference BM</label>
                                    <select name="reference_bm_id" class="form-select">
                                        <option value="">None</option>
                                        @foreach($referencePoints as $point)
                                            <option value="{{ $point['id'] }}" @selected((int) ($config['reference_bm_id'] ?? 0) === (int) $point['id'])>{{ $point['point_code'] }} - {{ $point['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Offset Direction</label>
                                    <select name="offset_direction" class="form-select" required>
                                        @foreach(['left', 'right', 'upstream', 'downstream', 'centerline'] as $direction)
                                            <option value="{{ $direction }}" @selected(($config['offset_direction'] ?? 'centerline') === $direction)>{{ \Illuminate\Support\Str::headline($direction) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Offset Distance</label>
                                    <input name="offset_distance" type="number" min="0" step="0.0001" class="form-control" value="{{ $config['offset_distance'] ?? '' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Station Ground Zero / Chainage</label>
                                    <input name="station_ground_zero_chainage" type="number" min="0" step="0.0001" class="form-control" value="{{ $config['station_ground_zero_chainage'] ?? '' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Uncertainty Factor</label>
                                    <input name="uncertainty_factor" type="number" min="0" step="0.0001" class="form-control" value="{{ $config['uncertainty_factor'] ?? '' }}">
                                </div>
                            </div>
                            <div class="d-flex gap-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="validate" value="1" id="validate-{{ $function }}">
                                    <label class="form-check-label" for="validate-{{ $function }}">Validate</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="activate" value="1" id="activate-{{ $function }}">
                                    <label class="form-check-label" for="activate-{{ $function }}">Activate</label>
                                </div>
                            </div>
                        @endif

                        <div class="border-top pt-3 mt-1">
                            <div class="text-muted small mb-2">{{ collect($capability['unresolved_rules'] ?? [])->implode(' ') }}</div>
                            <button type="submit" class="btn btn-primary">Save {{ $function }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($capabilities->where('available', true)->isEmpty())
    <div class="card"><div class="card-body text-center text-muted">No function configuration is available for this station instrumentation.</div></div>
@endif
