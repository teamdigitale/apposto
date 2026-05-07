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

    public function index(Request $request)
    {
        $projectFilter = $request->input('project_filter');
        
        $presencesQuery = $this->user->presences();
        
        $presences = $presencesQuery->get();

        $events = $presences->map(function($presence) {
            switch($presence->status) {
                case 'ferie':
                case 'permesso':
                    $icon = '🚫';
                    $title = 'A';
                    $bgColor = '#ffc107';
                    $borderColor = '#e0a800';
                    break;
                case 'smart_working':
                    $icon = '💻';
                    $title = 'SW';
                    $bgColor = '#17a2b8';
                    $borderColor = '#138496';
                    break;
                case 'presente':
                    $icon = '🏢';
                    $title = 'P';
                    $bgColor = '#28a745';
                    $borderColor = '#1e7e34';
                    break;
                default:
                    $icon = '';
                    $title = ucfirst($presence->status);
                    $bgColor = '#6c757d';
                    $borderColor = '#5a6268';
            }

            return [
                'title' => $title,
                'start' => $presence->date,
                'status' => $presence->status,
                'backgroundColor' => $bgColor,
                'borderColor' => $borderColor,
                'textColor' => '#fff',
                'extendedProps' => [
                    'type' => 'own',
                    'status' => $presence->status,
                    'icon' => $icon
                ]
            ];
        });

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

        // Ottieni tutti i progetti dell'utente (per il select del filtro)
        $userProjects = $this->user->projects()->pluck('projects.id');

        // Se c'è un filtro attivo, mostra solo i colleghi di quel progetto specifico
        // Altrimenti mostra i colleghi di tutti i progetti
        if ($projectFilter && $userProjects->contains((int) $projectFilter)) {
            $activeProjectIds = collect([(int) $projectFilter]);
        } else {
            $activeProjectIds = $userProjects;
            $projectFilter = null; // resetta se il valore non è valido
        }

        // Ottieni colleghi negli stessi progetti, con il loro ruolo per progetto
        // PUNTO 3: withPivot('role') caricato su projects per mostrare il ruolo
        $colleagues = \App\Models\User::whereHas('projects', function($query) use ($activeProjectIds) {
            $query->whereIn('projects.id', $activeProjectIds);
        })
        ->where('id', '!=', $this->user->id)
        ->with([
            'presences' => function($query) {
                $query->where('date', '>=', now()->startOfYear()->format('Y-m-d'))
                    ->where('date', '<=', now()->addYear()->endOfYear()->format('Y-m-d'));
            },
            // PUNTO 3: carica i progetti con il ruolo pivot per mostrarlo nel tooltip
            'projects' => function($query) use ($activeProjectIds) {
                $query->whereIn('projects.id', $activeProjectIds)
                    ->withPivot('role');
            }
        ])
        ->get();

        // ------------------------------------------------------------------
        // PUNTO 1: Raggruppa le presenze dei colleghi per data e per status
        // invece di creare un evento per ogni persona (che affollava il calendario),
        // creiamo UN evento aggregato per giorno per tipo di presenza.
        // ------------------------------------------------------------------
        $byDate = []; // [ 'YYYY-MM-DD' => [ 'ferie' => [...], 'smart_working' => [...], 'presente' => [...] ] ]

        foreach ($colleagues as $colleague) {
            // PUNTO 3: determina il ruolo del collega nel primo progetto comune
            $sharedProject = $colleague->projects->first();
            $role = $sharedProject ? ($sharedProject->pivot->role ?? 'member') : 'member';

            foreach ($colleague->presences as $presence) {
                $dateStr = $presence->date;
                $status  = $presence->status;

                // Normalizza ferie e permesso nello stesso bucket per il calendario
                $bucket = ($status === 'permesso') ? 'ferie' : $status;

                if (!isset($byDate[$dateStr][$bucket])) {
                    $byDate[$dateStr][$bucket] = [];
                }

                $byDate[$dateStr][$bucket][] = [
                    'name'   => $colleague->name,
                    'status' => $status,   // status reale (ferie/permesso/smart_working/presente)
                    'role'   => $role,
                ];
            }
        }

        // Costruisci gli eventi aggregati da passare a FullCalendar
        $colleagueEvents = [];

        $bucketConfig = [
            'ferie' => [
                'label'       => '🚫 Assenti',
                'bgColor'     => '#ffc107',
                'borderColor' => '#e0a800',
                'textColor'   => '#000',
            ],
            'smart_working' => [
                'label'       => '💻 SW',
                'bgColor'     => '#17a2b8',
                'borderColor' => '#138496',
                'textColor'   => '#fff',
            ],
            'presente' => [
                'label'       => '🏢 Presenti',
                'bgColor'     => '#28a745',
                'borderColor' => '#1e7e34',
                'textColor'   => '#fff',
            ],
        ];

        foreach ($byDate as $dateStr => $buckets) {
            foreach ($buckets as $bucket => $users) {
                $count  = count($users);
                $config = $bucketConfig[$bucket] ?? [
                    'label' => '👥',
                    'bgColor' => '#6c757d',
                    'borderColor' => '#5a6268',
                    'textColor' => '#fff',
                ];

                $colleagueEvents[] = [
                    // Mostra il conteggio: "🚫 Assenti 2"
                    'title'           => $config['label'] . ' ' . $count,
                    'start'           => $dateStr,
                    'backgroundColor' => $config['bgColor'],
                    'borderColor'     => $config['borderColor'],
                    'textColor'       => $config['textColor'],
                    'display'         => 'block',
                    'extendedProps'   => [
                        'type'        => 'colleague_group',
                        'bucket'      => $bucket,
                        'count'       => $count,
                        // Lista dettagliata per il modale (PUNTO 3: include ruolo)
                        'users'       => $users,
                    ],
                ];
            }
        }

        return view('presences.index', [
            'presences'       => $presences,
            'events'          => $events,
            'holidays'        => $holidayEvents,
            'colleagueEvents' => $colleagueEvents,
            'activeProjectIds' => $activeProjectIds, // per evidenziare il filtro attivo
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
        
        $stats = Presence::where('user_id', $this->user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
        
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate  = now()->subMonths($i);
            $monthStart = $monthDate->copy()->startOfMonth();
            $monthEnd   = $monthDate->copy()->endOfMonth();
            
            $monthStats = Presence::where('user_id', $this->user->id)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
            
            $trend[] = [
                'month'         => $monthDate->format('M Y'),
                'ferie'         => $monthStats['ferie'] ?? 0,
                'smart_working' => $monthStats['smart_working'] ?? 0,
                'permesso'      => $monthStats['permesso'] ?? 0,
                'presente'      => $monthStats['presente'] ?? 0,
            ];
        }
        
        $yearStart = Carbon::create($year, 1, 1);
        $yearEnd   = Carbon::create($year, 12, 31);
        
        $yearStats = Presence::where('user_id', $this->user->id)
            ->whereBetween('date', [$yearStart, $yearEnd])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
        
        $totalDays   = array_sum($yearStats);
        $percentages = [];
        foreach ($yearStats as $status => $count) {
            $percentages[$status] = $totalDays > 0 ? round(($count / $totalDays) * 100, 1) : 0;
        }
        
        $workDays    = ($yearStats['presente'] ?? 0) + ($yearStats['smart_working'] ?? 0);
        $absenceDays = ($yearStats['ferie'] ?? 0) + ($yearStats['permesso'] ?? 0);

        $workDaysPercentage    = $totalDays > 0 ? round(($workDays / $totalDays) * 100, 1) : 0;
        $absenceDaysPercentage = $totalDays > 0 ? round(($absenceDays / $totalDays) * 100, 1) : 0;

        return response()->json([
            'current_month' => $stats,
            'trend'         => $trend,
            'year_stats'    => $yearStats,
            'percentages'   => $percentages,
            'aggregated'    => [
                'work_days'          => $workDays,
                'absence_days'       => $absenceDays,
                'work_percentage'    => $workDaysPercentage,
                'absence_percentage' => $absenceDaysPercentage,
            ],
            'ferie_info' => [
                'totali'      => $this->user->ferie_totali,
                'usate'       => $this->user->ferie_usate,
                'disponibili' => $this->user->remaining_leave_days,
            ]
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'status'     => 'required|in:presente,ferie,smart_working',
        ]);
    
        $user = $this->user;
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate   = Carbon::parse($validated['end_date']);
        
        $dates       = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            if ($currentDate->isWeekday() && !Holidays::isHoliday($currentDate)) {
                $dates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }
        
        if (empty($dates)) {
            return response()->json([
                'message' => 'Nessun giorno lavorativo nel periodo selezionato',
                'dates'   => []
            ], 422);
        }

        DB::transaction(function () use ($dates, $validated, $user) {
            $timezone   = config('app.timezone', 'Europe/Rome');
            $start_time = "07:30";
            $end_time   = "21:00";
            
            foreach ($dates as $date) {
                Presence::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $date],
                    ['status'  => $validated['status']]
                );
        
                if ($validated['status'] === 'presente' && $user->default_workstation_id) {
                    Booking::updateOrCreate(
                        [
                            'user_id'    => $user->id,
                            'start_date' => $date,
                        ],
                        [
                            'desk_id'    => $user->default_workstation_id,
                            'end_date'   => $date,
                            'start_time' => $start_time,
                            'end_time'   => $end_time,
                            'from_date'  => Carbon::parse("$date $start_time", $timezone),
                            'to_date'    => Carbon::parse("$date $end_time", $timezone),
                            'status'     => 0,
                        ]
                    );
                } else {
                    Booking::where('user_id', $user->id)
                        ->where('start_date', $date)
                        ->update(['status' => 1]);
                }
            }
        });

        return response()->json([
            'message' => 'Presenze salvate con successo!',
            'dates'   => $dates,
            'count'   => count($dates)
        ]);
    }

    public function destroySimple($date)
    {
        $user = $this->user;
        
        $deleted = Presence::where('user_id', $user->id)
            ->where('date', $date)
            ->delete();
        
        if ($deleted) {
            Booking::where('user_id', $user->id)
                ->where('start_date', $date)
                ->update(['status' => 1]);
        }
        
        return redirect()->route('presences.index')
            ->with('success', 'Presenza del ' . \Carbon\Carbon::parse($date)->format('d/m/Y') . ' eliminata!');
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);
        
        $user = $this->user;
        $date = $validated['date'];
        
        $deleted = Presence::where('user_id', $user->id)
            ->where('date', $date)
            ->delete();
        
        if ($deleted) {
            Booking::where('user_id', $user->id)
                ->where('start_date', $date)
                ->update(['status' => 1]);
            
            return response()->json([
                'success' => true,
                'message' => 'Presenza eliminata con successo'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Presenza non trovata'
        ], 404);
    }
}