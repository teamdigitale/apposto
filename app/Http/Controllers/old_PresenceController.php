<?php

namespace App\Http\Controllers;

use App\Helpers\Holidays;
use Illuminate\Http\Request;
use App\Models\Presence;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $presences = Presence::where('user_id', $user->id)->get();

        $events = $presences->map(function ($presence) {
            return [
                'title' => ucfirst(str_replace('_', ' ', $presence->status)),
                'start' => $presence->date,
                'status' => $presence->status,
            ];
        });

        // Aggiunta festività italiane
        $yearsToLoad = [now()->year - 1, now()->year, now()->year + 1];
        $holidayEvents = [];

        foreach ($yearsToLoad as $year) {
            foreach (Holidays::italianHolidays($year) as $date => $name) {
                $holidayEvents[] = [
                    'title' => $name,
                    'start' => $date,
                    'display' => 'background',
                    'backgroundColor' => '#f8d7da',
                    'borderColor' => '#f5c2c7',
                    'textColor' => '#842029'
                ];
            }
        }

        return view('presences.index', [
            'presences' => $presences,
            'events' => $events,
            'holidays' => $holidayEvents, // nuovo
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'presences' => 'required|array',
            'presences.*.date' => 'required|date',
            'presences.*.status' => 'required|in:presente,ferie,smart_working,permesso',
        ]);

        
    
        $user = Auth::user();
    
        DB::transaction(function() use ($validated, $user) {
            foreach ($validated['presences'] as $presenceData) {
                $presence = Presence::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $presenceData['date']],
                    ['status' => $presenceData['status']]
                );
    
                if ($presenceData['status'] === 'presente') {
                    $timezone = env('APP_TIMEZONE', 'Europe/Rome');
                    $start_time =  "07:30";
                    $end_time = "21:00";
                    $date = $presenceData['date'];
                    $booking_save = [
                        'desk_id' => $user->default_workstation_id,
                        'start_date' => $date,
                        'end_date' => $date,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'from_date'  => Carbon::createFromTimestamp(strtotime($date . $start_time . ":00"))->setTimezone($timezone),
                        'to_date'  => Carbon::createFromTimestamp(strtotime($date . $end_time . ":00"))->setTimezone($timezone),
                        'user_id' => Auth::id(),
                        'status'    => 0
                    ];

                    // Se lo stato è "presente", creiamo o aggiorniamo la prenotazione
                    Booking::updateOrCreate(
                        $booking_save
                    );
                } else {
                    // Se lo stato NON è più "presente", cancelliamo eventuale booking esistente
                    Booking::where('user_id', $user->id)
                        ->where('start_date', $presenceData['date'])
                        ->delete();
                }
            }
        });

        return response()->json(['message' => 'Presenze salvate con successo!']);
    }

}