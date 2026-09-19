<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        return 'Dashboard APPosto';
    }

    public function getSubheading(): ?string
    {
        return 'Panoramica di prenotazioni, presenze e progetti del dipartimento.';
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
