<?php

namespace App\Filament\Widgets;

use App\Models\Presence;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodayAbsencesWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Fuori sede oggi';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Presence::query()
                    ->whereDate('date', now()->toDateString())
                    ->whereIn('status', ['ferie', 'permesso', 'smart_working'])
                    ->with(['user.team'])
                    ->orderBy('status')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Persona')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('user.team.label')
                    ->label('Team')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'ferie' => 'Assenza',
                        'permesso' => 'Permesso',
                        'smart_working' => 'Smart working',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'ferie' => 'warning',
                        'permesso' => 'danger',
                        'smart_working' => 'info',
                        default => 'gray',
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nessuno fuori sede')
            ->emptyStateDescription('Oggi non risultano ferie, permessi o smart working.');
    }
}
