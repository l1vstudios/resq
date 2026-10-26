@php
    $stepKeys = array_keys($setupSteps);
    $stepIndex = array_search($activeSection, $stepKeys, true);
    $previousStep = $stepIndex > 0 ? $stepKeys[$stepIndex - 1] : null;
    $nextStep = $stepKeys[$stepIndex + 1] ?? null;
    $stepUrl = fn ($step) => $step === 'project'
        ? route('projects.index', $setupProject ? ['project_id' => $setupProject->id] : [])
        : route('project-setup.section', array_filter(['section' => $step, 'project_id' => $setupProject?->id]));
@endphp
<div class="card" data-setup-navigation>
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="card-title mb-1">Alur Registrasi Project</h4>
                @if ($setupProject)
                    <p class="text-muted mb-0">{{ $setupProject->project_code }} · {{ $setupProject->name }} · Langkah {{ $stepIndex + 1 }} dari {{ count($stepKeys) }}</p>
                @else
                    <p class="text-muted mb-0">Simpan Project untuk memulai, atau pilih Lanjut Setup pada project yang sudah terdaftar.</p>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if ($previousStep)
                    <a href="{{ $stepUrl($previousStep) }}" class="btn btn-outline-secondary" data-setup-previous><i class="bx bx-left-arrow-alt me-1" aria-hidden="true"></i> Sebelumnya</a>
                @endif
                @if ($nextStep && ($activeSection !== 'project' || $setupProject))
                    <a href="{{ $stepUrl($nextStep) }}" class="btn btn-primary" data-setup-next>Next: {{ $setupSteps[$nextStep] }} <i class="bx bx-right-arrow-alt ms-1" aria-hidden="true"></i></a>
                @elseif ($setupProject && ! $nextStep)
                    <a href="{{ route('dashboard.projects.map', $setupProject) }}" class="btn btn-success">Buka Dashboard <i class="bx bx-right-arrow-alt ms-1" aria-hidden="true"></i></a>
                @endif
                @if ($setupProject)
                    <a href="{{ route('projects.index') }}" class="btn btn-light">Daftar Project</a>
                @endif
            </div>
        </div>
    </div>
</div>
