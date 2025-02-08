<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Newstats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make( 'Nuove Prenotazioni', Booking::where('status',1)->count())
            ->description('Prenotazioni attive')
            ->chart([7, 2, 10, 3, 15, 4, 17]),
            Stat::make( 'Numero Utenti attivi', User::count())
            ->description('Persone nel dipartimento'),
        ];
    }
}
