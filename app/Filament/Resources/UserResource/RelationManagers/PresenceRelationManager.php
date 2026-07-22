<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Workplace;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PresenceRelationManager extends RelationManager
{
    protected static string $relationship = 'presences';
    protected static ?string $title = 'Presenze/Smart/Permessi';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Data')
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->native(false),

                Forms\Components\Select::make('status')
                    ->label('Stato')
                    ->options([
                        'presente'      => '🏢 Presente',
                        'smart_working' => '💻 Smart Working',
                        'ferie'         => '🚫 Assenza',
                        'permesso'      => '⏰ Permesso',
                    ])
                    ->required()
                    ->live(),

                Forms\Components\Select::make('workplace_id')
                    ->label('Sede')
                    ->options(Workplace::orderBy('name')->pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->visible(fn (Forms\Get $get): bool => $get('status') === 'presente')
                    ->helperText('Opzionale — solo per presenze in ufficio'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Data')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string =>
                        \Carbon\Carbon::parse($state)->format('d-m-Y')
                    ),
                TextColumn::make('status')
                    ->badge()
                    ->label('Stato')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ferie'         => 'Assenza',
                        'presente'      => 'Presente',
                        'smart_working' => 'Smart working',
                        'permesso'      => 'Permesso',
                        default         => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'presente'      => 'success',
                        'ferie'         => 'warning',
                        'smart_working' => 'info',
                        'permesso'      => 'danger',
                        default         => 'gray',
                    }),
                TextColumn::make('workplace.name')->label('Sede')->default('—'),
                TextColumn::make('booking.desk.identifier')->label('Postazione')->default('—'),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Aggiungi presenza')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (($data['status'] ?? null) !== 'presente') {
                            $data['workplace_id'] = null;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifica')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (($data['status'] ?? null) !== 'presente') {
                            $data['workplace_id'] = null;
                        }
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}