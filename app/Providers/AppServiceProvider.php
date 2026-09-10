<?php

namespace App\Providers;

use App\Models\Sensor;
use App\Models\SentinelNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        /*
        |--------------------------------------------------------------------------
        | Default String Length
        |--------------------------------------------------------------------------
        */
        Schema::defaultStringLength(191);

        /*
        |--------------------------------------------------------------------------
        | Force Application URL
        |--------------------------------------------------------------------------
        |
        | Semua url(), asset(), route(), dan URL Laravel akan mengikuti APP_URL
        | yang ada di .env.
        |
        */

        $appUrl = rtrim((string) config('app.url'), '/');

        if (!empty($appUrl)) {
            URL::forceRootUrl($appUrl);
        }

        /*
        |--------------------------------------------------------------------------
        | Force HTTPS
        |--------------------------------------------------------------------------
        */

        $forceHttps = filter_var(
            env('FORCE_HTTPS', false),
            FILTER_VALIDATE_BOOLEAN
        );

        if (
            $forceHttps ||
            str_starts_with($appUrl, 'https://')
        ) {
            URL::forceScheme('https');
        }

        /*
        |--------------------------------------------------------------------------
        | Topbar View Composer
        |--------------------------------------------------------------------------
        */

        View::composer('layouts.topbar', function ($view) {

            /*
            |--------------------------------------------------------------------------
            | Default Values
            |--------------------------------------------------------------------------
            */

            $alerts = collect();
            $inboxNotifications = collect();
            $inboxUnreadCount = 0;

            /*
            |--------------------------------------------------------------------------
            | Sensor Alert Notifications
            |--------------------------------------------------------------------------
            */

            if (Schema::hasTable('sensors')) {

                $alerts = Sensor::with([
                        'workspace',
                        'monitoringStation',
                        'warningStation',
                    ])
                    ->where(function ($query) {
                        $query
                            ->where('alert_level', 'Awas')
                            ->orWhere('status', 'Awas');
                    })
                    ->latest('last_seen_at')
                    ->limit(10)
                    ->get()
                    ->map(function (Sensor $sensor) {

                        return [
                            'sensor_id' => $sensor->sensor_code,
                            'type' => $sensor->type,
                            'parameter' => $sensor->parameter,
                            'value' => $sensor->value,
                            'threshold' => $sensor->threshold,

                            'alert_level' => $sensor->alert_level,
                            'status' => $sensor->status,

                            'province' => $sensor->workspace?->province,
                            'city' => $sensor->workspace?->city,

                            'station' =>
                                $sensor->monitoringStation?->station_code,

                            'warning_station' =>
                                $sensor->warningStation?->station_code,

                            'last_seen' =>
                                optional($sensor->last_seen_at)
                                    ->diffForHumans() ?? '-',
                        ];
                    });
            }

            /*
            |--------------------------------------------------------------------------
            | Logged In User
            |--------------------------------------------------------------------------
            */

            $user = auth()->user();

            /*
            |--------------------------------------------------------------------------
            | Sentinel Inbox Notifications
            |--------------------------------------------------------------------------
            */

            if (
                $user &&
                method_exists($user, 'isClientUser') &&
                $user->isClientUser() &&
                Schema::hasTable('sentinel_notifications')
            ) {

                $baseQuery = SentinelNotification::query()
                    ->where('client_id', $user->client_id)
                    ->where(function ($query) use ($user) {

                        $query
                            ->whereNull('user_id')
                            ->orWhere('user_id', $user->id);
                    });

                /*
                |--------------------------------------------------------------------------
                | Unread Count
                |--------------------------------------------------------------------------
                */

                $inboxUnreadCount = (clone $baseQuery)
                    ->whereNull('read_at')
                    ->count();

                /*
                |--------------------------------------------------------------------------
                | Latest Notifications
                |--------------------------------------------------------------------------
                */

                $inboxNotifications = $baseQuery
                    ->latest('occurred_at')
                    ->latest('id')
                    ->limit(5)
                    ->get();
            }

            /*
            |--------------------------------------------------------------------------
            | Send Data To Topbar
            |--------------------------------------------------------------------------
            */

            $view->with([
                'alertNotifications' => $alerts,
                'inboxNotifications' => $inboxNotifications,
                'inboxUnreadCount' => $inboxUnreadCount,
            ]);
        });
    }
}
