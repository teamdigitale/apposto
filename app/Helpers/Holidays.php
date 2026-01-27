<?php

namespace App\Helpers;

use Carbon\Carbon;

class Holidays
{
    public static function italianHolidays(int $year = null): array
    {
        $year = $year ?? now()->year;

        return [
            "$year-01-01" => 'Capodanno',
            "$year-01-06" => 'Epifania',
            date('Y-m-d', easter_date($year)) => 'Pasqua',
            Carbon::createFromTimestamp(easter_date($year))->addDay()->toDateString() => 'Lunedì dell\'Angelo',
            "$year-04-25" => 'Festa della Liberazione',
            "$year-06-29" => 'Santo Patrono Roma',
            "$year-05-01" => 'Festa dei Lavoratori',
            "$year-06-02" => 'Festa della Repubblica',
            "$year-08-15" => 'Ferragosto',
            "$year-11-01" => 'Ognissanti',
            "$year-12-08" => 'Immacolata Concezione',
            "$year-12-25" => 'Natale',
            "$year-12-26" => 'Santo Stefano',
        ];
    }

    public static function isHoliday(Carbon|string $date): bool
    {
        $date = Carbon::parse($date)->toDateString();
        return array_key_exists($date, self::italianHolidays(Carbon::parse($date)->year));
    }

    public static function getHolidayName(Carbon|string $date): ?string
    {
        $date = Carbon::parse($date)->toDateString();
        return self::italianHolidays(Carbon::parse($date)->year)[$date] ?? null;
    }
}
