@extends('layouts.master')

@section('title') Reporting & Export @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Client Operations @endslot
@slot('title') Reporting & Export @endslot
@endcomponent

@php
    $projects = collect($projects ?? []);
    $targetOptions = $targetOptions ?? ['corridors' => [], 'stations' => []];
@endphp

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Client UI',
    'title' => 'Reporting & Export',
    'subtitle' => 'Reusable report flow: select target, select data or parameter, select period, then generate printable, CSV, or Excel-compatible output.',
    'steps' => [
        ['label' => 'Select Target', 'icon' => 'bx bx-target-lock', 'active' => true],
        ['label' => 'Select Data', 'icon' => 'bx bx-data'],
        ['label' => 'Select Period', 'icon' => 'bx bx-calendar'],
        ['label' => 'Generate Output', 'icon' => 'bx bx-file'],
    ],
])

<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">Generate Report</h4>
        <form method="GET" action="{{ route('client-operations.reporting.generate') }}">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Select Target</label>
                    <select name="target_type" class="form-select" required>
                        <option value="corridor" @selected(($preselectedTargetType ?? '') === 'corridor')>Corridor Monitoring</option>
                        <option value="station" @selected(($preselectedTargetType ?? '') === 'station')>Monitoring Station</option>
                    </select>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Target</label>
                    <select name="target_id" class="form-select" required>
                        <optgroup label="Corridor Monitoring">
                            @foreach($targetOptions['corridors'] as $corridor)
                                <option value="{{ $corridor['id'] }}" @selected(($preselectedTargetType ?? '') === 'corridor' && (string) ($preselectedTargetId ?? '') === (string) $corridor['id'])>Corridor: {{ $corridor['code'] }} - {{ $corridor['name'] }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Monitoring Station">
                            @foreach($targetOptions['stations'] as $station)
                                <option value="{{ $station['id'] }}" @selected(($preselectedTargetType ?? '') === 'station' && (string) ($preselectedTargetId ?? '') === (string) $station['id'])>Station: {{ $station['code'] }} - {{ $station['name'] }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Data / Parameter</label>
                    <input name="parameter" type="text" class="form-control" placeholder="All parameters">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">From</label>
                    <input name="from" type="datetime-local" class="form-control" value="{{ now()->subDay()->format('Y-m-d\\TH:i') }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">To</label>
                    <input name="to" type="datetime-local" class="form-control" value="{{ now()->format('Y-m-d\\TH:i') }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Output</label>
                    <select name="output" class="form-select" required>
                        <option value="print">Printable Report</option>
                        <option value="csv">CSV</option>
                        <option value="excel">Excel-compatible Export</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Generate Output</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
