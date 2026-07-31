@extends('layouts.master')
@section('title') Mapping {{ $version->profile->name }} v{{ $version->version }} @endsection
@section('content')
@component('components.breadcrumb') @slot('li_1') Mapping Workbench @endslot @slot('title') {{ $version->profile->name }} v{{ $version->version }} @endslot @endcomponent

@if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
@if (session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
@if ($errors->any()) <div class="alert alert-danger"><strong>Periksa konfigurasi:</strong><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

<div class="card"><div class="card-body d-flex flex-wrap justify-content-between gap-3">
    <div><h4 class="mb-1">{{ $version->profile->manufacturer }} / {{ $version->profile->device_model }}</h4><code>{{ $version->profile->profile_key }}</code><p class="text-muted mb-0">{{ $version->profile->description }}</p></div>
    <div class="text-end"><span class="badge {{ $version->status === 'published' ? 'bg-success' : 'bg-warning text-dark' }} font-size-13">{{ $version->status }} · v{{ $version->version }}</span><div class="mt-2"><a href="{{ route('mapping-workbench.index') }}">Kembali ke daftar</a></div></div>
</div></div>

<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center"><h4 class="card-title">Version History</h4><span class="text-muted">Published version tidak dapat diedit.</span></div>
    <div class="d-flex flex-wrap gap-2 mt-3">@foreach ($version->profile->versions as $item)<a class="btn btn-sm {{ $item->id === $version->id ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('mapping-workbench.show', $item) }}">v{{ $item->version }} · {{ $item->status }}</a>@endforeach</div>
</div></div>

<div class="row">
    <div class="col-xl-5">
        <div class="card"><div class="card-body">
            <h4 class="card-title">{{ $version->status === 'draft' ? 'Rule Editor' : 'Rule Configuration (read-only)' }}</h4>
            @if ($version->status === 'draft')
            <form method="POST" action="{{ route('mapping-workbench.rules.save', $version) }}">@csrf
                @if ($editRule)<input type="hidden" name="rule_id" value="{{ $editRule->id }}">@endif
                <div class="row"><div class="col-md-8 mb-3"><label class="form-label">Source parameter</label><input name="source_parameter" class="form-control" value="{{ old('source_parameter', $editRule?->source_parameter) }}" required></div><div class="col-md-4 mb-3"><label class="form-label">Item key</label><input name="source_item_key" class="form-control" value="{{ old('source_item_key', $editRule?->source_item_key) }}"></div></div>
                <div class="row"><div class="col-md-4 mb-3"><label class="form-label">Parser</label><select name="parser" class="form-select">@foreach (['boolean','uint16','int16','uint32','int32','float32','decimal'] as $parser)<option @selected(old('parser', $editRule?->parser ?? 'uint16') === $parser)>{{ $parser }}</option>@endforeach</select></div><div class="col-md-4 mb-3"><label class="form-label">Byte offset</label><input type="number" min="0" name="byte_offset" value="{{ old('byte_offset', $editRule?->byte_offset ?? 0) }}" class="form-control"></div><div class="col-md-4 mb-3"><label class="form-label">Byte length</label><input type="number" min="1" name="byte_length" value="{{ old('byte_length', $editRule?->byte_length ?? 2) }}" class="form-control"></div></div>
                <div class="row"><div class="col-md-4 mb-3"><label class="form-label">Signedness</label><select name="signedness" class="form-select">@foreach (['unsigned','signed','not_applicable'] as $choice)<option @selected(old('signedness', $editRule?->signedness ?? 'unsigned') === $choice)>{{ $choice }}</option>@endforeach</select></div><div class="col-md-4 mb-3"><label class="form-label">Byte order</label><select name="byte_order" class="form-select">@foreach (['big','little'] as $choice)<option @selected(old('byte_order', $editRule?->byte_order ?? 'big') === $choice)>{{ $choice }}</option>@endforeach</select></div><div class="col-md-4 mb-3"><label class="form-label">Word order</label><select name="word_order" class="form-select">@foreach (['high_low','low_high'] as $choice)<option @selected(old('word_order', $editRule?->word_order ?? 'high_low') === $choice)>{{ $choice }}</option>@endforeach</select></div></div>
                <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Register start</label><input type="number" min="0" name="register_start" value="{{ old('register_start', $editRule?->register_start) }}" class="form-control"></div><div class="col-md-6 mb-3"><label class="form-label">Register count</label><input type="number" min="1" name="register_count" value="{{ old('register_count', $editRule?->register_count) }}" class="form-control"></div></div>
                <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Scale</label><input name="scale" class="form-control" value="{{ old('scale', $editRule?->scale ?? '1') }}"></div><div class="col-md-6 mb-3"><label class="form-label">Offset</label><input name="offset" class="form-control" value="{{ old('offset', $editRule?->offset ?? '0') }}"></div></div>
                <div class="mb-3"><label class="form-label">Source unit</label><select name="source_unit_id" class="form-select" required>@foreach ($units as $unit)<option value="{{ $unit->id }}" @selected((int) old('source_unit_id', $editRule?->source_unit_id) === $unit->id)>{{ $unit->code }} ({{ $unit->symbol }}) · {{ $unit->dimension_key }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">Canonical target</label><select name="canonical_parameter_version_id" class="form-select" required>@foreach ($definitions as $definition)<option value="{{ $definition->id }}" @selected((int) old('canonical_parameter_version_id', $editRule?->canonical_parameter_version_id) === $definition->id)>{{ $definition->parameter->key }} · {{ $definition->unit->symbol }} · v{{ $definition->version }}</option>@endforeach</select></div>
                <div class="row"><div class="col-md-4 mb-3"><label class="form-label">Origin</label><select name="origin" class="form-select">@foreach (['RDM','RDP'] as $choice)<option @selected(old('origin', $editRule?->origin ?? 'RDM') === $choice)>{{ $choice }}</option>@endforeach</select></div><div class="col-md-8 mb-3"><label class="form-label">Missing markers (satu per baris)</label><textarea name="missing_markers_text" class="form-control" rows="2" placeholder="hex:ffff">{{ old('missing_markers_text', $editRule ? implode("\n", $editRule->missing_markers ?? []) : '') }}</textarea></div></div>
                <button class="btn btn-primary">{{ $editRule ? 'Update Rule' : 'Tambah Rule' }}</button>@if ($editRule)<a class="btn btn-outline-secondary" href="{{ route('mapping-workbench.show', $version) }}">Batal</a>@endif
            </form>
            @else <div class="alert alert-info mb-0">Versi ini immutable. Gunakan Clone to New Draft untuk perubahan.</div> @endif
        </div></div>
    </div>
    <div class="col-xl-7">
        <div class="card"><div class="card-body"><h4 class="card-title">Rules</h4>
            <div class="table-responsive"><table class="table table-sm align-middle"><thead class="table-light"><tr><th>#</th><th>Source</th><th>Parse</th><th>Normalize</th><th>Target</th><th>Origin</th><th></th></tr></thead><tbody>
                @forelse ($version->rules as $rule)<tr><td>{{ $rule->sort_order }}</td><td><code>{{ $rule->source_parameter }}</code><div class="text-muted">{{ $rule->source_item_key ?: '—' }}</div></td><td>{{ $rule->parser }} / {{ $rule->byte_length }}B<div class="text-muted">{{ $rule->byte_order }} · {{ $rule->word_order }}</div></td><td>× {{ $rule->scale }} + {{ $rule->offset }}<div class="text-muted">{{ $rule->sourceUnit?->symbol ?? '—' }}</div></td><td><code>{{ $rule->canonicalParameter?->key ?? '—' }}</code><div class="text-muted">{{ $rule->canonicalDefinition?->unit?->symbol ?? '—' }}</div></td><td><span class="badge bg-info">{{ $rule->origin }}</span></td><td>@if ($version->status === 'draft')<div class="d-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ route('mapping-workbench.show', ['version' => $version, 'edit_rule' => $rule->id]) }}">Edit</a><form method="POST" action="{{ route('mapping-workbench.rules.destroy', [$version, $rule]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form></div>@endif</td></tr>
                @empty <tr><td colspan="7" class="text-center text-muted py-4">Draft belum memiliki rule. Tambahkan minimal satu rule sebelum publish.</td></tr> @endforelse
            </tbody></table></div>
            <div class="alert {{ $validation['valid'] ? 'alert-success' : 'alert-warning' }} mt-3"><strong>{{ $validation['valid'] ? 'Valid' : 'Belum valid' }}</strong>@if (! $validation['valid'])<ul class="mb-0 mt-2">@foreach ($validation['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>@endif</div>
            <form method="POST" action="{{ route('mapping-workbench.validate', $version) }}">@csrf <button class="btn btn-outline-primary">Validate Sekarang</button></form>
        </div></div>
    </div>
</div>

<div class="row">
    <div class="col-xl-7"><div class="card"><div class="card-body"><h4 class="card-title">Preview <span class="badge bg-info">Tanpa side effect</span></h4>
        <form method="POST" action="{{ route('mapping-workbench.preview', $version) }}">@csrf
            <div class="row"><div class="col-md-4 mb-3"><label class="form-label">Rule</label><select name="rule_id" class="form-select" required>@foreach ($version->rules as $rule)<option value="{{ $rule->id }}">#{{ $rule->sort_order }} {{ $rule->source_parameter }}</option>@endforeach</select></div><div class="col-md-4 mb-3"><label class="form-label">Stored raw item (opsional)</label><select name="raw_item_id" class="form-select"><option value="">Gunakan sample</option>@foreach ($rawItems as $item)<option value="{{ $item->id }}">#{{ $item->id }} {{ $item->source_parameter ?: $item->item_key }}</option>@endforeach</select></div><div class="col-md-4 mb-3"><label class="form-label">Sample format</label><select name="sample_format" class="form-select"><option value="hex">hex</option><option value="text">text</option></select></div></div>
            <div class="mb-3"><label class="form-label">Sample value</label><textarea name="sample" class="form-control" rows="2" placeholder="012e"></textarea></div><button class="btn btn-primary" @disabled($version->rules->isEmpty())>Jalankan Preview</button>
        </form>
        @if (session('preview_result')) @php $preview = session('preview_result'); @endphp
            <div class="alert {{ $preview['status'] === 'value' ? 'alert-success' : 'alert-warning' }} mt-4"><strong>{{ $preview['status'] }}</strong> · value: <code>{{ $preview['value'] ?? '—' }}</code> · reason: {{ $preview['reason'] ?? '—' }}</div>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Stage</th><th>Status</th><th>Detail</th></tr></thead><tbody>@foreach ($preview['stages'] as $stage)<tr><td>{{ $stage['stage'] }}</td><td>{{ $stage['status'] }}</td><td><code class="text-wrap">{{ json_encode($stage, JSON_UNESCAPED_SLASHES) }}</code></td></tr>@endforeach</tbody></table></div>
        @endif
    </div></div></div>
    <div class="col-xl-5"><div class="card"><div class="card-body"><h4 class="card-title">Version Lifecycle</h4>
        @if ($version->status === 'draft')
            <form method="POST" action="{{ route('mapping-workbench.publish', $version) }}">@csrf <label class="form-label">Change reason</label><textarea name="change_reason" class="form-control mb-3" required></textarea><button class="btn btn-success" @disabled(! $validation['valid'])>Publish Immutable Version</button></form>
        @else
            <p class="text-muted">Published {{ optional($version->published_at)->format('Y-m-d H:i:s') }}. Perubahan harus melalui draft baru.</p>
            <form method="POST" action="{{ route('mapping-workbench.clone', $version) }}">@csrf <label class="form-label">Alasan clone</label><textarea name="change_reason" class="form-control mb-3"></textarea><button class="btn btn-outline-primary">Clone to New Draft</button></form>
        @endif
    </div></div></div>
</div>

@if ($version->status === 'published')
<div class="card"><div class="card-body"><h4 class="card-title">Activation / Rollback</h4><p class="text-muted">Assignment selalu memilih satu source konkret; destination tidak pernah ikut tersalin saat clone.</p>
    <form method="POST" action="{{ route('mapping-workbench.activate', $version) }}" class="row g-3 mb-4">@csrf
        <div class="col-md-5"><label class="form-label">Exact source</label><select name="source" class="form-select" required><optgroup label="Data Logger">@foreach ($dataLoggers as $logger)<option value="data_logger:{{ $logger->id }}">{{ $logger->logger_code }} · {{ $logger->vendor }}/{{ $logger->logger_model }}</option>@endforeach</optgroup><optgroup label="Sensor">@foreach ($sensors as $sensor)<option value="sensor:{{ $sensor->id }}">{{ $sensor->sensor_code }} · {{ $sensor->type }}</option>@endforeach</optgroup></select></div>
        <div class="col-md-5"><label class="form-label">Activation reason</label><input name="activation_reason" class="form-control" required></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">Activate</button></div>
    </form>
    <div class="table-responsive"><table class="table table-sm align-middle"><thead class="table-light"><tr><th>Source</th><th>Active version</th><th>Lock</th><th>Reason</th><th>Rollback</th></tr></thead><tbody>@forelse ($assignments as $assignment)<tr><td><code>{{ $assignment->scope_key }}</code></td><td>{{ $assignment->activeVersion?->profile?->name }} v{{ $assignment->activeVersion?->version }}</td><td>{{ $assignment->lock_version }}</td><td>{{ $assignment->activation_reason }}</td><td>@if ($assignment->active_version_id !== $version->id)<form method="POST" action="{{ route('mapping-workbench.rollback', $assignment) }}">@csrf<input type="hidden" name="target_version_id" value="{{ $version->id }}"><input name="rollback_reason" class="form-control form-control-sm mb-1" placeholder="Alasan rollback" required><button class="btn btn-sm btn-outline-warning">Rollback ke v{{ $version->version }}</button></form>@endif</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">Belum ada assignment aktif.</td></tr>@endforelse</tbody></table></div>
</div></div>
@endif
@endsection
