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
        
        // Ottieni tutti i progetti attivi
        $projects = Project::where('active', true)
            ->withCount('users')
            ->get();
        
        // Calcola le assenze per ogni progetto
        $projectAbsences = [];
        
        foreach ($projects as $project) {
            $totalMembers = $project->users_count;
            
            if ($totalMembers == 0) {
                continue; // Salta progetti senza membri
            }
            
            // Ottieni gli ID dei membri del progetto
            $memberIds = $project->users->pluck('id')->toArray();
            
            // Ottieni tutte le presenze 'ferie' dei membri nel periodo
            $presences = Presence::whereIn('user_id', $memberIds)
                ->where('status', 'ferie')
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->with('user')
                ->get()
                ->groupBy('date'); // Raggruppa per data per performance
            
            // Calcola assenze per data
            $absencesByDate = [];
            $period = CarbonPeriod::create($startDate, $endDate);
            
            foreach ($period as $date) {
                // Salta weekend (opzionale, se vuoi)
                if (!$date->isWeekday()) {
                    continue;
                }
                
                $dateStr = $date->format('Y-m-d');
                
                // Conta quanti sono in ferie in questa data
                $dayPresences = $presences->get($dateStr, collect());
                $absentCount = $dayPresences->count();
                
                if ($absentCount > 0) {
                    $absentUsers = $dayPresences->pluck('user.name')->toArray();
                    
                    $absencesByDate[$dateStr] = [
                        'count' => $absentCount,
                        'percentage' => round(($absentCount / $totalMembers) * 100, 1),
                        'users' => $absentUsers,
                    ];
                }
            }
            
            // Calcola statistiche generali per il periodo
            $maxAbsence = 0;
            $avgAbsence = 0;
            $daysWithAbsences = 0;
            
            if (!empty($absencesByDate)) {
                $maxAbsence = max(array_column($absencesByDate, 'percentage'));
                $avgAbsence = round(array_sum(array_column($absencesByDate, 'percentage')) / count($absencesByDate), 1);
                $daysWithAbsences = count($absencesByDate);
            }
            
            $projectAbsences[] = [
                'project' => $project,
                'total_members' => $totalMembers,
                'absences_by_date' => $absencesByDate,
                'max_absence_percentage' => $maxAbsence,
                'avg_absence_percentage' => $avgAbsence,
                'days_with_absences' => $daysWithAbsences,
                'total_days' => $startDate->diffInDays($endDate) + 1,
            ];
        }
        
        // Ordina per percentuale media di assenza (decrescente)
        usort($projectAbsences, function($a, $b) {
            return $b['avg_absence_percentage'] <=> $a['avg_absence_percentage'];
        });
        
        return view('absences.dashboard', compact('projectAbsences', 'startDate', 'endDate'));
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
}