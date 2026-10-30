<?php

use App\Models\Presence;
use App\Models\Project;
use App\Models\User;

test('presence delete is not available as GET', function () {
    $user = User::factory()->create();
    $date = now()->toDateString();

    Presence::factory()->create([
        'user_id' => $user->id,
        'date' => $date,
        'status' => 'presente',
    ]);

    $this->actingAs($user)
        ->get("/presences/{$date}/delete")
        ->assertNotFound();
});

test('a user can delete their own future presence with DELETE', function () {
    $user = User::factory()->create();
    $date = now()->addDay()->toDateString();

    Presence::factory()->create([
        'user_id' => $user->id,
        'date' => $date,
        'status' => 'presente',
    ]);

    $this->actingAs($user)
        ->delete(route('presences.destroy', $date))
        ->assertRedirect(route('presences.index'));

    expect(Presence::where('user_id', $user->id)->where('date', $date)->exists())->toBeFalse();
});

test('a user can delete their own future presence via JSON without leaving the calendar', function () {
    $user = User::factory()->create();
    $date = now()->addDay()->toDateString();

    Presence::factory()->create([
        'user_id' => $user->id,
        'date' => $date,
        'status' => 'presente',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('presences.destroy', $date))
        ->assertOk()
        ->assertJsonPath('date', $date);

    expect(Presence::where('user_id', $user->id)->where('date', $date)->exists())->toBeFalse();
});

test('a regular user cannot export absences of projects they do not belong to', function () {
    $outsider = User::factory()->create();
    Project::factory()->create();

    $this->actingAs($outsider)
        ->get(route('absences.export', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]))
        ->assertForbidden();
});

test('a project manager can access the presence overview', function () {
    $pm = User::factory()->create(['is_project_manager' => true]);
    $otherTeam = \App\Models\Team::factory()->create();
    $outsider = User::factory()->create(['team_id' => $otherTeam->id, 'name' => 'Collega Altro Team']);

    $this->actingAs($pm)
        ->get(route('presences.overview'))
        ->assertOk()
        ->assertSee('Collega Altro Team')
        ->assertSee('Team');
});

test('the presence overview defaults to the SSI team', function () {
    $ssi = \App\Models\Team::factory()->create(['label' => 'SSII - esperti']);
    $other = \App\Models\Team::factory()->create(['label' => 'Altro']);
    $pm = User::factory()->create([
        'is_project_manager' => true,
        'team_id' => $ssi->id,
        'name' => 'PM SSI',
    ]);
    User::factory()->create(['team_id' => $ssi->id, 'name' => 'Utente SSI']);
    User::factory()->create(['team_id' => $other->id, 'name' => 'Utente Altro']);

    $this->actingAs($pm)
        ->get(route('presences.overview'))
        ->assertOk()
        ->assertSee('Utente SSI')
        ->assertDontSee('Utente Altro');

    $this->actingAs($pm)
        ->get(route('presences.overview', ['team_filter' => '']))
        ->assertOk()
        ->assertSee('Utente SSI')
        ->assertSee('Utente Altro');

    $this->actingAs($pm)
        ->get(route('presences.overview', ['team_filter' => $other->id]))
        ->assertOk()
        ->assertSee('Utente Altro')
        ->assertDontSee('Utente SSI');
});

test('a regular user cannot access the presence overview', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('presences.overview'))
        ->assertForbidden();
});

test('a project manager timesheet includes colleagues from other teams', function () {
    $pm = User::factory()->create(['is_project_manager' => true]);
    $otherTeam = \App\Models\Team::factory()->create();
    $outsider = User::factory()->create(['team_id' => $otherTeam->id, 'name' => 'Visibile Al PM']);
    Presence::factory()->create([
        'user_id' => $outsider->id,
        'date' => now()->next('Wednesday')->toDateString(),
        'status' => 'ferie',
    ]);

    $this->actingAs($pm)
        ->get(route('presences.index'))
        ->assertOk()
        ->assertSee('Visibile Al PM');
});

test('a project manager can export absences of all projects', function () {
    $pm = User::factory()->create(['is_project_manager' => true]);
    Project::factory()->create();

    $this->actingAs($pm)
        ->get(route('absences.export', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]))
        ->assertOk();
});
