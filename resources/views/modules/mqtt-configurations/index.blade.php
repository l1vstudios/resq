@extends('layouts.master')

@section('title') MQTT Configuration @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Configuration @endslot
@slot('title') MQTT Configuration @endslot
@endcomponent

@if (session('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Periksa kembali konfigurasi MQTT berikut:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-xl-5">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1">Broker & Project Binding</h4>
                <p class="text-muted">Credential disimpan terenkripsi dan tidak pernah ditampilkan kembali.</p>
                <form method="POST" action="{{ route('mqtt-configurations.store') }}" id="mqtt-configuration-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Project</label><select name="project_id" class="form-select" required>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->project_code }} - {{ $project->name }}</option>@endforeach</select></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Configuration Code</label><input name="configuration_code" class="form-control" value="{{ old('configuration_code') }}" placeholder="MQTT-PROJECT-01" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
                    <div class="mb-3"><label class="form-label">Broker URL</label><input name="broker_url" class="form-control" value="{{ old('broker_url') }}" placeholder="mqtts://broker.example.com:8883" required></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Username</label><input name="username" class="form-control" value="{{ old('username') }}"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" placeholder="Kosong = pertahankan password"></div>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <div class="form-check form-switch mb-3"><input type="hidden" name="consumer_enabled" value="0"><input class="form-check-input" type="checkbox" name="consumer_enabled" value="1" id="mqtt-consumer-enabled" @checked(old('consumer_enabled'))><label class="form-check-label fw-bold" for="mqtt-consumer-enabled">Consumer</label></div>
                        <div class="row">
                            <div class="col-md-9 mb-3"><label class="form-label">Input Topic</label><input name="consumer_topic" class="form-control" value="{{ old('consumer_topic') }}" placeholder="project/telemetry/#"></div>
                            <div class="col-md-3 mb-3"><label class="form-label">QoS</label><select name="consumer_qos" class="form-select">@foreach([0, 1, 2] as $qos)<option value="{{ $qos }}" @selected((string) old('consumer_qos', 0) === (string) $qos)>{{ $qos }}</option>@endforeach</select></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Example Output (JSON)</label><textarea name="example_payload" rows="5" class="form-control font-monospace" placeholder='{"sensor_code":"SNS-01","data":{"temperature":28.5}}'>{{ old('example_payload') }}</textarea></div>
                        <div><label class="form-label">Sensor Code JSON Path</label><input name="sensor_code_path" class="form-control" value="{{ old('sensor_code_path', 'sensor_code') }}" placeholder="device.sensor_code"></div>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <div class="form-check form-switch mb-3"><input type="hidden" name="producer_enabled" value="0"><input class="form-check-input" type="checkbox" name="producer_enabled" value="1" id="mqtt-producer-enabled" @checked(old('producer_enabled'))><label class="form-check-label fw-bold" for="mqtt-producer-enabled">Producer</label></div>
                        <div class="row">
                            <div class="col-md-8 mb-3"><label class="form-label">Output Topic</label><input name="producer_topic" class="form-control" value="{{ old('producer_topic') }}" placeholder="project/output"></div>
                            <div class="col-md-2 mb-3"><label class="form-label">QoS</label><select name="producer_qos" class="form-select">@foreach([0, 1, 2] as $qos)<option value="{{ $qos }}" @selected((string) old('producer_qos', 0) === (string) $qos)>{{ $qos }}</option>@endforeach</select></div>
                            <div class="col-md-2 mb-3"><label class="form-label">Retain</label><div class="form-check mt-2"><input type="hidden" name="producer_retain" value="0"><input type="checkbox" name="producer_retain" value="1" class="form-check-input" @checked(old('producer_retain'))></div></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><div class="form-check mb-2"><input type="hidden" name="publish_canonical" value="0"><input class="form-check-input" type="checkbox" name="publish_canonical" value="1" id="publish-canonical" @checked(old('publish_canonical'))><label class="form-check-label" for="publish-canonical">Publish canonical</label></div></div>
                            <div class="col-md-6"><div class="form-check mb-2"><input type="hidden" name="publish_warning" value="0"><input class="form-check-input" type="checkbox" name="publish_warning" value="1" id="publish-warning" @checked(old('publish_warning'))><label class="form-check-label" for="publish-warning">Publish warning transition</label></div></div>
                        </div>
                        <div class="mb-3"><label class="form-label">Canonical Parameter Filter</label><select name="canonical_parameter_ids[]" class="form-select" multiple>@foreach(\App\Models\CanonicalParameter::orderBy('field_identity')->get() as $parameter)<option value="{{ $parameter->id }}" @selected(in_array((string) $parameter->id, array_map('strval', old('canonical_parameter_ids', [])), true))>{{ $parameter->field_identity }}</option>@endforeach</select><small class="text-muted">Kosong berarti semua parameter.</small></div>
                        <div class="mb-3"><label class="form-label">Warning Level Filter</label><select name="warning_levels[]" class="form-select" multiple>@foreach(['Normal','Waspada','Siaga','Awas'] as $level)<option value="{{ $level }}" @selected(in_array($level, old('warning_levels', []), true))>{{ $level }}</option>@endforeach</select></div>
                        <div class="mb-3"><label class="form-label">Canonical JSON Template</label><textarea name="canonical_template" rows="4" class="form-control font-monospace">{{ old('canonical_template', \App\Services\MqttOutboxService::CANONICAL_DEFAULT_TEMPLATE) }}</textarea></div>
                        <div><label class="form-label">Warning JSON Template</label><textarea name="warning_template" rows="4" class="form-control font-monospace">{{ old('warning_template', \App\Services\MqttOutboxService::WARNING_DEFAULT_TEMPLATE) }}</textarea></div>
                    </div>
                    <div class="form-check form-switch mb-3"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="mqtt-active" @checked(old('is_active', 1))><label class="form-check-label" for="mqtt-active">Active / auto-connect</label></div>
                    <button class="btn btn-primary" @disabled($projects->isEmpty())>Save / Update Configuration</button>
                    <button type="reset" class="btn btn-outline-secondary">Reset</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="card-title mb-1">Broker Monitor</h4><span class="text-muted small">Status diperbarui otomatis dari runtime Node.</span></div><button type="button" class="btn btn-outline-primary btn-sm" id="mqtt-refresh">Refresh</button></div>
                <div class="table-responsive"><table class="table table-nowrap align-middle"><thead class="table-light"><tr><th>Configuration</th><th>Project</th><th>Mode / Topic</th><th>Status</th><th>Last Activity</th><th></th></tr></thead><tbody>
                @forelse($configurations as $config)
                    <tr data-mqtt-row="{{ $config->id }}">
                        <td><strong>{{ $config->name }}</strong><br><code>{{ $config->configuration_code }}</code><br><span class="small text-muted">{{ $config->broker_url }}</span></td>
                        <td>{{ $config->project?->project_code }}</td>
                        <td>@if($config->consumer_enabled)<span class="badge bg-info">Consumer QoS {{ $config->consumer_qos }}</span><br><small>{{ $config->consumer_topic }}</small>@endif @if($config->producer_enabled)<br><span class="badge bg-primary">Producer QoS {{ $config->producer_qos }}{{ $config->producer_retain ? ' retain' : '' }}</span><br><small>{{ $config->producer_topic }}</small>@endif</td>
                        <td><span class="badge {{ $config->connection_status === 'connected' ? 'bg-success' : ($config->connection_status === 'error' ? 'bg-danger' : 'bg-secondary') }}" data-status>{{ $config->is_active ? $config->connection_status : 'inactive' }}</span><div class="small text-danger mt-1" data-error>{{ $config->last_error }}</div></td>
                        <td class="small"><div>IN: <span data-received>{{ optional($config->last_received_at)->diffForHumans() ?: '-' }}</span></div><div>OUT: <span data-published>{{ optional($config->last_published_at)->diffForHumans() ?: '-' }}</span></div><div>Sensor: {{ $config->sensors->count() }}</div></td>
                        <td class="text-end"><button type="button" class="btn btn-outline-success btn-sm" data-test-url="{{ route('mqtt-configurations.test', $config) }}">Test</button> <button type="button" class="btn btn-outline-primary btn-sm" data-edit-fields="{{ base64_encode(json_encode(['project_id'=>$config->project_id,'configuration_code'=>$config->configuration_code,'name'=>$config->name,'broker_url'=>$config->broker_url,'username'=>$config->username,'consumer_enabled'=>$config->consumer_enabled,'consumer_topic'=>$config->consumer_topic,'consumer_qos'=>$config->consumer_qos,'example_payload'=>json_encode($config->example_payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),'sensor_code_path'=>$config->sensor_code_path,'producer_enabled'=>$config->producer_enabled,'producer_topic'=>$config->producer_topic,'producer_qos'=>$config->producer_qos,'producer_retain'=>$config->producer_retain,'publish_canonical'=>$config->publish_canonical,'publish_warning'=>$config->publish_warning,'canonical_parameter_ids'=>$config->canonical_parameter_ids ?? [],'warning_levels'=>$config->warning_levels ?? [],'canonical_template'=>$config->canonical_template,'warning_template'=>$config->warning_template,'is_active'=>$config->is_active])) }}" data-edit-form="#mqtt-configuration-form">Edit</button><form class="d-inline" method="POST" action="{{ route('mqtt-configurations.destroy', $config) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form></td>
                    </tr>
                @empty<tr><td colspan="6" class="text-center text-muted">Belum ada MQTT configuration.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(() => {
    const refresh = async () => {
        const response = await fetch(@json(route('mqtt-configurations.status')), {headers: {'Accept':'application/json'}});
        if (!response.ok) return;
        const body = await response.json();
        body.configurations.forEach(item => {
            const row = document.querySelector(`[data-mqtt-row="${item.id}"]`);
            if (!row) return;
            const badge = row.querySelector('[data-status]');
            badge.textContent = item.status;
            badge.className = `badge ${item.status === 'connected' ? 'bg-success' : (item.status === 'error' ? 'bg-danger' : 'bg-secondary')}`;
            row.querySelector('[data-error]').textContent = item.last_error || '';
            row.querySelector('[data-received]').textContent = item.last_received_at || '-';
            row.querySelector('[data-published]').textContent = item.last_published_at || '-';
        });
    };
    document.getElementById('mqtt-refresh')?.addEventListener('click', refresh);
    document.querySelectorAll('[data-test-url]').forEach(button => button.addEventListener('click', async () => {
        button.disabled = true;
        const response = await fetch(button.dataset.testUrl, {method:'POST', headers:{'Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())}});
        const body = await response.json().catch(() => ({}));
        window.alert(body.ok ? (body.message || `Test publish berhasil ke ${body.topic}`) : (body.message || 'Test MQTT gagal.'));
        button.disabled = false;
        refresh();
    }));
    window.setInterval(refresh, 5000);
})();
</script>
@endsection
