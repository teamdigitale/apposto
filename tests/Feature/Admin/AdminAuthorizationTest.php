<?php

use App\Models\Admin;
use App\Models\Project;
use App\Models\User;

test('an admin can authorize filament project actions without hitting user policies', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin');

    expect($admin->can('viewAny', Project::class))->toBeTrue()
        ->and($admin->can('create', Project::class))->toBeTrue();
});

test('a frontend user is still subject to project policies', function () {
    $user = User::factory()->create(['is_project_manager' => false]);
    $project = Project::factory()->create();

    $this->actingAs($user);

    expect($user->can('viewAny', Project::class))->toBeTrue()
        ->and($user->can('create', Project::class))->toBeFalse()
        ->and($user->can('update', $project))->toBeFalse();
});
