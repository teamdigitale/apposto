<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardIntro extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.dashboard-intro';
}
