<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectRequest;
use Illuminate\Http\Request;

class ProjectRequestController extends Controller
{
    /**
     * Richiesta di join
     */
    public function requestJoin(Request $request, Project $project)
    {
        $user = auth()->user();
        
        // Verifica se è già nel progetto
        if ($user->projects()->where('project_id', $project->id)->exists()) {
            return back()->with('error', 'Sei già membro di questo progetto.');
        }
        
        // Verifica se ha già una richiesta pending
        $existing = ProjectRequest::where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->where('type', 'join')
            ->where('status', 'pending')
            ->exists();
            
        if ($existing) {
            return back()->with('warning', 'Hai già una richiesta in attesa per questo progetto.');
        }
        
        ProjectRequest::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'type' => 'join',
            'role' => 'member',
            'message' => $request->input('message'),
        ]);
        
        return back()->with('success', 'Richiesta inviata! Attendi l\'approvazione di un admin.');
    }
    
    /**
     * Richiesta di leave
     */
    public function requestLeave(Request $request, Project $project)
    {
        $user = auth()->user();
        
        // Verifica se è nel progetto
        if (!$user->projects()->where('project_id', $project->id)->exists()) {
            return back()->with('error', 'Non fai parte di questo progetto.');
        }
        
        // Verifica richiesta pending
        $existing = ProjectRequest::where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->where('type', 'leave')
            ->where('status', 'pending')
            ->exists();
            
        if ($existing) {
            return back()->with('warning', 'Hai già una richiesta di uscita in attesa.');
        }
        
        ProjectRequest::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'type' => 'leave',
            'message' => $request->input('message'),
        ]);
        
        return back()->with('success', 'Richiesta di uscita inviata! Attendi conferma admin.');
    }
    
    /**
     * Lista richieste pending (per admin/superuser)
     */
    public function index()
    {
        // Check superuser
        if (!auth()->user()->superuser) {
            abort(403, 'Accesso riservato agli amministratori');
        }
        
        $requests = ProjectRequest::with(['user', 'project'])
            ->pending()
            ->latest()
            ->get();
        
        return view('project-requests.index', compact('requests'));
    }
    
    /**
     * Approva richiesta
     */
    public function approve(ProjectRequest $projectRequest)
    {
        if (!auth()->user()->superuser) {
            abort(403, 'Accesso riservato agli amministratori');
        }
        
        if ($projectRequest->type === 'join') {
            // Aggiungi utente al progetto
            $projectRequest->user->projects()->attach($projectRequest->project_id, [
                'role' => $projectRequest->role ?? 'member',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // Rimuovi utente dal progetto
            $projectRequest->user->projects()->detach($projectRequest->project_id);
        }
        
        $projectRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        
        return back()->with('success', 'Richiesta approvata!');
    }
    
    /**
     * Rifiuta richiesta
     */
    public function reject(Request $request, ProjectRequest $projectRequest)
    {
        if (!auth()->user()->superuser) {
            abort(403, 'Accesso riservato agli amministratori');
        }
        
        $projectRequest->update([
            'status' => 'rejected',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        
        return back()->with('success', 'Richiesta rifiutata.');
    }
}