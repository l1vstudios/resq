<?php

namespace App\Http\Controllers;

use App\Models\SentinelNotification;
use App\Services\AuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientInboxController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorization
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeClient($request);

        $notifications = SentinelNotification::with(['project', 'corridor', 'monitoringStation'])
            ->where('client_id', $request->user()->client_id)
            ->where(function ($query) use ($request) {
                $query->whereNull('user_id')->orWhere('user_id', $request->user()->id);
            })
            ->latest('occurred_at')
            ->latest()
            ->paginate(25);

        return view('modules.client-operations.inbox', [
            'notifications' => $notifications,
            'activeFilter' => $request->query('status'),
        ]);
    }

    public function show(Request $request, SentinelNotification $notification): View
    {
        $this->authorizeNotification($request, $notification);
        $notification->markRead();

        return view('modules.client-operations.inbox-detail', [
            'notification' => $notification->load(['project', 'corridor', 'monitoringStation', 'warningStation']),
            'contextUrl' => $this->contextUrl($notification),
        ]);
    }

    public function markRead(Request $request, SentinelNotification $notification): RedirectResponse
    {
        $this->authorizeNotification($request, $notification);
        $notification->markRead();

        return back()->with('message', 'Notification marked as read.');
    }

    private function authorizeClient(Request $request): void
    {
        abort_unless($request->user()?->isClientUser(), 403);
    }

    private function authorizeNotification(Request $request, SentinelNotification $notification): void
    {
        $this->authorizeClient($request);
        abort_unless((int) $notification->client_id === (int) $request->user()->client_id, 403);
        abort_if($notification->user_id && (int) $notification->user_id !== (int) $request->user()->id, 403);

        if ($notification->project_id) {
            abort_unless($this->authorization->canAccessProject($request->user(), $notification->project_id), 403);
        }
    }

    private function contextUrl(SentinelNotification $notification): ?string
    {
        if ($notification->monitoring_station_id) {
            return route('client-operations.stations.show', $notification->monitoring_station_id);
        }

        if ($notification->corridor_id && $notification->project_id) {
            return route('client-operations.corridors.show', [$notification->project_id, $notification->corridor_id]);
        }

        if ($notification->project_id) {
            return route('client-operations.projects.show', $notification->project_id);
        }

        return null;
    }
}
