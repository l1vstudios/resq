@extends('layouts.master')
@section('title') Canonical Ingress Rollout @endsection
@section('content')
@component('components.breadcrumb') @slot('li_1') Canonical Data @endslot @slot('title') Ingress Rollout @endslot @endcomponent

@if (! $available)
    <div class="alert alert-warning">Tabel rollout canonical belum tersedia. Jalankan migration terlebih dahulu.</div>
@endif
@if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
@if ($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-title">Bukti rollout terbatasi</h4>
        <p class="text-muted">Semua waktu UTC. Tampilan ini hanya memuat metadata, hitungan, digest, dan lineage terotorisasi; payload, header, inspection JSON, serta credential tidak ditampilkan.</p>
        <form method="GET" action="{{ route('canonical-ingress-rollout.index') }}" class="row g-3 align-items-end">
            <div class="col-lg-3"><label class="form-label" for="rollout-path">Ingress path</label><select id="rollout-path" name="path" class="form-select">@foreach ($paths as $candidate)<option value="{{ $candidate }}" @selected($candidate === $path)>{{ $candidate }}</option>@endforeach</select></div>
            <div class="col-lg-3"><label class="form-label" for="from-utc">Dari UTC</label><input id="from-utc" name="from_utc" type="datetime-local" class="form-control" value="{{ $fromUtc->format('Y-m-d\TH:i') }}" required></div>
            <div class="col-lg-3"><label class="form-label" for="to-utc">Sampai UTC</label><input id="to-utc" name="to_utc" type="datetime-local" class="form-control" value="{{ $toUtc->format('Y-m-d\TH:i') }}" required></div>
            <div class="col-lg-2"><label class="form-label" for="evidence-limit">Batas baris</label><input id="evidence-limit" name="limit" type="number" min="1" max="{{ config('canonical.ingress_rollout.evidence_max_rows', 1000) }}" class="form-control" value="{{ $limit }}" required></div>
            <div class="col-lg-1"><button class="btn btn-primary w-100" @disabled(! $available)>Muat</button></div>
        </form>
    </div>
</div>

<div class="row">
    @forelse ($rolloutStates as $rolloutState)
        @php
            $latestAttestation = $rolloutState->attestations->sortByDesc(fn ($item) => [$item->verified_at?->getTimestamp(), $item->id])->first();
            $attestationCurrent = $latestAttestation
                && $latestAttestation->suite_version === $suiteVersion
                && $latestAttestation->passed_count > 0
                && $latestAttestation->failed_count === 0
                && $latestAttestation->verified_at?->gte($attestationFreshAfter);
        @endphp
        <div class="col-xl-4 col-md-6">
            <div class="card h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-start"><h5 class="card-title"><code>{{ $rolloutState->path_key }}</code></h5><span class="badge {{ $rolloutState->state === 'cutover' ? 'bg-success' : ($rolloutState->state === 'rolled_back' ? 'bg-danger' : 'bg-info text-dark') }}">{{ $rolloutState->state }}</span></div>
                <dl class="row small mb-3">
                    <dt class="col-5">Changed UTC</dt><dd class="col-7">{{ optional($rolloutState->state_changed_at)->utc()?->format('Y-m-d H:i:s') ?? '—' }}</dd>
                    <dt class="col-5">Actor</dt><dd class="col-7">{{ $rolloutState->actor?->email ?? 'system' }}</dd>
                    <dt class="col-5">Reason</dt><dd class="col-7">{{ $rolloutState->reason }}</dd>
                    <dt class="col-5">Canonical read</dt><dd class="col-7">{{ $rolloutState->state === 'cutover' ? 'enabled' : 'disabled' }}</dd>
                    <dt class="col-5">Attestation</dt><dd class="col-7"><span class="badge {{ $attestationCurrent ? 'bg-success' : 'bg-secondary' }}">{{ $attestationCurrent ? 'current passing' : 'not current' }}</span></dd>
                </dl>
                @foreach ($allowedTransitions[$rolloutState->state] ?? [] as $targetState)
                    <form method="POST" action="{{ route('canonical-ingress-rollout.transition') }}" class="border rounded p-2 mb-2">
                        @csrf
                        <input type="hidden" name="path" value="{{ $rolloutState->path_key }}">
                        <input type="hidden" name="target_state" value="{{ $targetState }}">
                        <label class="form-label small" for="reason-{{ $rolloutState->id }}-{{ $targetState }}">Reason untuk {{ $targetState }}</label>
                        <textarea id="reason-{{ $rolloutState->id }}-{{ $targetState }}" name="reason" class="form-control form-control-sm mb-2" rows="2" maxlength="{{ config('canonical.ingress_rollout.reason_max_length', 500) }}" required></textarea>
                        <button class="btn btn-sm btn-outline-primary" @disabled(! $available)>Pindah ke {{ $targetState }}</button>
                    </form>
                @endforeach
            </div></div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-secondary">Belum ada state rollout yang dapat ditampilkan.</div></div>
    @endforelse
</div>

@if ($selectedState)
    <div class="card mt-4"><div class="card-body">
        <h4 class="card-title">Ringkasan bukti: <code>{{ $path }}</code></h4>
        <div class="table-responsive"><table class="table table-sm align-middle">
            <thead class="table-light"><tr><th>Capture</th><th>Klasifikasi</th><th>Parity</th><th>Total</th><th>Value</th><th>Non-value</th><th>Failure</th></tr></thead>
            <tbody>@forelse ($summary as $row)<tr><td>{{ $row['capture_outcome'] }}</td><td>{{ $row['payload_classification'] ?? '—' }}</td><td>{{ $row['parity_status'] ?? '—' }}</td><td>{{ $row['total'] }}</td><td>{{ $row['canonical_value_count'] }}</td><td>{{ $row['canonical_non_value_count'] }}</td><td>{{ $row['canonical_failure_count'] }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">Tidak ada bukti pada rentang ini.</td></tr>@endforelse</tbody>
        </table></div>
    </div></div>

    <div class="card mt-4"><div class="card-body">
        <h4 class="card-title">Bukti immutable terbaru</h4>
        <div class="table-responsive"><table class="table table-sm align-middle">
            <thead class="table-light"><tr><th>UTC</th><th>Capture / reason</th><th>Raw semantics</th><th>Mapping / canonical</th><th>Projection / parity</th><th>Lineage</th></tr></thead>
            <tbody>@forelse ($evidence as $row)<tr>
                <td>{{ optional($row->recorded_at)->utc()?->format('Y-m-d H:i:s') ?? '—' }}</td>
                <td>{{ $row->capture_outcome }}<div class="text-muted">{{ $row->reason_code ?? '—' }}</div></td>
                <td>{{ $row->payload_classification ?? '—' }}<div class="text-muted">{{ $row->payload_size ?? '—' }} bytes · {{ $row->payload_sha256 ? substr($row->payload_sha256, 0, 12).'…' : 'no digest' }}</div></td>
                <td>{{ $row->mapped === null ? '—' : ($row->mapped ? 'mapped' : 'unmapped') }}<div class="text-muted">values {{ $row->canonical_value_count }}, non-values {{ $row->canonical_non_value_count }}, failures {{ $row->canonical_failure_count }}</div></td>
                <td>{{ $row->compatibility_projected ? 'projected' : ($row->compatibility_eligible ? 'eligible' : 'not eligible') }}<div class="text-muted">{{ $row->parity_status ?? '—' }} @if ($row->parity_difference_decimal !== null) Δ {{ $row->parity_difference_decimal }} @endif</div></td>
                <td>@if ($row->raw_ingestion_event_id)<a href="{{ route('canonical-trace.raw', $row->raw_ingestion_event_id) }}">raw #{{ $row->raw_ingestion_event_id }}</a>@else — @endif<div class="text-muted">run #{{ $row->canonical_processing_run_id ?? '—' }} · mapping #{{ $row->mapping_profile_version_id ?? '—' }}</div></td>
            </tr>@empty<tr><td colspan="6" class="text-center text-muted">Tidak ada baris bukti pada rentang ini.</td></tr>@endforelse</tbody>
        </table></div>
    </div></div>

    <div class="row mt-4">
        <div class="col-xl-6"><div class="card h-100"><div class="card-body">
            <h4 class="card-title">Transition history (immutable)</h4>
            <div class="table-responsive"><table class="table table-sm"><thead class="table-light"><tr><th>UTC</th><th>Transition</th><th>Actor</th><th>Reason</th></tr></thead><tbody>@forelse ($selectedState->transitions as $transition)<tr><td>{{ optional($transition->created_at)->utc()?->format('Y-m-d H:i:s') ?? '—' }}</td><td>{{ $transition->from_state }} → {{ $transition->to_state }}</td><td>{{ $transition->actor?->email ?? 'deleted actor' }}</td><td>{{ $transition->reason }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">Belum ada transition.</td></tr>@endforelse</tbody></table></div>
        </div></div></div>
        <div class="col-xl-6"><div class="card h-100"><div class="card-body">
            <h4 class="card-title">Attestation history (immutable)</h4>
            <div class="table-responsive"><table class="table table-sm"><thead class="table-light"><tr><th>UTC</th><th>Suite</th><th>Result</th><th>Actor</th><th>Digest</th></tr></thead><tbody>@forelse ($selectedState->attestations as $attestation)<tr><td>{{ optional($attestation->verified_at)->utc()?->format('Y-m-d H:i:s') ?? '—' }}</td><td>{{ $attestation->suite_version }}</td><td>{{ $attestation->passed_count }} pass / {{ $attestation->failed_count }} fail</td><td>{{ $attestation->actor?->email ?? 'deleted actor' }}</td><td><code>{{ substr($attestation->result_digest, 0, 12) }}…</code></td></tr>@empty<tr><td colspan="5" class="text-center text-muted">Belum ada attestation.</td></tr>@endforelse</tbody></table></div>
        </div></div></div>
    </div>
@endif
@endsection
