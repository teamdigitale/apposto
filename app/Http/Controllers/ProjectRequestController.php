<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectRequest;
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

        $validated = $request->validate([
            'message' => 'nullable|string|max:2000',
            'role' => 'nullable|string|in:developer,designer,tester,product owner,scrum master,manager,member',
        ]);

        ProjectRequest::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'type' => 'join',
            'role' => $validated['role'] ?? 'member',
            'message' => $validated['message'] ?? null,
        ]);

        return back()->with('success', 'Richiesta inviata! Attendi l\'approvazione.');
    }

    /**
     * Richiesta di leave dal progetto
     */
    public function requestLeave(Request $request, Project $project)
    {
        $user = auth()->user();

        if (! $user->projects()->where('project_id', $project->id)->exists()) {
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
            'user_id' => $user->id,
            'project_id' => $project->id,
            'type' => 'leave',
            'message' => $request->input('message'),
        ]);

        return back()->with('success', 'Richiesta di uscita inviata! Attendi conferma.');
    }

    /**
     * Lista richieste pending.
     *
     * superuser e project manager vedono tutte le richieste da frontend.
     * L'approvazione resta vincolata a manageMembers sul progetto.
     */
    public function index()
    {
        $user = auth()->user();

        abort_unless($user->hasFullFrontendVisibility(), 403, 'Accesso riservato agli amministratori di progetto.');

        $requests = ProjectRequest::with(['user', 'project'])
            ->pending()
            ->latest()
            ->get();

        return view('project-requests.index', compact('requests'));
    }

    /**
     * Approva una richiesta.
     * Controllo: superuser oppure project manager del progetto specifico.
     */
    public function approve(ProjectRequest $projectRequest)
    {
        $projectRequest->loadMissing('project');
        $this->authorize('manageMembers', $projectRequest->project);

        if ($projectRequest->type === 'join') {
            $projectRequest->user->projects()->attach($projectRequest->project_id, [
                'role' => $projectRequest->role ?? 'member',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
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
     * Rifiuta una richiesta.
     * Stesso controllo autorizzazione di approve().
     */
    public function reject(Request $request, ProjectRequest $projectRequest)
    {
        $projectRequest->loadMissing('project');
        $this->authorize('manageMembers', $projectRequest->project);

        $projectRequest->update([
            'status' => 'rejected',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Richiesta rifiutata.');
    }
}
