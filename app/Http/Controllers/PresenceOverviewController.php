<?php

namespace App\Http\Controllers;

use App\Models\Presence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        $validFilter = null;
        if ($projectFilter) {
            $validFilter = $allProjects->firstWhere('id', (int) $projectFilter)
                ? (int) $projectFilter
                : null;
        }

        $usersQuery = User::query()->orderBy('name');

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
            'allProjects', 'validFilter'
        ));
    }
}
