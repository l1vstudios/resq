@extends('layouts.master')

@section('title') Printable Report @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
<style>
    @media print {
        .vertical-menu, #page-topbar, .footer { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .card { border: 0; box-shadow: none; }
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Reporting & Export @endslot
@slot('title') Printable Report @endslot
@endcomponent

@php($rows = collect($report['rows'] ?? []))

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="card-title mb-1">{{ $report['target']['code'] }} - {{ $report['target']['name'] }}</h4>
                <div class="text-muted small">{{ ucfirst($report['target_type']) }} report from {{ $report['from']->format('d M Y H:i') }} to {{ $report['to']->format('d M Y H:i') }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">Print</button>
        </div>
        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light">
                    <tr><th>Station</th><th>Parameter</th><th>Value</th><th>Unit</th><th>Status</th><th>Alert</th><th>Received At</th></tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['station_code'] }}</td>
                            <td>{{ $row['parameter'] }}</td>
                            <td>{{ $row['value'] }}</td>
                            <td>{{ $row['unit'] }}</td>
                            <td>{{ $row['status'] }}</td>
                            <td>{{ $row['alert_level'] }}</td>
                            <td>{{ $row['received_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No data for the selected period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="text-muted small mt-3">Generated {{ $report['generated_at']->toISOString() }}. Query limited to {{ $report['bounded_limit'] }} rows.</div>
    </div>
</div>
@endsection
