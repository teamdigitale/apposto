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
     * Unisciti a un progetto (metodo semplice con GET)
     */
    public function joinSimple(Project $project, $role)
    {
        $user = Auth::user();
        
        // Verifica che il progetto sia attivo
        if (!$project->active) {
            return redirect()->route('projects.index')->with('error', 'Questo progetto non è più attivo.');
        }
        
        // Verifica che l'utente non sia già nel progetto
        if ($user->projects()->where('project_id', $project->id)->exists()) {
            return redirect()->route('projects.index')->with('error', 'Sei già parte di questo progetto.');
        }
        
        // Validazione ruolo
        $validRoles = ['member', 'developer', 'designer', 'tester', 'manager'];
        if (!in_array($role, $validRoles)) {
            return redirect()->route('projects.index')->with('error', 'Ruolo non valido.');
        }
        
        try {
            // Unisciti al progetto
            $user->projects()->attach($project->id, [
                'role' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return redirect()->route('projects.index')->with('success', "Ti sei unito al progetto '{$project->name}' come {$role}!");
        } catch (\Exception $e) {
            \Log::error('Failed to join project', [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('projects.index')->with('error', 'Errore durante l\'iscrizione al progetto. Riprova.');
        }
    }

    /**
     * Unisciti a un progetto
     */
    public function join(Request $request, Project $project)
    {
        $user = Auth::user();
        
        // Verifica che il progetto sia attivo
        if (!$project->active) {
            return back()->with('error', 'Questo progetto non è più attivo.');
        }
        
        // Verifica che l'utente non sia già nel progetto
        if ($user->projects()->where('project_id', $project->id)->exists()) {
            return back()->with('error', 'Sei già parte di questo progetto.');
        }
        
        // Validazione ruolo (se fornito)
        $validated = $request->validate([
            'role' => 'nullable|string|max:255',
        ]);
        
        // Unisciti al progetto con ruolo di default 'member'
        try {
            $user->projects()->attach($project->id, [
                'role' => $validated['role'] ?? 'member',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return back()->with('success', "Ti sei unito al progetto '{$project->name}' con successo!");
        } catch (\Exception $e) {
            \Log::error('Failed to join project', [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Errore durante l\'iscrizione al progetto. Riprova.');
        }
    }

    /**
     * Abbandona un progetto (metodo semplice con GET)
     */
    public function leaveSimple(Project $project)
    {
        $user = Auth::user();
        
        // Verifica che l'utente sia effettivamente nel progetto
        if (!$user->projects()->where('project_id', $project->id)->exists()) {
            return redirect()->route('projects.index')->with('error', 'Non fai parte di questo progetto.');
        }
        
        try {
            // Rimuovi l'utente dal progetto
            $user->projects()->detach($project->id);
            
            return redirect()->route('projects.index')->with('success', "Hai lasciato il progetto '{$project->name}' con successo!");
        } catch (\Exception $e) {
            \Log::error('Failed to leave project', [
                'user_id' => $user->id,
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('projects.index')->with('error', 'Errore durante l\'abbandono del progetto. Riprova.');
        }
    }

    /**
     * Lascia un progetto
     */
    public function leave(Project $project)
    {
        $user = Auth::user();
            
        // Verifica che l'utente sia nel progetto
        if (!$user->projects()->where('project_id', $project->id)->exists()) {
            return back()->with('error', 'Non fai parte di questo progetto.');
        }
        
        // Lascia il progetto
        $user->projects()->detach($project->id);
        
        return back()->with('success', "Hai lasciato il progetto '{$project->name}'.");
    }

    /**
     * Aggiorna il ruolo dell'utente in un progetto
     */
    public function updateRole(Request $request, Project $project)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'role' => 'required|string|max:255',
        ]);
        
        // Verifica che l'utente sia nel progetto
        if (!$user->projects()->where('project_id', $project->id)->exists()) {
            return back()->with('error', 'Non fai parte di questo progetto.');
        }
        
        // Aggiorna il ruolo
        $user->projects()->updateExistingPivot($project->id, [
            'role' => $validated['role'],
            'updated_at' => now(),
        ]);
        
        return back()->with('success', 'Ruolo aggiornato con successo!');
    }

    /**
     * Mostra i dettagli di un progetto e i suoi membri
     */
    public function show(Project $project)
    {
        $project->load(['users' => function($query) {
            $query->withPivot('role', 'created_at');
        }]);
        
        $user = Auth::user();
        $isMember = $user->projects()->where('project_id', $project->id)->exists();
        
        return view('projects.show', compact('project', 'isMember'));
    }
}