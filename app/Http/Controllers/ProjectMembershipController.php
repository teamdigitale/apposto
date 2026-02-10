<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectMembershipController extends Controller
{
    /**
     * Mostra i progetti dell'utente e quelli disponibili
     */
    public function index()
    {
        $user = Auth::user();
        
        // Progetti a cui l'utente è già assegnato
        $myProjects = $user->projects()
            ->withCount('users')
            ->withPivot('role', 'created_at')
            ->get();
        
        // Progetti disponibili (attivi e non ancora parte)
        $availableProjects = Project::where('active', true)
            ->whereNotIn('id', $myProjects->pluck('id'))
            ->withCount('users')
            ->get();
        
        return view('projects.index', compact('myProjects', 'availableProjects'));
    }

    /**
     * Unisciti a un progetto
     */
    public function join(Request $request, Project $project)
    {
        // Laravel risolve automaticamente il model
        // Niente più findOrFail necessario
        
        if (!$project->active) {
            return back()->with('error', 'Progetto non attivo.');
        }
        
        $user->projects()->attach($project->id, [
            'role' => $validated['role'] ?? 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return back()->with('success', "Unito al progetto!");
    }

    /**
     * Lascia un progetto
     */
    public function leave($projectId)
    {
        $user = Auth::user();
        $project = Project::findOrFail($projectId);
            
        // Verifica che l'utente sia nel progetto
        if (!$user->projects()->where('project_id', $projectId)->exists()) {
            return back()->with('error', 'Non fai parte di questo progetto.');
        }
        
        // Lascia il progetto
        $user->projects()->detach($projectId);
        
        return back()->with('success', "Hai lasciato il progetto '{$project->name}'.");
    }

    /**
     * Aggiorna il ruolo dell'utente in un progetto
     */
    public function updateRole(Request $request, $projectId)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'role' => 'required|string|max:255',
        ]);
        
        // Verifica che l'utente sia nel progetto
        if (!$user->projects()->where('project_id', $projectId)->exists()) {
            return back()->with('error', 'Non fai parte di questo progetto.');
        }
        
        // Aggiorna il ruolo
        $user->projects()->updateExistingPivot($projectId, [
            'role' => $validated['role']
        ]);
        
        return back()->with('success', 'Ruolo aggiornato con successo!');
    }

    /**
     * Mostra i dettagli di un progetto e i suoi membri
     */
    public function show($projectId)
    {
        $project = Project::with(['users' => function($query) {
            $query->withPivot('role', 'created_at');
        }])->findOrFail($projectId);
        
        $user = Auth::user();
        $isMember = $user->projects()->where('project_id', $projectId)->exists();
        
        return view('projects.show', compact('project', 'isMember'));
    }
}