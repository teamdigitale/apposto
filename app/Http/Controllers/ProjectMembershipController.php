<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectMembershipController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $myProjects = $user->projects()
            ->withCount('users')
            ->withPivot('role', 'created_at')
            ->get();

        $availableProjects = Project::where('active', true)
            ->whereNotIn('id', $myProjects->pluck('id'))
            ->withCount('users')
            ->get();

        return view('projects.index', compact('myProjects', 'availableProjects'));
    }

    public function leave(Project $project)
    {
        $this->authorize('leave', $project);

        Auth::user()->projects()->detach($project->id);

        return back()->with('success', "Hai lasciato il progetto '{$project->name}'.");
    }

    public function updateResources(Request $request, Project $project)
    {
        $this->authorize('updateResources', $project);

        $validated = $request->validate([
            'slack_channel' => 'nullable|string|max:255',
            'drive_folder' => 'nullable|url',
            'documentation_url' => 'nullable|url',
            'resources_notes' => 'nullable|string',
        ]);

        $project->update($validated);

        return back()->with('success', 'Risorse progetto aggiornate!');
    }

    public function updateMemberRole(Request $request, Project $project, User $member)
    {
        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'role' => 'required|string|in:developer,designer,tester,product owner,scrum master,manager,member',
        ]);

        if (! $member->projects()->where('project_id', $project->id)->exists()) {
            return back()->with('error', 'L utente non è membro di questo progetto.');
        }

        $member->projects()->updateExistingPivot($project->id, [
            'role' => $validated['role'],
            'updated_at' => now(),
        ]);

        $val = $validated['role'] === 'member' ? '' : "di {$validated['role']}";

        return back()->with('success', "Ruolo di {$member->name} aggiornato a {$val}.");
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load(['users' => function ($query) {
            $query->withPivot('role', 'created_at');
        }]);

        $isMember = Auth::user()->projects()->where('project_id', $project->id)->exists();

        return view('projects.show', compact('project', 'isMember'));
    }

    public function create()
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    public function store(StoreProjectRequest $request)
    {
        $project = Project::create($request->validated());

        $request->user()->projects()->attach($project->id, [
            'role' => 'manager',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('projects.show', $project->id)
            ->with('success', "Progetto \"{$project->name}\" creato con successo!");
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    public function update(StoreProjectRequest $request, Project $project)
    {
        $project->update($request->validated());

        return redirect()->route('projects.show', $project->id)
            ->with('success', "Progetto \"{$project->name}\" aggiornato con successo!");
    }
}
