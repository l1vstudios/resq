@extends('layouts.master')

@section('title') Function Configuration @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Client Operations @endslot
@slot('title') {{ $station->station_code }} Function Configuration @endslot
@endcomponent

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Client UI',
    'title' => $station->station_code.' Function Configuration',
    'subtitle' => 'Configure only Client-authorized function settings. Analytical internals and formulas remain backend/system-owned.',
    'steps' => [
        ['label' => 'Select Monitoring Station', 'icon' => 'bx bx-station'],
        ['label' => 'Capability', 'icon' => 'bx bx-check-shield'],
        ['label' => 'Function Configuration', 'icon' => 'bx bx-cog', 'active' => true],
    ],
])

@include('modules.client-operations.partials.function-configuration-panel', [
    'station' => $station,
    'context' => $context,
])
</div>
@endsection
