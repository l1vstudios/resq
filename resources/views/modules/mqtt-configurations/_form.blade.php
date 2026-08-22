@php
    $mqttFormId = $mqttFormId ?? 'mqtt-configuration-form';
    $mqttProjects = collect($mqttProjects ?? $projects ?? []);
    $mqttCanonicalParameters = collect($mqttCanonicalParameters ?? $canonicalParameters ?? []);
@endphp

<form method="POST" action="{{ route('mqtt-configurations.store') }}" id="{{ $mqttFormId }}">
    @csrf
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Project</label><select name="project_id" class="form-select" required>@foreach($mqttProjects as $project)<option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->project_code }} - {{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-6 mb-3"><label class="form-label">Configuration Code</label><input name="configuration_code" class="form-control" value="{{ old('configuration_code') }}" placeholder="MQTT-PROJECT-01" required></div>
    </div>
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
    <div class="mb-3"><label class="form-label">Broker URL</label><input name="broker_url" class="form-control" value="{{ old('broker_url') }}" placeholder="mqtts://broker.example.com:8883" required></div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Username</label><input name="username" class="form-control" value="{{ old('username') }}"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" placeholder="Kosong = pertahankan password"></div>
    </div>

    <div class="border rounded p-3 mb-3">
        <div class="form-check form-switch mb-3"><input type="hidden" name="consumer_enabled" value="0"><input class="form-check-input" type="checkbox" name="consumer_enabled" value="1" id="{{ $mqttFormId }}-consumer-enabled" @checked(old('consumer_enabled'))><label class="form-check-label fw-bold" for="{{ $mqttFormId }}-consumer-enabled">Consumer</label></div>
        <div class="row">
            <div class="col-md-9 mb-3"><label class="form-label">Input Topic</label><input name="consumer_topic" class="form-control" value="{{ old('consumer_topic') }}" placeholder="project/telemetry/#"></div>
            <div class="col-md-3 mb-3"><label class="form-label">QoS</label><select name="consumer_qos" class="form-select">@foreach([0, 1, 2] as $qos)<option value="{{ $qos }}" @selected((string) old('consumer_qos', 0) === (string) $qos)>{{ $qos }}</option>@endforeach</select></div>
        </div>
        <div class="mb-3"><label class="form-label">Example Output (JSON)</label><textarea name="example_payload" rows="5" class="form-control font-monospace" placeholder='{"sensor_code":"SNS-01","data":{"temperature":28.5}}'>{{ old('example_payload') }}</textarea></div>
        <div><label class="form-label">Sensor Code JSON Path</label><input name="sensor_code_path" class="form-control" value="{{ old('sensor_code_path', 'sensor_code') }}" placeholder="device.sensor_code"></div>
    </div>

    <div class="border rounded p-3 mb-3">
        <div class="form-check form-switch mb-3"><input type="hidden" name="producer_enabled" value="0"><input class="form-check-input" type="checkbox" name="producer_enabled" value="1" id="{{ $mqttFormId }}-producer-enabled" @checked(old('producer_enabled'))><label class="form-check-label fw-bold" for="{{ $mqttFormId }}-producer-enabled">Producer</label></div>
        <div class="row">
            <div class="col-md-8 mb-3"><label class="form-label">Output Topic</label><input name="producer_topic" class="form-control" value="{{ old('producer_topic') }}" placeholder="project/output"></div>
            <div class="col-md-2 mb-3"><label class="form-label">QoS</label><select name="producer_qos" class="form-select">@foreach([0, 1, 2] as $qos)<option value="{{ $qos }}" @selected((string) old('producer_qos', 0) === (string) $qos)>{{ $qos }}</option>@endforeach</select></div>
            <div class="col-md-2 mb-3"><label class="form-label">Retain</label><div class="form-check mt-2"><input type="hidden" name="producer_retain" value="0"><input type="checkbox" name="producer_retain" value="1" class="form-check-input" @checked(old('producer_retain'))></div></div>
        </div>
        <div class="row">
            <div class="col-md-6"><div class="form-check mb-2"><input type="hidden" name="publish_canonical" value="0"><input class="form-check-input" type="checkbox" name="publish_canonical" value="1" id="{{ $mqttFormId }}-publish-canonical" @checked(old('publish_canonical'))><label class="form-check-label" for="{{ $mqttFormId }}-publish-canonical">Publish canonical</label></div></div>
            <div class="col-md-6"><div class="form-check mb-2"><input type="hidden" name="publish_warning" value="0"><input class="form-check-input" type="checkbox" name="publish_warning" value="1" id="{{ $mqttFormId }}-publish-warning" @checked(old('publish_warning'))><label class="form-check-label" for="{{ $mqttFormId }}-publish-warning">Publish warning transition</label></div></div>
        </div>
        <div class="mb-3"><label class="form-label">Canonical Parameter Filter</label><select name="canonical_parameter_ids[]" class="form-select" multiple>@foreach($mqttCanonicalParameters as $parameter)<option value="{{ $parameter->id }}" @selected(in_array((string) $parameter->id, array_map('strval', old('canonical_parameter_ids', [])), true))>{{ $parameter->field_identity }}</option>@endforeach</select><small class="text-muted">Kosong berarti semua parameter.</small></div>
        <div class="mb-3"><label class="form-label">Warning Level Filter</label><select name="warning_levels[]" class="form-select" multiple>@foreach(['Normal','Waspada','Siaga','Awas'] as $level)<option value="{{ $level }}" @selected(in_array($level, old('warning_levels', []), true))>{{ $level }}</option>@endforeach</select></div>
        <div class="mb-3"><label class="form-label">Canonical JSON Template</label><textarea name="canonical_template" rows="4" class="form-control font-monospace">{{ old('canonical_template', \App\Services\MqttOutboxService::CANONICAL_DEFAULT_TEMPLATE) }}</textarea></div>
        <div><label class="form-label">Warning JSON Template</label><textarea name="warning_template" rows="4" class="form-control font-monospace">{{ old('warning_template', \App\Services\MqttOutboxService::WARNING_DEFAULT_TEMPLATE) }}</textarea></div>
    </div>
    <div class="form-check form-switch mb-3"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="{{ $mqttFormId }}-active" @checked(old('is_active', 1))><label class="form-check-label" for="{{ $mqttFormId }}-active">Active / auto-connect</label></div>
    <button class="btn btn-primary" @disabled($mqttProjects->isEmpty())>Save / Update Configuration</button>
    <button type="reset" class="btn btn-outline-secondary">Reset</button>
</form>
