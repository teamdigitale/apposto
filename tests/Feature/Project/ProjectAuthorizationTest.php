<?php

use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\User;

test('join and leave are not available as GET', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user)
        ->get("/projects/{$project->id}/join/member")
        ->assertNotFound();

    $this->actingAs($user)
        ->get("/projects/{$project->id}/leave")
        ->assertMethodNotAllowed();
});

test('a regular member cannot change another member role', function () {
    $project = Project::factory()->create();
    $member = User::factory()->create();
    $other = User::factory()->create();

    $member->projects()->attach($project->id, ['role' => 'member']);
    $other->projects()->attach($project->id, ['role' => 'developer']);

    $this->actingAs($member)
        ->patch(route('projects.update-member-role', [$project, $other]), ['role' => 'manager'])
        ->assertForbidden();

    expect($other->projects()->first()->pivot->role)->toBe('developer');
});

test('a regular member cannot update project resources', function () {
    $project = Project::factory()->create();
    $member = User::factory()->create();
    $member->projects()->attach($project->id, ['role' => 'member']);

    $this->actingAs($member)
        ->patch(route('projects.updateResources', $project), [
            'documentation_url' => 'https://example.com/docs',
        ])
        ->assertForbidden();
});

test('a project manager can see all pending project requests', function () {
    $pm = User::factory()->create(['is_project_manager' => true]);
    $project = Project::factory()->create();
    $applicant = User::factory()->create();

    ProjectRequest::create([
        'user_id' => $applicant->id,
        'project_id' => $project->id,
        'type' => 'join',
        'status' => 'pending',
        'role' => 'developer',
    ]);

    $this->actingAs($pm)
        ->get(route('projectRequests.index'))
        ->assertOk()
        ->assertSee($applicant->name)
        ->assertSee('Solo lettura');
});

test('a project manager cannot approve requests for projects they do not manage', function () {
    $pm = User::factory()->create(['is_project_manager' => true]);
    $project = Project::factory()->create();
    $applicant = User::factory()->create();

    $request = ProjectRequest::create([
        'user_id' => $applicant->id,
        'project_id' => $project->id,
        'type' => 'join',
        'status' => 'pending',
        'role' => 'developer',
    ]);

    $this->actingAs($pm)
        ->post(route('projectRequests.approve', $request))
        ->assertForbidden();
});

test('join request stores the selected role', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.requestJoin', $project), [
            'role' => 'developer',
            'message' => 'ciao',
        ])
        ->assertRedirect();

    $request = ProjectRequest::first();
    expect($request->role)->toBe('developer')
        ->and($request->type)->toBe('join')
        ->and($user->projects()->count())->toBe(0);
});
