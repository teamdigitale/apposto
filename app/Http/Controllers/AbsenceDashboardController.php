<?php

namespace App\Http\Controllers;

use App\Models\Presence;
use App\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class AbsenceDashboardController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAnyAbsences', Project::class);

        $user = auth()->user();
        $projectFilter = $request->input('project_id', null);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $validated = validator([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ])->validate();

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalWorkDays = $this->countWorkDays($startDate, $endDate);
        $period = CarbonPeriod::create($startDate, $endDate);

        $baseQuery = $user->visibleProjects();
        $allUserProjects = (clone $baseQuery)->orderBy('name')->get();

        $validFilter = null;
        if ($projectFilter) {
            $pid = (int) $projectFilter;
            $validFilter = $allUserProjects->firstWhere('id', $pid)?->id;
        }

        $projectsQuery = (clone $baseQuery)
            ->withCount('users')
            ->with(['users.projects' => fn ($q) => $q->withPivot('role')]);

        if ($validFilter) {
            $projectsQuery->where('projects.id', $validFilter);
        }

        $projects = $projectsQuery->get();
        $projectFilter = $validFilter;

        $memberIds = $projects->flatMap(fn ($p) => $p->users->pluck('id'))->unique()->values();
        $presences = Presence::whereIn('user_id', $memberIds)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('user:id,name')
            ->get();
        $presencesByUser = $presences->groupBy('user_id');

        $globalStats = [
            'total_projects' => $projects->count(),
            'total_users' => $projects->sum('users_count'),
            'total_absence_days' => 0,
            'avg_absence_percentage' => 0,
            'most_affected_project' => null,
            'least_affected_project' => null,
        ];

        $projectAbsences = [];

        foreach ($projects as $project) {
            $totalMembers = $project->users_count;
            if ($totalMembers == 0) {
                continue;
            }

            $projectMemberIds = $project->users->pluck('id');
            $projectPresences = $presences->whereIn('user_id', $projectMemberIds);
            $presencesByDate = $projectPresences->groupBy('date');
            $presencesByStatus = $projectPresences->groupBy('status');

            $absencesByDate = [];
            $criticalDates = [];

            foreach ($period as $date) {
                if (! $date->isWeekday()) {
                    continue;
                }

                $dateStr = $date->format('Y-m-d');
                $dayPresences = $presencesByDate->get($dateStr, collect());
                $absentCount = $dayPresences->whereIn('status', ['ferie', 'permesso'])->count();

                if ($absentCount > 0) {
                    $percentage = round(($absentCount / $totalMembers) * 100, 1);
                    $absentUsers = $dayPresences->whereIn('status', ['ferie', 'permesso'])
                        ->map(fn ($p) => [
                            'name' => $p->user->name,
                            'status' => $p->status,
                        ])->toArray();

                    $absencesByDate[$dateStr] = [
                        'count' => $absentCount,
                        'percentage' => $percentage,
                        'users' => $absentUsers,
                    ];

                    if ($percentage > 50) {
                        $criticalDates[] = $dateStr;
                    }
                }
            }

            $statusBreakdown = [
                'ferie' => $presencesByStatus->get('ferie', collect())->count(),
                'permesso' => $presencesByStatus->get('permesso', collect())->count(),
                'smart_working' => $presencesByStatus->get('smart_working', collect())->count(),
                'presente' => $presencesByStatus->get('presente', collect())->count(),
            ];

            $maxAbsence = 0;
            $avgAbsence = 0;
            $daysWithAbsences = 0;
            $totalAbsenceDays = $statusBreakdown['ferie'] + $statusBreakdown['permesso'];

            if ($absencesByDate !== []) {
                $percentages = array_column($absencesByDate, 'percentage');
                $maxAbsence = max($percentages);
                $avgAbsence = round(array_sum($percentages) / count($percentages), 1);
                $daysWithAbsences = count($absencesByDate);
            }

            $daysWithPresence = $presencesByDate->filter(
                fn ($dayPresences) => $dayPresences->where('status', 'presente')->count() > 0
            )->count();

            $coveragePercentage = $totalWorkDays > 0
                ? round(($daysWithPresence / $totalWorkDays) * 100, 1)
                : 0;

            $projectAbsences[] = [
                'project' => $project,
                'total_members' => $totalMembers,
                'absences_by_date' => $absencesByDate,
                'status_breakdown' => $statusBreakdown,
                'max_absence_percentage' => $maxAbsence,
                'avg_absence_percentage' => $avgAbsence,
                'days_with_absences' => $daysWithAbsences,
                'total_absence_days' => $totalAbsenceDays,
                'coverage_percentage' => $coveragePercentage,
                'critical_dates' => $criticalDates,
                'total_work_days' => $totalWorkDays,
                'risk_level' => $avgAbsence > 50 ? 'high' : ($avgAbsence > 30 ? 'medium' : 'low'),
            ];

            $globalStats['total_absence_days'] += $totalAbsenceDays;
        }

        usort($projectAbsences, fn ($a, $b) => $b['avg_absence_percentage'] <=> $a['avg_absence_percentage']);

        if ($projectAbsences !== []) {
            $globalStats['avg_absence_percentage'] = round(
                array_sum(array_column($projectAbsences, 'avg_absence_percentage')) / count($projectAbsences),
                1
            );
            $globalStats['most_affected_project'] = $projectAbsences[0] ?? null;
            $globalStats['least_affected_project'] = end($projectAbsences) ?: null;
        }

        $allMembers = $projects->flatMap->users->unique('id');
        $projectIds = $projects->pluck('id');
        $userAbsences = [];

        foreach ($allMembers as $member) {
            $memberPresences = $presencesByUser->get($member->id, collect());
            $memberProjects = $member->projects
                ->whereIn('id', $projectIds)
                ->values();

            $ferieCount = $memberPresences->where('status', 'ferie')->count();
            $permessoCount = $memberPresences->where('status', 'permesso')->count();
            $totalAbsences = $ferieCount + $permessoCount;

            $userAbsences[] = [
                'user' => $member,
                'projects' => $memberProjects,
                'stats' => [
                    'ferie' => $ferieCount,
                    'permesso' => $permessoCount,
                    'smart_working' => $memberPresences->where('status', 'smart_working')->count(),
                    'presente' => $memberPresences->where('status', 'presente')->count(),
                ],
                'total_absences' => $totalAbsences,
                'absence_percentage' => $totalWorkDays > 0
                    ? round(($totalAbsences / $totalWorkDays) * 100, 1)
                    : 0,
            ];
        }

        usort($userAbsences, fn ($a, $b) => $b['absence_percentage'] <=> $a['absence_percentage']);

        return view('absences.dashboard', compact(
            'projectAbsences',
            'userAbsences',
            'startDate',
            'endDate',
            'globalStats',
            'totalWorkDays',
            'allUserProjects',
            'projectFilter'
        ));
    }

    public function projectAbsences(Request $request, Project $project)
    {
        $this->authorize('viewAbsences', $project);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $project->loadCount('users')->load('users:id,name');
        $totalMembers = $project->users_count;

        if ($totalMembers == 0) {
            return response()->json([
                'project' => $project->name,
                'message' => 'Nessun membro nel progetto',
                'absences' => [],
            ]);
        }

        $memberIds = $project->users->pluck('id');
        $presences = Presence::whereIn('user_id', $memberIds)
            ->where('status', 'ferie')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('user:id,name')
            ->get()
            ->groupBy('date');

        $absencesByDate = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            if (! $date->isWeekday()) {
                continue;
            }

            $dateStr = $date->format('Y-m-d');
            $dayPresences = $presences->get($dateStr, collect());
            $absentCount = $dayPresences->count();

            $absencesByDate[] = [
                'date' => $dateStr,
                'date_formatted' => $date->format('d/m/Y'),
                'day_name' => $date->locale('it')->dayName,
                'absent_count' => $absentCount,
                'present_count' => $totalMembers - $absentCount,
                'absence_percentage' => round(($absentCount / $totalMembers) * 100, 1),
                'absent_users' => $dayPresences->map(fn ($presence) => [
                    'name' => $presence->user->name,
                    'date' => Carbon::parse($presence->date)->format('d/m/Y'),
                ])->toArray(),
            ];
        }

        return response()->json([
            'project' => $project->name,
            'total_members' => $totalMembers,
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y'),
            ],
            'absences' => $absencesByDate,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('exportAbsences', Project::class);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $projects = auth()->user()->visibleProjects()
            ->withCount('users')
            ->with('users:id')
            ->get();

        $filename = 'assenze_progetti_'.$startDate->format('Ymd').'_'.$endDate->format('Ymd').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $memberIds = $projects->flatMap(fn ($p) => $p->users->pluck('id'))->unique()->values();
        $presences = Presence::whereIn('user_id', $memberIds)
            ->where('status', 'ferie')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['user_id', 'date']);

        $callback = function () use ($projects, $startDate, $endDate, $presences) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Progetto', 'Data', 'Totale Membri', 'Assenti', 'Presenti', '% Assenza']);

            $period = CarbonPeriod::create($startDate, $endDate);

            foreach ($projects as $project) {
                $totalMembers = $project->users_count;
                if ($totalMembers == 0) {
                    continue;
                }

                $ids = $project->users->pluck('id');
                $projectPresences = $presences->whereIn('user_id', $ids)->groupBy('date');

                foreach ($period as $date) {
                    if (! $date->isWeekday()) {
                        continue;
                    }

                    $dateStr = $date->format('Y-m-d');
                    $absentCount = $projectPresences->get($dateStr, collect())->count();

                    if ($absentCount > 0) {
                        fputcsv($file, [
                            $project->name,
                            $date->format('d/m/Y'),
                            $totalMembers,
                            $absentCount,
                            $totalMembers - $absentCount,
                            round(($absentCount / $totalMembers) * 100, 1).'%',
                        ]);
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getChartData(Request $request)
    {
        $this->authorize('viewAnyAbsences', Project::class);

        $startDate = Carbon::parse($request->input('start_date', now()->format('Y-m-d')));
        $endDate = Carbon::parse($request->input('end_date', now()->addDays(30)->format('Y-m-d')));

        $projects = auth()->user()->visibleProjects()
            ->withCount('users')
            ->with('users:id')
            ->get();

        $allMemberIds = $projects->flatMap(fn ($p) => $p->users->pluck('id'))->unique()->values();

        $presences = Presence::whereIn('user_id', $allMemberIds)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['user_id', 'date', 'status']);

        $workDays = $this->countWorkDays($startDate, $endDate);
        $projectStats = [];

        foreach ($projects as $project) {
            if ($project->users_count == 0) {
                continue;
            }

            $memberIds = $project->users->pluck('id');
            $absentDays = $presences
                ->whereIn('user_id', $memberIds)
                ->where('status', 'ferie')
                ->count();

            $totalPossibleDays = $project->users_count * $workDays;
            $projectStats[] = [
                'name' => $project->name,
                'absent_days' => $absentDays,
                'percentage' => $totalPossibleDays > 0
                    ? round(($absentDays / $totalPossibleDays) * 100, 1)
                    : 0,
                'total_members' => $project->users_count,
            ];
        }

        usort($projectStats, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);
        $topProjects = array_slice($projectStats, 0, 5);

        $period = CarbonPeriod::create($startDate, $endDate);
        $timeline = [];
        $totalMembers = $projects->sum('users_count');
        $presencesByDate = $presences->where('status', 'ferie')->groupBy('date');

        foreach ($period as $date) {
            if (! $date->isWeekday()) {
                continue;
            }

            $dateStr = $date->format('Y-m-d');
            $totalAbsent = $presencesByDate->get($dateStr, collect())->count();

            $timeline[] = [
                'date' => $date->format('d/m'),
                'date_full' => $dateStr,
                'absent' => $totalAbsent,
                'percentage' => $totalMembers > 0
                    ? round(($totalAbsent / $totalMembers) * 100, 1)
                    : 0,
            ];
        }

        $typeStats = $presences->groupBy('status')->map->count()->all();

        return response()->json([
            'top_projects' => $topProjects,
            'timeline' => $timeline,
            'type_stats' => $typeStats,
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y'),
                'days' => $workDays,
            ],
        ]);
    }

    private function countWorkDays($startDate, $endDate): int
    {
        $count = 0;
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            if ($date->isWeekday()) {
                $count++;
            }
        }

        return $count;
    }
}
