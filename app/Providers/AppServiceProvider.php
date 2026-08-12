<?php

namespace App\Providers;

use App\Models\Sensor;
use App\Models\SentinelNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        View::composer('layouts.topbar', function ($view) {
            $alerts = collect();
            $inboxNotifications = collect();
            $inboxUnreadCount = 0;

            if (Schema::hasTable('sensors')) {
                $alerts = Sensor::with(['workspace', 'monitoringStation', 'warningStation'])
                    ->where(function ($query) {
                        $query->where('alert_level', 'Awas')
                            ->orWhere('status', 'Awas');
                    })
                    ->latest('last_seen_at')
                    ->limit(10)
                    ->get()
                    ->map(fn (Sensor $sensor) => [
                        'sensor_id' => $sensor->sensor_code,
                        'type' => $sensor->type,
                        'parameter' => $sensor->parameter,
                        'value' => $sensor->value,
                        'threshold' => $sensor->threshold,
                        'alert_level' => $sensor->alert_level,
                        'status' => $sensor->status,
                        'province' => $sensor->workspace?->province,
                        'city' => $sensor->workspace?->city,
                        'station' => $sensor->monitoringStation?->station_code,
                        'warning_station' => $sensor->warningStation?->station_code,
                        'last_seen' => optional($sensor->last_seen_at)->diffForHumans() ?? '-',
                    ]);
            }

            $user = auth()->user();
            if ($user && $user->isClientUser() && Schema::hasTable('sentinel_notifications')) {
                $baseQuery = SentinelNotification::query()
                    ->where('client_id', $user->client_id)
                    ->where(function ($query) use ($user) {
                        $query->whereNull('user_id')->orWhere('user_id', $user->id);
                    });

                $inboxUnreadCount = (clone $baseQuery)->whereNull('read_at')->count();
                $inboxNotifications = $baseQuery
                    ->latest('occurred_at')
                    ->latest()
                    ->limit(5)
                    ->get();
            }

            $view->with([
                'alertNotifications' => $alerts,
                'inboxNotifications' => $inboxNotifications,
                'inboxUnreadCount' => $inboxUnreadCount,
            ]);
        });
    }
}
