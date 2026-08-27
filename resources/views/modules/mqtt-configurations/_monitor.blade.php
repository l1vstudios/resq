@php
    $mqttConfigurations = collect($mqttConfigurations ?? $configurations ?? []);
    $mqttEditForm = $mqttEditForm ?? '#mqtt-configuration-form';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-size-14 mb-1">MQTT Broker Registry</h5>
        <span class="text-muted small">Status diperbarui otomatis dari runtime Node.</span>
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" data-mqtt-refresh>Refresh</button>
</div>
<div class="table-responsive mb-4">
    <table class="table table-nowrap align-middle mb-0">
        <thead class="table-light"><tr><th>Configuration</th><th>Project</th><th>Mode / Topic</th><th>Status</th><th>Last Activity</th><th></th></tr></thead>
        <tbody>
        @forelse($mqttConfigurations as $config)
            <tr data-mqtt-row="{{ $config->id }}">
                <td><strong>{{ $config->name }}</strong><br><code>{{ $config->configuration_code }}</code><br><span class="small text-muted">{{ $config->broker_url }}</span></td>
                <td>{{ $config->project?->project_code }}</td>
                <td>@if($config->consumer_enabled)<span class="badge bg-info">Consumer QoS {{ $config->consumer_qos }}</span><br><small>{{ $config->consumer_topic }}</small>@endif @if($config->producer_enabled)<br><span class="badge bg-primary">Producer QoS {{ $config->producer_qos }}{{ $config->producer_retain ? ' retain' : '' }}</span><br><small>{{ $config->producer_topic }}</small>@endif</td>
                <td><span class="badge {{ $config->connection_status === 'connected' ? 'bg-success' : ($config->connection_status === 'error' ? 'bg-danger' : 'bg-secondary') }}" data-status>{{ $config->is_active ? $config->connection_status : 'inactive' }}</span><div class="small text-danger mt-1" data-error>{{ $config->last_error }}</div></td>
                <td class="small"><div>IN: <span data-received>{{ optional($config->last_received_at)->diffForHumans() ?: '-' }}</span></div><div>OUT: <span data-published>{{ optional($config->last_published_at)->diffForHumans() ?: '-' }}</span></div><div>Sensor: {{ $config->sensors->count() }}</div></td>
                <td class="text-end"><button type="button" class="btn btn-outline-success btn-sm" data-test-url="{{ route('mqtt-configurations.test', $config) }}">Test</button> <button type="button" class="btn btn-outline-primary btn-sm" data-edit-fields="{{ base64_encode(json_encode(['project_id'=>$config->project_id,'configuration_code'=>$config->configuration_code,'name'=>$config->name,'broker_url'=>$config->broker_url,'username'=>$config->username,'consumer_enabled'=>$config->consumer_enabled,'consumer_topic'=>$config->consumer_topic,'consumer_qos'=>$config->consumer_qos,'example_payload'=>json_encode($config->example_payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),'sensor_code_path'=>$config->sensor_code_path,'producer_enabled'=>$config->producer_enabled,'producer_topic'=>$config->producer_topic,'producer_qos'=>$config->producer_qos,'producer_retain'=>$config->producer_retain,'publish_canonical'=>$config->publish_canonical,'publish_warning'=>$config->publish_warning,'canonical_parameter_ids'=>$config->canonical_parameter_ids ?? [],'warning_levels'=>$config->warning_levels ?? [],'canonical_template'=>$config->canonical_template,'warning_template'=>$config->warning_template,'is_active'=>$config->is_active])) }}" data-edit-form="{{ $mqttEditForm }}">Edit</button><form class="d-inline" method="POST" action="{{ route('mqtt-configurations.destroy', $config) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete</button></form></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">Belum ada MQTT configuration.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
