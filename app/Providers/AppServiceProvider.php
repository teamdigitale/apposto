<?php

namespace App\Providers;

use App\Helpers\Holidays;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\User;
use App\Observers\BookingObserver;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use italia\DesignLaravelTheme\Events\BuildingMenu;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        Booking::observe(BookingObserver::class);

        Gate::before(function ($user) {
            if ($user instanceof Admin) {
                return true;
            }

            return null;
        });

        Blade::if('holiday', function ($date) {
            return Holidays::isHoliday($date);
        });

        Paginator::useBootstrap();

        if (config('app.force_https') || $this->app->environment('production')) {
            URL::forceScheme('https');
        }

        app(Dispatcher::class)->listen(BuildingMenu::class, function (BuildingMenu $event) {
            $event->address = '';
            $frontendUser = Auth::guard('web')->user();

            if (! $frontendUser instanceof User) {
                $event->menu->header_menu = [];
                $event->menu->slim_header_menu[0]['text'] = '';

                return;
            }

            $event->menu->slim_header_menu[0]['text'] = $frontendUser->name;

            $event->menu->header_menu[] = [
                'url' => '/presences',
                'text' => 'Timesheet',
                'active' => 0,
            ];

            if ($frontendUser->hasFullFrontendVisibility()) {
                $event->menu->header_menu[] = [
                    'url' => '/presences/overview',
                    'text' => 'Resoconto',
                    'active' => 0,
                ];

                $event->menu->header_menu[] = [
                    'url' => '/project-requests',
                    'text' => 'Richieste Progetti',
                    'active' => 0,
                    'icon' => 'bi-inbox',
                ];
            }

            $event->menu->header_menu[] = [
                'url' => '/projects',
                'text' => 'Progetti',
                'active' => 0,
            ];
            $event->menu->header_menu[] = [
                'url' => '/absences/dashboard',
                'text' => 'Resoconto Assenze',
                'active' => 0,
            ];
        });
    }
}
