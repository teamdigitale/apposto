<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageProjects();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->managesProject($project);
    }

    public function updateResources(User $user, Project $project): bool
    {
        return $user->managesProject($project);
    }

    public function leave(User $user, Project $project): bool
    {
        return $user->projects()->where('projects.id', $project->id)->exists();
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $user->managesProject($project);
    }

    public function viewAbsences(User $user, Project $project): bool
    {
        if ($user->hasFullFrontendVisibility()) {
            return true;
        }

        return $user->projects()->where('projects.id', $project->id)->exists();
    }

    public function viewAnyAbsences(User $user): bool
    {
        return true;
    }

    public function exportAbsences(User $user): bool
    {
        return $user->hasFullFrontendVisibility()
            || $user->projects()->exists();
    }
}
