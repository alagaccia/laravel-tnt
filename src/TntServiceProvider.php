<?php
namespace AndreaLagaccia\Tnt;

use Illuminate\Support\ServiceProvider;

class TntServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tnt.php' => config_path('tnt.php'),
            ]);
        }
    }
}
