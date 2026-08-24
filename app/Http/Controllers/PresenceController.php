<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Helpers\Holidays;
use App\Http\Requests\StorePresenceRequest;
use App\Models\Booking;
use App\Models\Presence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $projectFilter = $request->input('project_filter');
        $rangeStart = now()->subYear()->startOfYear()->toDateString();
        $rangeEnd = now()->addYear()->endOfYear()->toDateString();

        $presences = $user->presences()
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->with(['workplace', 'booking.desk.plan.workplace'])
            ->get();

        $events = $presences->map(fn ($presence) => $this->ownEvent($presence));

        $holidayEvents = [];
        foreach ([now()->year - 1, now()->year, now()->year + 1] as $year) {
            foreach (Holidays::italianHolidays($year) as $date => $name) {
                $holidayEvents[] = [
                    'title' => $name,
                    'start' => $date,
                    'display' => 'background',
                    'backgroundColor' => '#f8d7da',
                    'borderColor' => '#f5c2c7',
                    'textColor' => '#842029',
                ];
            }
        }

        $validFilter = null;
        if ($projectFilter) {
            $pid = (int) $projectFilter;
            $validFilter = $user->visibleProjects()->where('projects.id', $pid)->exists() ? $pid : null;
        }

        $colleaguesQuery = User::where('id', '!=', $user->id);

        if ($validFilter) {
            $colleaguesQuery->whereHas('projects', fn ($q) => $q->where('projects.id', $validFilter));
        } elseif (! $user->hasFullFrontendVisibility()) {
            if ($user->team_id) {
                $colleaguesQuery->where('team_id', $user->team_id);
            } else {
                $colleaguesQuery->where('allow_view', true);
            }
        }

        $colleagues = $colleaguesQuery
            ->with([
                'presences' => function ($query) use ($rangeStart, $rangeEnd) {
                    $query->whereBetween('date', [$rangeStart, $rangeEnd])
                        ->with(['workplace', 'booking.desk.plan.workplace']);
                },
                'projects' => fn ($query) => $query->withPivot('role'),
            ])
            ->get();

        $userProjectIds = $user->projects()->pluck('projects.id');
        $byDate = [];

        foreach ($colleagues as $colleague) {
            if ($validFilter) {
                $proj = $colleague->projects->firstWhere('id', $validFilter);
            } else {
                $proj = $colleague->projects->first(fn ($p) => $userProjectIds->contains($p->id))
                    ?? $colleague->projects->first();
            }
            $role = $proj ? ($proj->pivot->role ?? 'member') : '—';

            foreach ($colleague->presences as $presence) {
                $dateStr = $presence->date;
                $status = $presence->status;
                $bucket = ($status === 'permesso') ? 'ferie' : $status;

                $workplace = ($status === 'presente')
                    ? ($presence->workplace ?? $presence->booking?->desk?->plan?->workplace)
                    : null;

                $byDate[$dateStr][$bucket][] = [
                    'name' => $colleague->name,
                    'status' => $status,
                    'role' => $role,
                    'workplaceName' => $workplace?->name,
                    'workplaceAddr' => $workplace?->address,
                ];
            }
        }

        $colleagueEvents = [];
        $bucketConfig = [
            'ferie' => ['label' => '🚫 Assenti', 'bgColor' => '#ffc107', 'borderColor' => '#e0a800', 'textColor' => '#000'],
            'smart_working' => ['label' => '💻 SW', 'bgColor' => '#17a2b8', 'borderColor' => '#138496', 'textColor' => '#fff'],
            'presente' => ['label' => '🏢 Presenti', 'bgColor' => '#28a745', 'borderColor' => '#1e7e34', 'textColor' => '#fff'],
        ];

        foreach ($byDate as $dateStr => $buckets) {
            foreach ($buckets as $bucket => $users) {
                $config = $bucketConfig[$bucket] ?? ['label' => '👥', 'bgColor' => '#6c757d', 'borderColor' => '#5a6268', 'textColor' => '#fff'];
                $colleagueEvents[] = [
                    'title' => $config['label'].' '.count($users),
                    'start' => $dateStr,
                    'backgroundColor' => $config['bgColor'],
                    'borderColor' => $config['borderColor'],
                    'textColor' => $config['textColor'],
                    'display' => 'block',
                    'extendedProps' => [
                        'type' => 'colleague_group',
                        'bucket' => $bucket,
                        'count' => count($users),
                        'users' => $users,
                    ],
                ];
            }
        }

        $selectableProjects = $user->visibleProjects()->orderBy('name')->get();

        return view('presences.index', [
            'presences' => $presences,
            'events' => $events,
            'holidays' => $holidayEvents,
            'colleagueEvents' => $colleagueEvents,
            'projectFilter' => $validFilter,
            'selectableProjects' => $selectableProjects,
        ]);
    }

    public function getStats(Request $request)
    {
        $user = auth()->user();
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $trendStart = now()->subMonths(5)->startOfMonth();
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

        $from = collect([$trendStart, $yearStart, $startDate])->min();
        $to = collect([$endDate, $yearEnd])->max();

        $rows = Presence::where('user_id', $user->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['date', 'status']);

        $countByStatus = fn ($items) => $items->groupBy('status')->map->count()->all();

        $inRange = fn ($fromDate, $toDate) => $rows->filter(
            fn ($p) => Carbon::parse($p->date)->betweenIncluded($fromDate, $toDate)
        );

        $stats = $countByStatus($inRange($startDate, $endDate));

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthStats = $countByStatus($inRange($monthDate->copy()->startOfMonth(), $monthDate->copy()->endOfMonth()));
            $trend[] = [
                'month' => $monthDate->format('M Y'),
                'ferie' => $monthStats['ferie'] ?? 0,
                'smart_working' => $monthStats['smart_working'] ?? 0,
                'permesso' => $monthStats['permesso'] ?? 0,
                'presente' => $monthStats['presente'] ?? 0,
            ];
        }

        $yearStats = $countByStatus($inRange($yearStart, $yearEnd));
        $totalDays = array_sum($yearStats);
        $workDays = ($yearStats['presente'] ?? 0) + ($yearStats['smart_working'] ?? 0);
        $absenceDays = ($yearStats['ferie'] ?? 0) + ($yearStats['permesso'] ?? 0);
        $percentages = [];
        foreach ($yearStats as $status => $count) {
            $percentages[$status] = $totalDays > 0 ? round(($count / $totalDays) * 100, 1) : 0;
        }

        return response()->json([
            'current_month' => $stats,
            'trend' => $trend,
            'year_stats' => $yearStats,
            'percentages' => $percentages,
            'aggregated' => [
                'work_days' => $workDays,
                'absence_days' => $absenceDays,
                'work_percentage' => $totalDays > 0 ? round(($workDays / $totalDays) * 100, 1) : 0,
                'absence_percentage' => $totalDays > 0 ? round(($absenceDays / $totalDays) * 100, 1) : 0,
            ],
            'ferie_info' => [
                'totali' => $user->ferie_totali,
                'usate' => $user->ferie_usate,
                'disponibili' => $user->remaining_leave_days,
            ],
        ]);
    }

    public function store(StorePresenceRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if ($currentDate->isWeekday() && ! Holidays::isHoliday($currentDate)) {
                $dates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }

        if ($dates === []) {
            return response()->json(['message' => 'Nessun giorno lavorativo nel periodo selezionato', 'dates' => []], 422);
        }

        DB::transaction(function () use ($dates, $validated, $user) {
            $timezone = config('app.timezone', 'Europe/Rome');
            $startTime = '07:30';
            $endTime = '21:00';

            foreach ($dates as $date) {
                $booking = null;

                if ($validated['status'] === 'presente' && $user->default_workstation_id) {
                    $booking = Booking::updateOrCreate(
                        ['user_id' => $user->id, 'start_date' => $date],
                        [
                            'desk_id' => $user->default_workstation_id,
                            'end_date' => $date,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'from_date' => Carbon::parse("$date $startTime", $timezone),
                            'to_date' => Carbon::parse("$date $endTime", $timezone),
                            'status' => BookingStatus::Confirmed->value,
                        ]
                    );
                } else {
                    Booking::where('user_id', $user->id)
                        ->where('start_date', $date)
                        ->update(['status' => BookingStatus::Cancelled->value]);
                }

                Presence::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $date],
                    [
                        'status' => $validated['status'],
                        'booking_id' => $booking?->id,
                        'workplace_id' => $validated['status'] === 'presente'
                            ? ($validated['workplace_id'] ?? null)
                            : null,
                    ]
                );
            }
        });

        return response()->json(['message' => 'Presenze salvate con successo!', 'dates' => $dates, 'count' => count($dates)]);
    }

    public function destroy(string $date)
    {
        if (Carbon::parse($date)->startOfDay()->lt(Carbon::today())) {
            return redirect()->route('presences.index')
                ->with('error', 'Non è possibile eliminare presenze di giorni precedenti ad oggi.');
        }

        $user = auth()->user();
        $presence = Presence::where('user_id', $user->id)->where('date', $date)->first();

        if (! $presence) {
            return redirect()->route('presences.index')->with('error', 'Presenza non trovata.');
        }

        $this->authorize('delete', $presence);

        $presence->delete();
        Booking::where('user_id', $user->id)
            ->where('start_date', $date)
            ->update(['status' => BookingStatus::Cancelled->value]);

        return redirect()->route('presences.index')
            ->with('success', 'Presenza del '.Carbon::parse($date)->format('d/m/Y').' eliminata!');
    }

    private function ownEvent(Presence $presence): array
    {
        switch ($presence->status) {
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

        $workplace = ($presence->status === 'presente')
            ? ($presence->workplace ?? $presence->booking?->desk?->plan?->workplace)
            : null;

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
                'icon' => $icon,
                'workplaceName' => $workplace?->name,
                'workplaceAddr' => $workplace?->address,
            ],
        ];
    }
}
