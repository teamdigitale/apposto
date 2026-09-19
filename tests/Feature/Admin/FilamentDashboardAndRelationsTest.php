<?php

use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\RelationManagers\ProjectsRelationManager;
use App\Filament\Widgets\Newstats;
use App\Models\Admin;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

test('an admin can open the associated projects relation on a user', function () {
    $admin = Admin::factory()->create();
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Progetto Relazione']);

    $user->projects()->attach($project->id, ['role' => null]);

    $this->actingAs($admin, 'admin');

    Livewire::test(ProjectsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->assertSuccessful()
        ->assertSee('Progetto Relazione')
        ->assertSee('Progetti Associati');
});

test('the admin dashboard stats widget renders for an admin', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin');

    Livewire::test(Newstats::class)
        ->assertSuccessful()
        ->assertSee('Prenotazioni oggi')
        ->assertSee('Persone in ufficio')
        ->assertSee('Progetti attivi');
});
