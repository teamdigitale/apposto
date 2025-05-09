<?php

namespace App\Http\Controllers;

use App\Models\Presence;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PresenceOverviewController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        abort_unless($user->superuser, 403, 'Accesso negato');

        // Imposta il mese (default = mese corrente)
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        // Genera giorni lavorativi (esclude sabato e domenica)
        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->isWeekday()) {
                $days[] = $cursor->format('Y-m-d');
            }
            $cursor->addDay();
        }

        // Recupera utenti del proprio team
        $teamUsers = User::where('team_id', $user->team_id)->get();

        // Recupera presenze nel periodo per questi utenti
        $presences = Presence::whereIn('user_id', $teamUsers->pluck('id'))
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->groupBy(function ($p) {
                return $p->user_id . '-' . $p->date;
            });

        // Prepara la struttura dati per la tabella
        $overview = [];

        foreach ($teamUsers as $u) {
            $row = ['user' => $u];
            foreach ($days as $day) {
                $key = $u->id . '-' . $day;
                $row['days'][$day] = $presences[$key][0]->status ?? '-';
            }
            $overview[] = $row;
        }

        return view('presences.overview', compact('overview', 'days', 'month'));
    }
}
