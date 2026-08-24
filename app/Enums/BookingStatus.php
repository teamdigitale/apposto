<?php

namespace App\Enums;

enum BookingStatus: int
{
    case Confirmed = 0;
    case Cancelled = 1;
    case Overridden = 2;
    case Completed = 3;

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'confermata',
            self::Cancelled => 'cancellata',
            self::Overridden => 'rubata',
            self::Completed => 'conclusa',
        };
    }

    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
