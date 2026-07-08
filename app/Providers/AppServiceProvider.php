<?php

namespace App\Providers;

use App\Models\Sensor;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;


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

            $view->with('alertNotifications', $alerts);
        });
    }
}
