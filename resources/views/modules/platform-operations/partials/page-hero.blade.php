@php
    $eyebrow = $eyebrow ?? 'Sentinel EMP';
    $title = $title ?? 'Operational Workspace';
    $subtitle = $subtitle ?? null;
    $steps = collect($steps ?? []);
    $actions = collect($actions ?? []);
@endphp

<div class="emp-page-hero">
    <div class="d-flex flex-wrap justify-content-between gap-3">
        <div>
            <div class="emp-page-eyebrow">{{ $eyebrow }}</div>
            <h1 class="emp-page-title">{{ $title }}</h1>
            @if($subtitle)
                <p class="emp-page-subtitle">{{ $subtitle }}</p>
            @endif
        </div>

        @if($actions->isNotEmpty())
            <div class="emp-hero-actions align-self-start">
                @foreach($actions as $action)
                    <a href="{{ $action['url'] }}" class="btn btn-sm {{ $action['class'] ?? 'btn-outline-primary' }}">
                        @if(! empty($action['icon']))
                            <i class="{{ $action['icon'] }} me-1"></i>
                        @endif
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if($steps->isNotEmpty())
        <div class="emp-flow">
            @foreach($steps as $step)
                @php
                    $stepUrl = $step['url'] ?? null;
                    $stepClass = 'emp-flow-step ' . (! empty($step['active']) ? 'active' : '');
                @endphp
                @if($stepUrl)
                    <a href="{{ $stepUrl }}" class="{{ $stepClass }}">
                        @if(! empty($step['icon']))
                            <i class="{{ $step['icon'] }}"></i>
                        @endif
                        {{ $step['label'] }}
                    </a>
                @else
                    <span class="{{ $stepClass }}">
                        @if(! empty($step['icon']))
                            <i class="{{ $step['icon'] }}"></i>
                        @endif
                        {{ $step['label'] }}
                    </span>
                @endif
            @endforeach
        </div>
    @endif
</div>
