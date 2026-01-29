<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $modelLabel = 'Progetto';
    protected static ?string $pluralModelLabel = 'Progetti';
    protected static ?string $navigationLabel = 'Sezione Progetti';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Gestione Ferie';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nome Progetto')
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('description')
                    ->label('Descrizione')
                    ->rows(3)
                    ->maxLength(1000),
                
                Forms\Components\DatePicker::make('start_date')
                    ->label('Data Inizio'),
                
                Forms\Components\DatePicker::make('end_date')
                    ->label('Data Fine')
                    ->after('start_date'),
                
                Forms\Components\Toggle::make('active')
                    ->label('Attivo')
                    ->default(true),
                
                Forms\Components\Select::make('users')
                    ->label('Utenti assegnati')
                    ->multiple()
                    ->relationship('users', 'name')
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrizione')
                    ->limit(50)
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Data Inizio')
                    ->date('d-m-Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Data Fine')
                    ->date('d-m-Y')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('active')
                    ->label('Attivo')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('users_count')
                    ->label('N° Utenti')
                    ->counts('users')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Attivo')
                    ->placeholder('Tutti')
                    ->trueLabel('Solo attivi')
                    ->falseLabel('Solo inattivi'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}