<?php

namespace App\Filament\Widgets;

use App\Models\Presence;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PresenceTrendChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Andamento presenze (7 giorni)';

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $labels = [];
        $presente = [];
        $smart = [];
        $assenti = [];

        $from = Carbon::today()->subDays(6);
        $to = Carbon::today();

        $rows = Presence::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['date', 'status']);

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayRows = $rows->filter(fn ($row) => Carbon::parse($row->date)->isSameDay($day));

            $labels[] = $day->translatedFormat('D d');
            $presente[] = $dayRows->where('status', 'presente')->count();
            $smart[] = $dayRows->where('status', 'smart_working')->count();
            $assenti[] = $dayRows->whereIn('status', ['ferie', 'permesso'])->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'In ufficio',
                    'data' => $presente,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Smart working',
                    'data' => $smart,
                    'borderColor' => '#0284c7',
                    'backgroundColor' => 'rgba(2, 132, 199, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Assenze',
                    'data' => $assenti,
                    'borderColor' => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
