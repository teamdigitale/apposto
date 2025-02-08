<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Admin;
use Illuminate\Contracts\Auth\Authenticatable;

use Illuminate\Contracts\Events\Dispatcher;
use italia\DesignLaravelTheme\Events\BuildingMenu;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Authenticatable::class, Admin::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Dispatcher $events)
    {
        $events->listen(BuildingMenu::class, function (BuildingMenu $event) {
            $event->address = '';
            if(!Auth()->check()){

                
                //$this->header_menu
                $event->menu->header_menu = [];
            }
        });
    }
}
