<?php

namespace App\Http\Controllers;

use App\Helpers\Holidays;
use Illuminate\Http\Request;
use App\Models\Presence;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    protected $user;
    protected $timezone;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->timezone = config('app.timezone', 'Europe/Rome');
    }

    public function index()
    {
        $presences = $this->user->presences;

        $events = $presences->map(fn($presence) => [
            'title' => ucfirst(str_replace('_', ' ', $presence->status)),
            'start' => $presence->date,
            'status' => $presence->status,
        ]);

        $yearsToLoad = [now()->year - 1, now()->year, now()->year + 1];
        $holidayEvents = [];

        foreach ($yearsToLoad as $year) {
            foreach (Holidays::italianHolidays($year) as $date => $name) {
                $holidayEvents[] = [
                    'title' => $name,
                    'start' => $date,
                    'display' => 'background',
                    'backgroundColor' => '#f8d7da',
                    'borderColor' => '#f5c2c7',
                    'textColor' => '#842029'
                ];
            }
        }

        // Ottieni progetti dell'utente
        $userProjects = $this->user->projects()->pluck('projects.id');
        
        // Ottieni colleghi negli stessi progetti
        $colleagues = \App\Models\User::whereHas('projects', function($query) use ($userProjects) {
            $query->whereIn('projects.id', $userProjects);
        })
        ->where('id', '!=', $this->user->id)
        ->with(['presences' => function($query) {
            $query->whereIn('status', ['ferie', 'permesso'])
                  ->where('date', '>=', now()->subMonths(1)->format('Y-m-d'))
                  ->where('date', '<=', now()->addMonths(3)->format('Y-m-d'));
        }])
        ->get();
        
        // Crea eventi per colleghi in ferie
        $colleagueEvents = [];
        foreach ($colleagues as $colleague) {
            foreach ($colleague->presences as $presence) {
                $colleagueEvents[] = [
                    'title' => "🚫 {$colleague->name}",
                    'start' => $presence->date,
                    'backgroundColor' => '#6c757d',
                    'borderColor' => '#495057',
                    'textColor' => '#fff',
                    'display' => 'list-item',
                    'extendedProps' => [
                        'type' => 'colleague',
                        'userName' => $colleague->name,
                        'status' => $presence->status,
                    ]
                ];
            }
        }

        return view('presences.index', [
            'presences' => $presences,
            'events' => $events,
            'holidays' => $holidayEvents,
            'colleagueEvents' => $colleagueEvents,
        ]);
    }

    /**
     * API: Statistiche presenze utente
     */
    public function getStats(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        // Conta per tipo (mese corrente)
        $stats = Presence::where('user_id', $this->user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
        
        // Calcola trend ultimi 6 mesi
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthStart = $monthDate->copy()->startOfMonth();
            $monthEnd = $monthDate->copy()->endOfMonth();
            
            $monthStats = Presence::where('user_id', $this->user->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            $trend[] = [
                'month' => $monthDate->format('M Y'),
                'ferie' => $monthStats['ferie'] ?? 0,
                'smart_working' => $monthStats['smart_working'] ?? 0,
                'permesso' => $monthStats['permesso'] ?? 0,
                'presente' => $monthStats['presente'] ?? 0,
            ];
        }
        
        // Calcola statistiche anno corrente
        $yearStart = Carbon::create($year, 1, 1);
        $yearEnd = Carbon::create($year, 12, 31);
        
        $yearStats = Presence::where('user_id', $this->user->id)
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
        
        $totalDays = array_sum($yearStats);
        $percentages = [];
        foreach ($yearStats as $status => $count) {
            $percentages[$status] = $totalDays > 0 ? round(($count / $totalDays) * 100, 1) : 0;
        }
        
        return response()->json([
            'current_month' => $stats,
            'trend' => $trend,
            'year_stats' => $yearStats,
            'percentages' => $percentages,
            'ferie_info' => [
                'totali' => $this->user->ferie_totali,
                'usate' => $this->user->ferie_usate,
                'disponibili' => $this->user->remaining_leave_days,
            ]
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:presente,ferie,smart_working,permesso',
        ]);
    
        $user = $this->user;
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        
        // Genera tutte le date nel range (esclusi weekend e festivi)
        $dates = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // Solo giorni lavorativi e non festivi
            if ($currentDate->isWeekday() && !Holidays::isHoliday($currentDate)) {
                $dates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }
        
        if (empty($dates)) {
            return response()->json([
                'message' => 'Nessun giorno lavorativo nel periodo selezionato',
                'dates' => []
            ], 422);
        }

        DB::transaction(function () use ($dates, $validated, $user) {
            $timezone = config('app.timezone', 'Europe/Rome');
            $start_time = "07:30";
            $end_time = "21:00";
            
            foreach ($dates as $date) {
                // Crea/Aggiorna presenza
                Presence::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $date],
                    ['status' => $validated['status']]
                );
        
                if ($validated['status'] === 'presente') {
                    // Crea booking
                    Booking::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'start_date' => $date,
                        ],
                        [
                            'desk_id' => $user->default_workstation_id,
                            'end_date' => $date,
                            'start_time' => $start_time,
                            'end_time' => $end_time,
                            'from_date' => Carbon::parse("$date $start_time", $timezone),
                            'to_date' => Carbon::parse("$date $end_time", $timezone),
                            'status' => 0,
                        ]
                    );
                } else {
                    // Cancella booking
                    Booking::where('user_id', $user->id)
                        ->where('start_date', $date)
                        ->update(['status' => 1]);
                }
            }
        });

        return response()->json([
            'message' => 'Presenze salvate con successo!',
            'dates' => $dates,
            'count' => count($dates)
        ]);
    }

}