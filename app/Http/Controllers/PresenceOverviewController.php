<?php

namespace App\Http\Controllers;

use App\Models\Presence;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PresenceOverviewController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        abort_unless($user->can('viewOverview', Presence::class), 403, 'Accesso negato');

        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->isWeekday()) {
                $days[] = $cursor->format('Y-m-d');
            }
            $cursor->addDay();
        }

        $projectFilter = $request->input('project_filter');

        $allProjects = $user->visibleProjects()->orderBy('name')->get();
        $allTeams = Team::query()->orderBy('label')->get();
        $defaultTeamId = $this->defaultTeamId($allTeams);

        $validFilter = null;
        if ($projectFilter) {
            $validFilter = $allProjects->firstWhere('id', (int) $projectFilter)
                ? (int) $projectFilter
                : null;
        }

        if ($request->exists('team_filter')) {
            $rawTeam = $request->input('team_filter');
            $validTeamFilter = ($rawTeam === '' || $rawTeam === null)
                ? null
                : $allTeams->firstWhere('id', (int) $rawTeam)?->id;
        } else {
            $validTeamFilter = $defaultTeamId;
        }

        $usersQuery = User::query()->orderBy('name');

        if ($validTeamFilter) {
            $usersQuery->where('team_id', $validTeamFilter);
        }

        if ($validFilter) {
            $usersQuery->whereHas('projects', fn ($q) => $q->where('projects.id', $validFilter));
        }

        $teamUsers = $usersQuery->get();

        $presences = Presence::whereIn('user_id', $teamUsers->pluck('id'))
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->groupBy(function ($p) {
                return $p->user_id.'-'.$p->date;
            });

        $overview = [];
        foreach ($teamUsers as $u) {
            $row = ['user' => $u];
            foreach ($days as $day) {
                $key = $u->id.'-'.$day;
                $row['days'][$day] = $presences[$key][0]->status ?? '-';
            }
            $overview[] = $row;
        }

        return view('presences.overview', compact(
            'overview', 'days', 'month',
            'allProjects', 'validFilter',
            'allTeams', 'validTeamFilter', 'defaultTeamId'
        ));
    }

    private function defaultTeamId(Collection $teams): ?int
    {
        $byLabel = $teams->mapWithKeys(fn (Team $team) => [
            $team->id => trim((string) $team->label),
        ]);

        $ssii = $byLabel->search(fn (string $label) => (bool) preg_match('/^ssii\b/i', $label));
        if ($ssii !== false) {
            return (int) $ssii;
        }

        $ssi = $byLabel->search(fn (string $label) => (bool) preg_match('/^ssi\b/i', $label)
            || strcasecmp($label, 'team SSI') === 0);

        return $ssi === false ? null : (int) $ssi;
    }
}
