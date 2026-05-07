<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';
    protected static ?string $title = 'Progetti Associati';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id')
                    ->label('Progetto')
                    ->options(Project::where('active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('role')
                    ->label('Ruolo nel progetto')
                    ->options([
                        'developer'     => 'Developer',
                        'designer'      => 'Designer',
                        'tester'        => 'Tester',
                        'product owner' => 'Product Owner',
                        'scrum master'  => 'Scrum Master',
                        'manager'       => 'Manager',
                        'member'        => 'Member',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            // Fondamentale: senza questo la pivot non viene caricata
            // e il ruolo risulta sempre null/member
            ->modifyQueryUsing(fn ($query) => $query->withPivot('role', 'created_at', 'updated_at'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Progetto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pivot.role')
                    ->label('Ruolo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manager'       => 'danger',
                        'scrum master'  => 'warning',
                        'product owner' => 'info',
                        'developer'     => 'success',
                        'designer'      => 'primary',
                        'tester'        => 'gray',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\IconColumn::make('active')
                    ->label('Attivo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inizio')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fine')
                    ->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('active')
                    ->label('Stato')
                    ->options([
                        '1' => 'Attivi',
                        '0' => 'Archiviati',
                    ]),
            ])
            ->headerActions([
                // Aggiungi l'utente a un progetto esistente con un ruolo
                Tables\Actions\AttachAction::make()
                    ->label('Aggiungi a Progetto')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Progetto')
                            ->searchable(),
                        Forms\Components\Select::make('role')
                            ->label('Ruolo nel progetto')
                            ->options([
                                'developer'     => 'Developer',
                                'designer'      => 'Designer',
                                'tester'        => 'Tester',
                                'product owner' => 'Product Owner',
                                'scrum master'  => 'Scrum Master',
                                'manager'       => 'Manager',
                                'member'        => 'Member',
                            ])
                            ->required(),
                    ]),
            ])
            ->actions([
                // Modifica il ruolo dell'utente nel progetto
                Tables\Actions\EditAction::make()
                    ->label('Cambia Ruolo')
                    ->form([
                        Forms\Components\Select::make('role')
                            ->label('Ruolo nel progetto')
                            ->options([
                                'developer'     => 'Developer',
                                'designer'      => 'Designer',
                                'tester'        => 'Tester',
                                'product owner' => 'Product Owner',
                                'scrum master'  => 'Scrum Master',
                                'manager'       => 'Manager',
                                'member'        => 'Member',
                            ])
                            ->required(),
                    ])
                    // Pre-popola il form con il ruolo attuale dalla pivot
                    ->mountUsing(function (Forms\Form $form, $record): void {
                        $form->fill([
                            'role' => $record->pivot?->role ?? 'member',
                        ]);
                    })
                    ->using(function ($record, array $data, RelationManager $livewire): void {
                        $livewire->getOwnerRecord()
                            ->projects()
                            ->updateExistingPivot($record->id, [
                                'role'       => $data['role'],
                                'updated_at' => now(),
                            ]);
                    }),
                // Rimuovi l'utente dal progetto
                Tables\Actions\DetachAction::make()
                    ->label('Rimuovi dal Progetto'),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make()
                    ->label('Rimuovi dal Progetto'),
            ]);
    }
}