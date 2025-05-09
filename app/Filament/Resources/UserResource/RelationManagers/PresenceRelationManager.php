<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class PresenceRelationManager extends RelationManager
{
    protected static string $relationship = 'presences'; 
    protected static ?string $title = 'Presenze/Smart/Permessi';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('Data')->sortable()->formatStateUsing(function ($state): string {
                    return \Carbon\Carbon::parse($state)->format('d-m-Y') ;                  
                }),
                TextColumn::make('status')
                    ->badge()
                    ->label('Stato')
                    ->color(fn (string $state): string => match ($state) {
                        'presente' => 'success',
                        'ferie' => 'warning',
                        'smart_working' => 'info',
                        'permesso' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(), 
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
