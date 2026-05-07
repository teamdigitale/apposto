<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectRequestController extends Controller
{
    /**
     * Richiesta di join al progetto
     */
    public function requestJoin(Request $request, Project $project)
    {
        $user = auth()->user();
        
        if ($user->projects()->where('project_id', $project->id)->exists()) {
            return back()->with('error', 'Sei già membro di questo progetto.');
        }
        
        $existing = ProjectRequest::where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->where('type', 'join')
            ->where('status', 'pending')
            ->exists();
            
        if ($existing) {
            return back()->with('warning', 'Hai già una richiesta in attesa per questo progetto.');
        }
        
        ProjectRequest::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'type'       => 'join',
            'role'       => 'member',
            'message'    => $request->input('message'),
        ]);
        
        return back()->with('success', 'Richiesta inviata! Attendi l\'approvazione.');
    }
    
    /**
     * Richiesta di leave dal progetto
     */
    public function requestLeave(Request $request, Project $project)
    {
        $user = auth()->user();
        
        if (!$user->projects()->where('project_id', $project->id)->exists()) {
            return back()->with('error', 'Non fai parte di questo progetto.');
        }
        
        $existing = ProjectRequest::where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->where('type', 'leave')
            ->where('status', 'pending')
            ->exists();
            
        if ($existing) {
            return back()->with('warning', 'Hai già una richiesta di uscita in attesa.');
        }
        
        ProjectRequest::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'type'       => 'leave',
            'message'    => $request->input('message'),
        ]);
        
        return back()->with('success', 'Richiesta di uscita inviata! Attendi conferma.');
    }
    
    /**
     * Lista richieste pending.
     *
     * - superuser   → vede TUTTE le richieste di tutti i progetti
     * - project_manager → vede SOLO le richieste dei progetti in cui ha ruolo 'manager'
     * - utente base → 403
     */
    public function index()
    {
        $user = auth()->user();
        
        if ($user->superuser) {
            $requests = ProjectRequest::with(['user', 'project'])
                ->pending()
                ->latest()
                ->get();

        } elseif ($user->is_project_manager) {
            // Progetti di cui l'utente è manager (ruolo 'manager' nella pivot)
            $managedProjectIds = $user->projects()
                ->wherePivot('role', 'manager')
                ->pluck('projects.id');

            if ($managedProjectIds->isEmpty()) {
                return view('project-requests.index', ['requests' => collect()])
                    ->with('info', 'Non sei ancora manager di nessun progetto.');
            }

            $requests = ProjectRequest::with(['user', 'project'])
                ->pending()
                ->whereIn('project_id', $managedProjectIds)
                ->latest()
                ->get();

        } else {
            abort(403, 'Accesso riservato agli amministratori di progetto.');
        }
        
        return view('project-requests.index', compact('requests'));
    }
    
    /**
     * Approva una richiesta.
     * Controllo: superuser oppure project manager del progetto specifico.
     */
    public function approve(ProjectRequest $projectRequest)
    {
        $this->authorizeProjectAction(auth()->user(), $projectRequest->project_id);
        
        if ($projectRequest->type === 'join') {
            $projectRequest->user->projects()->attach($projectRequest->project_id, [
                'role'       => $projectRequest->role ?? 'member',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $projectRequest->user->projects()->detach($projectRequest->project_id);
        }
        
        $projectRequest->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        
        return back()->with('success', 'Richiesta approvata!');
    }
    
    /**
     * Rifiuta una richiesta.
     * Stesso controllo autorizzazione di approve().
     */
    public function reject(Request $request, ProjectRequest $projectRequest)
    {
        $this->authorizeProjectAction(auth()->user(), $projectRequest->project_id);
        
        $projectRequest->update([
            'status'      => 'rejected',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        
        return back()->with('success', 'Richiesta rifiutata.');
    }

    /**
     * Verifica che l'utente abbia i permessi per agire su un progetto specifico.
     *
     * - superuser: sempre autorizzato
     * - is_project_manager: autorizzato solo se ha ruolo 'manager' nel progetto
     * - altrimenti: 403
     */
    private function authorizeProjectAction(User $user, int $projectId): void
    {
        if ($user->superuser) {
            return;
        }

        if ($user->is_project_manager) {
            $isManagerOfProject = $user->projects()
                ->wherePivot('role', 'manager')
                ->where('projects.id', $projectId)
                ->exists();

            if ($isManagerOfProject) {
                return;
            }
        }

        abort(403, 'Non hai i permessi per gestire le richieste di questo progetto.');
    }
}