<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Admin;
use Illuminate\Contracts\Auth\Authenticatable;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\URL;
use italia\DesignLaravelTheme\Events\BuildingMenu;
use Illuminate\Pagination\Paginator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Observers\BookingObserver;
use App\Helpers\Holidays;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Authenticatable::class, Admin::class);
    }


    public function boot()
    {
        Booking::observe(BookingObserver::class);

        Blade::if('holiday', function ($date) {
            return Holidays::isHoliday($date);
        });

        Paginator::useBootstrap();
        
        if (env('APP_FORCE_HTTPS', false) || app()->environment('production')) {
            \URL::forceScheme('https');
        }

        app(Dispatcher::class)->listen(BuildingMenu::class, function (BuildingMenu $event) {
            // Note: removed dynamic property assignment ($event->address) deprecated in PHP 8.2+
            if (!Auth::check()) {
                $event->menu->header_menu = [];
            }

            if (Auth::user() ){
                if(Auth::user()->gestiamopresenze) {
                    $event->menu->header_menu[] = [
                        "url" => '/presences',
                        "text" => 'Timesheet',
                        "active" => 0,
                    ];
                }

                if (Auth::user()->superuser) {
                    $event->menu->header_menu[] = [
                        "url" => '/presences/overview',
                        "text" => 'Resoconto',
                        "active" => 0,
                    ];
                }

                $event->menu->header_menu[] = [
                        "url" => '/projects',
                        "text" => 'Progetti',
                        "active" => 0,
                    ];
                $event->menu->header_menu[] = [
                        "url" => '/absences/dashboard',
                        "text" => 'Resonto Ferie',
                        "active" => 0,
                    ];
            }
        });

        
    }
}
