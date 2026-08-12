@extends('layouts.master')

@section('title') Inbox @endsection

@section('css')
@include('modules.platform-operations.partials.styles')
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Client Operations @endslot
@slot('title') Inbox @endslot
@endcomponent

<div class="emp-ui">
@include('modules.platform-operations.partials.page-hero', [
    'eyebrow' => 'Client UI',
    'title' => 'Inbox',
    'subtitle' => 'Persistent operational and system notification history with source context and navigation to valid project, corridor, or station pages.',
    'steps' => [
        ['label' => 'Bell', 'icon' => 'bx bx-bell'],
        ['label' => 'Unread Count', 'icon' => 'bx bx-badge'],
        ['label' => 'Inbox', 'icon' => 'bx bx-envelope', 'active' => true],
        ['label' => 'Notification Detail', 'icon' => 'bx bx-detail'],
    ],
])

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0">Notification History</h4>
            <span class="text-muted small">Operational and system notifications</span>
        </div>
        <div class="table-responsive">
            <table class="table table-nowrap align-middle ops-table mb-0">
                <thead class="table-light">
                    <tr><th>Status</th><th>Category</th><th>Notification</th><th>Context</th><th>Timestamp</th></tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notification)
                        <tr>
                            <td>@include('modules.platform-operations.partials.status-badge', ['status' => $notification->read_at ? 'Healthy' : 'Warning', 'label' => $notification->read_at ? 'Read' : 'Unread'])</td>
                            <td>{{ $notification->category }}</td>
                            <td>
                                <a class="fw-semibold" href="{{ route('client-operations.inbox.show', $notification) }}">{{ $notification->title }}</a>
                                <div class="text-muted small">{{ $notification->event_type }}</div>
                            </td>
                            <td>{{ $notification->project?->project_code ?? '-' }} {{ $notification->corridor?->corridor_code ?? $notification->monitoringStation?->station_code }}</td>
                            <td>{{ optional($notification->occurred_at ?? $notification->created_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No notifications.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $notifications->links() }}</div>
    </div>
</div>
</div>
@endsection
