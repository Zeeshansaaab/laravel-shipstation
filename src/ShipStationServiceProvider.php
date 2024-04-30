<?php

namespace Zeeshan\LaravelShipStation;

use Illuminate\Support\ServiceProvider;

class ShipStationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('shipstation.php'),
        ], 'shipstation');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ShipStation::class, function ($app) {
            return new ShipStation(
                config('shipstation.apiKey'),
                config('shipstation.apiSecret')
            );
        });

        $this->app->bind('shipstation', function ($app) {
            return new \Zeeshan\LaravelShipStation\ShipStation();
        });
    }
}
