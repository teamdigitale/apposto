<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Desk;
use App\Models\Presence;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Newstats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Carbon::today();
        $todayPresences = Presence::query()
            ->whereDate('date', $today)
            ->get(['status']);

        $presenti = $todayPresences->where('status', 'presente')->count();
        $smart = $todayPresences->where('status', 'smart_working')->count();
        $assenti = $todayPresences->whereIn('status', ['ferie', 'permesso'])->count();

        $bookingsToday = Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->count();

        $desks = Desk::query()->count();
        $pendingRequests = ProjectRequest::query()->pending()->count();
        $activeProjects = Project::query()->where('active', true)->count();
        $users = User::query()->count();

        return [
            Stat::make('Prenotazioni oggi', $bookingsToday)
                ->description($desks > 0
                    ? "{$desks} postazioni in anagrafica"
                    : 'Nessuna postazione configurata')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->chart($this->bookingTrend())
                ->color('primary'),
            Stat::make('Persone in ufficio', $presenti)
                ->description('Timesheet: presente in sede')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),
            Stat::make('Smart working', $smart)
                ->description('In lavoro da remoto oggi')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('info'),
            Stat::make('Assenti oggi', $assenti)
                ->description('Ferie e permessi')
                ->descriptionIcon('heroicon-m-sun')
                ->color($assenti > 0 ? 'warning' : 'gray'),
            Stat::make('Utenti attivi', $users)
                ->description('Persone nel dipartimento')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Progetti attivi', $activeProjects)
                ->description($pendingRequests === 1
                    ? '1 richiesta in attesa'
                    : "{$pendingRequests} richieste in attesa")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color($pendingRequests > 0 ? 'danger' : 'success'),
        ];
    }

    /**
     * @return list<int>
     */
    private function bookingTrend(): array
    {
        $from = Carbon::today()->subDays(6)->startOfDay();
        $to = Carbon::today()->endOfDay();

        $bookings = Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->where('from_date', '<=', $to)
            ->where('to_date', '>=', $from)
            ->get(['from_date', 'to_date']);

        $points = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $points[] = $bookings
                ->filter(fn (Booking $booking) => $booking->from_date?->lte($day->copy()->endOfDay())
                    && $booking->to_date?->gte($day->copy()->startOfDay()))
                ->count();
        }

        return $points;
    }
}
