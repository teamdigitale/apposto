<?php

namespace App\Http\Controllers;

use App\Models\Presence;
use App\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbsenceDashboardController extends Controller
{
    /**
     * Mostra la dashboard con le assenze per progetto
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Date di default: prossimi 30 giorni
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->addDays(30)->format('Y-m-d'));
        
        // Validazione date
        $validated = validator([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ])->validate();
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        
        // Calcola giorni lavorativi nel periodo
        $totalWorkDays = 0;
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            if ($date->isWeekday()) {
                $totalWorkDays++;
            }
        }
        
        // ✅ CORRETTO: Ottieni solo i progetti dell'utente loggato
        $projects = $user->projects()
            ->where('active', true)
            ->withCount('users')
            ->with('users')
            ->get();
        
        // Statistiche globali
        $globalStats = [
            'total_projects' => $projects->count(),
            'total_users' => $projects->sum('users_count'),
            'total_absence_days' => 0,
            'avg_absence_percentage' => 0,
            'most_affected_project' => null,
            'least_affected_project' => null,
        ];
        
        // Calcola le assenze per ogni progetto
        $projectAbsences = [];
        
        foreach ($projects as $project) {
            $totalMembers = $project->users_count;
            
            if ($totalMembers == 0) {
                continue;
            }
            
            $memberIds = $project->users->pluck('id')->toArray();
            
            // Ottieni tutte le presenze (non solo ferie) dei membri nel periodo
            $presences = Presence::whereIn('user_id', $memberIds)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->with('user')
                ->get();
            
            // Raggruppa per data E status
            $presencesByDate = $presences->groupBy('date');
            $presencesByStatus = $presences->groupBy('status');
            
            // Calcola assenze per data
            $absencesByDate = [];
            $criticalDates = [];
            
            foreach ($period as $date) {
                if (!$date->isWeekday()) {
                    continue;
                }
                
                $dateStr = $date->format('Y-m-d');
                
                // Conta assenti (ferie + permessi)
                $dayPresences = $presencesByDate->get($dateStr, collect());
                $absentCount = $dayPresences->whereIn('status', ['ferie', 'permesso'])->count();
                
                if ($absentCount > 0) {
                    $percentage = round(($absentCount / $totalMembers) * 100, 1);
                    $absentUsers = $dayPresences->whereIn('status', ['ferie', 'permesso'])
                        ->map(function($p) {
                            return [
                                'name' => $p->user->name,
                                'status' => $p->status
                            ];
                        })->toArray();
                    
                    $absencesByDate[$dateStr] = [
                        'count' => $absentCount,
                        'percentage' => $percentage,
                        'users' => $absentUsers,
                    ];
                    
                    // Segna come critico se > 50%
                    if ($percentage > 50) {
                        $criticalDates[] = $dateStr;
                    }
                }
            }
            
            // Calcola statistiche per status
            $statusBreakdown = [
                'ferie' => $presencesByStatus->get('ferie', collect())->count(),
                'permesso' => $presencesByStatus->get('permesso', collect())->count(),
                'smart_working' => $presencesByStatus->get('smart_working', collect())->count(),
                'presente' => $presencesByStatus->get('presente', collect())->count(),
            ];
            
            // ✅ CORRETTO: Calcola statistiche generali
            $maxAbsence = 0;
            $avgAbsence = 0;
            $daysWithAbsences = 0;
            $totalAbsenceDays = $statusBreakdown['ferie'] + $statusBreakdown['permesso'];
            
            if (!empty($absencesByDate)) {
                $percentages = array_column($absencesByDate, 'percentage');
                $maxAbsence = max($percentages);
                $avgAbsence = round(array_sum($percentages) / count($percentages), 1);
                $daysWithAbsences = count($absencesByDate);
            }
            
            // Calcola coverage (giorni con almeno 1 persona presente)
            $daysWithPresence = $presencesByDate->filter(function($presences) {
                return $presences->where('status', 'presente')->count() > 0;
            })->count();
            
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
        
        // Ordina per percentuale media di assenza (decrescente)
        usort($projectAbsences, function($a, $b) {
            return $b['avg_absence_percentage'] <=> $a['avg_absence_percentage'];
        });
        
        // Aggiorna stats globali
        if (!empty($projectAbsences)) {
            $globalStats['avg_absence_percentage'] = round(
                array_sum(array_column($projectAbsences, 'avg_absence_percentage')) / count($projectAbsences),
                1
            );
            $globalStats['most_affected_project'] = $projectAbsences[0] ?? null;
            $globalStats['least_affected_project'] = end($projectAbsences) ?: null;
        }
        
        return view('absences.dashboard', compact('projectAbsences', 'startDate', 'endDate', 'globalStats', 'totalWorkDays'));
    }

    /**
     * API endpoint per ottenere assenze di un progetto specifico (JSON)
     */
    public function projectAbsences(Request $request, $projectId)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        
        $project = Project::withCount('users')->findOrFail($projectId);
        $totalMembers = $project->users_count;
        
        if ($totalMembers == 0) {
            return response()->json([
                'project' => $project->name,
                'message' => 'Nessun membro nel progetto',
                'absences' => []
            ]);
        }
        
        // Ottieni gli ID dei membri del progetto
        $memberIds = $project->users->pluck('id')->toArray();
        
        // Ottieni tutte le presenze 'ferie' dei membri nel periodo
        $presences = Presence::whereIn('user_id', $memberIds)
            ->where('status', 'ferie')
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with('user')
            ->get()
            ->groupBy('date');
        
        // Calcola per data
        $absencesByDate = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        
        foreach ($period as $date) {
            if (!$date->isWeekday()) {
                continue; // Salta weekend
            }
            
            $dateStr = $date->format('Y-m-d');
            $dayPresences = $presences->get($dateStr, collect());
            $absentCount = $dayPresences->count();
            
            $absentUsers = $dayPresences->map(function($presence) {
                return [
                    'name' => $presence->user->name,
                    'date' => Carbon::parse($presence->date)->format('d/m/Y'),
                ];
            })->toArray();
            
            $absencesByDate[] = [
                'date' => $dateStr,
                'date_formatted' => $date->format('d/m/Y'),
                'day_name' => $date->locale('it')->dayName,
                'absent_count' => $absentCount,
                'present_count' => $totalMembers - $absentCount,
                'absence_percentage' => round(($absentCount / $totalMembers) * 100, 1),
                'absent_users' => $absentUsers,
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

    /**
     * Esporta le assenze in formato CSV
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        
        $projects = Project::where('active', true)
            ->withCount('users')
            ->get();
        
        $filename = 'assenze_progetti_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($projects, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($file, ['Progetto', 'Data', 'Totale Membri', 'Assenti', 'Presenti', '% Assenza']);
            
            foreach ($projects as $project) {
                $totalMembers = $project->users_count;
                
                if ($totalMembers == 0) continue;
                
                // Ottieni gli ID dei membri del progetto
                $memberIds = $project->users->pluck('id')->toArray();
                
                // Ottieni tutte le presenze 'ferie' dei membri nel periodo
                $presences = Presence::whereIn('user_id', $memberIds)
                    ->where('status', 'ferie')
                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->get()
                    ->groupBy('date');
                
                $period = CarbonPeriod::create($startDate, $endDate);
                
                foreach ($period as $date) {
                    if (!$date->isWeekday()) continue;
                    
                    $dateStr = $date->format('Y-m-d');
                    $dayPresences = $presences->get($dateStr, collect());
                    $absentCount = $dayPresences->count();
                    
                    if ($absentCount > 0) {
                        $presentCount = $totalMembers - $absentCount;
                        $percentage = round(($absentCount / $totalMembers) * 100, 1);
                        
                        fputcsv($file, [
                            $project->name,
                            $date->format('d/m/Y'),
                            $totalMembers,
                            $absentCount,
                            $presentCount,
                            $percentage . '%'
                        ]);
                    }
                }
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * API: Dati per grafici dashboard assenze
     */
    public function getChartData(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', now()->format('Y-m-d')));
        $endDate = Carbon::parse($request->input('end_date', now()->addDays(30)->format('Y-m-d')));
        
        // Top 5 progetti con più assenze
        $projects = Project::where('active', true)
            ->withCount('users')
            ->with('users')
            ->get();
        
        $projectStats = [];
        foreach ($projects as $project) {
            if ($project->users_count == 0) continue;
            
            $memberIds = $project->users->pluck('id')->toArray();
            
            $absentDays = Presence::whereIn('user_id', $memberIds)
                ->where('status', 'ferie')
                ->whereBetween('date', [$startDate, $endDate])
                ->count();
            
            $totalPossibleDays = $project->users_count * $this->countWorkDays($startDate, $endDate);
            $absencePercentage = $totalPossibleDays > 0 
                ? round(($absentDays / $totalPossibleDays) * 100, 1) 
                : 0;
            
            $projectStats[] = [
                'name' => $project->name,
                'absent_days' => $absentDays,
                'percentage' => $absencePercentage,
                'total_members' => $project->users_count,
            ];
        }
        
        // Ordina per percentuale e prendi top 5
        usort($projectStats, fn($a, $b) => $b['percentage'] <=> $a['percentage']);
        $topProjects = array_slice($projectStats, 0, 5);
        
        // Timeline assenze per tutti i progetti
        $period = CarbonPeriod::create($startDate, $endDate);
        $timeline = [];
        
        foreach ($period as $date) {
            if (!$date->isWeekday()) continue;
            
            $dateStr = $date->format('Y-m-d');
            $totalAbsent = 0;
            $totalMembers = 0;
            
            foreach ($projects as $project) {
                if ($project->users_count == 0) continue;
                
                $memberIds = $project->users->pluck('id')->toArray();
                $totalMembers += $project->users_count;
                
                $absentCount = Presence::whereIn('user_id', $memberIds)
                    ->where('status', 'ferie')
                    ->where('date', $dateStr)
                    ->count();
                
                $totalAbsent += $absentCount;
            }
            
            $percentage = $totalMembers > 0 
                ? round(($totalAbsent / $totalMembers) * 100, 1) 
                : 0;
            
            $timeline[] = [
                'date' => $date->format('d/m'),
                'date_full' => $dateStr,
                'absent' => $totalAbsent,
                'percentage' => $percentage,
            ];
        }
        
        // Statistiche per tipo di assenza (tutti i progetti)
        $allMemberIds = $projects->flatMap(fn($p) => $p->users->pluck('id'))->unique()->toArray();
        
        $typeStats = Presence::whereIn('user_id', $allMemberIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
        
        return response()->json([
            'top_projects' => $topProjects,
            'timeline' => $timeline,
            'type_stats' => $typeStats,
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y'),
                'days' => $this->countWorkDays($startDate, $endDate),
            ]
        ]);
    }
    
    private function countWorkDays($startDate, $endDate)
    {
        $count = 0;
        $period = CarbonPeriod::create($startDate, $endDate);
        
        foreach ($period as $date) {
            if ($date->isWeekday()) {
                $count++;
            }
        }
        
        return $count;
    }
}