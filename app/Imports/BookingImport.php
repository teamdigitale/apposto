<?php

namespace App\Imports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\User;
use App\Models\Desk;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;


class BookingImport implements ToModel, WithValidation, WithStartRow
{
    public function startRow(): int
    {
        return 2;
    }
    public function rules(): array
    {
       return [        
            '0' => 'required|email|exists:users,email',
            '1' => 'required|string|exists:desks,identifier',
            '2' => 'required',
            '3' => 'required',
        ];
    }
    
    public function model(array $row)
    {
        if (!isset($row[0]) || !isset($row[1]) || !isset($row[2]) || !isset($row[3])) {
            return null; // Evita errori su righe vuote
        }

        $user = User::where('email', $row[0])->first();
        
        $desk = Desk::where('identifier', $row[1])->first();

        if (!$user || !$desk) {
            return null; // Se l'utente o la scrivania non esistono, ignora la riga
        }
        $timezone = env('APP_TIMEZONE', 'Europe/Rome');

        if (is_numeric($row[2])) {
            $fromA = Carbon::instance(Date::excelToDateTimeObject($row[2]));
        } else {
            $fromA =  Carbon::createFromFormat('d/m/y H:i', $row[2]);

        }

        $start_date_stat = $fromA->toDateString(); // YYYY-MM-DD
        $start_time_stat = $fromA->toTimeString(); // HH:MM:SS

        if (is_numeric($row[3])) {
            $toA = Carbon::instance(Date::excelToDateTimeObject($row[3]));
        } else {
            $toA =  Carbon::createFromFormat('d/m/y H:i', $row[3]);
        }

        $end_date_stat = $toA->toDateString(); // YYYY-MM-DD
        $end_time_stat = $toA->toTimeString(); // HH:MM:SS

        $booking_save = [
            'desk_id' => $desk->id,
            'start_date' => $start_date_stat,
            'end_date' => $end_date_stat,
            'start_time' => $start_time_stat,
            'end_time' => $end_time_stat,
            'from_date'  => Carbon::createFromTimestamp(strtotime("$start_date_stat . $start_time_stat"))->setTimezone($timezone),
            'to_date'  => Carbon::createFromTimestamp(strtotime("$end_date_stat . $end_time_stat"))->setTimezone($timezone),
            'user_id' => $user->id,
            'status'    => 0
        ];

        if($start_date_stat == $end_date_stat){
            $booking = Booking::create($booking_save);
        }
        else
        {
            $dates = [];

            $startDate = Carbon::parse($start_date_stat);
            $endDate = Carbon::parse($end_date_stat);
      
            $diff = $startDate->diffInDays($endDate);
            
            $i=0;
            // Itera tra le date e salva solo i giorni lavorativi
            while ($startDate->lte($endDate)) {
                if ($startDate->isWeekday()) { // Controlla che sia un giorno lavorativo (lun-ven)
                  
                    $start_time = ($i == 0) ? $start_time_stat : "07:30";
                    $end_time = ($i == $diff ) ? $end_time_stat : "21:00";
                    $start_date = $startDate->toDateString();

                    $dates[] = [
                        'desk_id' => $desk->id,
                        'start_date' => $start_date,
                        'end_date' => $start_date,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'from_date'  => Carbon::createFromTimestamp(strtotime("$start_date . $start_time"))->setTimezone($timezone),
                        'to_date'  => Carbon::createFromTimestamp(strtotime("$start_date . $end_time"))->setTimezone($timezone),
                        'user_id' => $user->id,
                        'status'    => 0
                    ];
                   
                }
                $i++;
                $startDate->addDay(); // Passa al giorno successivo
            }
    
            Booking::insert($dates);
        }

    }
}
