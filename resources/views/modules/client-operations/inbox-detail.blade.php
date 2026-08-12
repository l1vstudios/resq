@extends('layouts.master')

@section('title') Notification Detail @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Inbox @endslot
@slot('title') Notification Detail @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="card-title mb-1">{{ $notification->title }}</h4>
                <div class="text-muted small">{{ $notification->category }} / {{ $notification->event_type }}</div>
            </div>
            @include('modules.platform-operations.partials.status-badge', ['status' => $notification->read_at ? 'Healthy' : 'Warning', 'label' => $notification->read_at ? 'Read' : 'Unread'])
        </div>
        <p>{{ $notification->body }}</p>
        <div class="table-responsive mb-3">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <tbody>
                    <tr><th>Project</th><td>{{ $notification->project?->project_code ?? '-' }}</td></tr>
                    <tr><th>Corridor</th><td>{{ $notification->corridor?->corridor_code ?? '-' }}</td></tr>
                    <tr><th>Monitoring Station</th><td>{{ $notification->monitoringStation?->station_code ?? '-' }}</td></tr>
                    <tr><th>Warning Station</th><td>{{ $notification->warningStation?->station_code ?? '-' }}</td></tr>
                    <tr><th>Timestamp</th><td>{{ optional($notification->occurred_at ?? $notification->created_at)->toISOString() }}</td></tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('client-operations.inbox.index') }}" class="btn btn-outline-secondary">History</a>
            @if($contextUrl)
                <a href="{{ $contextUrl }}" class="btn btn-primary">Open Context</a>
            @endif
        </div>
    </div>
</div>
@endsection
