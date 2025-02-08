<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;
    protected static ?string $modelLabel = 'Gruppo';
    protected static ?string $pluralModelLabel = 'Gruppi';
    protected static ?string $navigationLabel = 'Sezione Team';

    protected static ?string $navigationIcon = 'heroicon-s-inbox-stack';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->required()->label("Nome Team"),
                Forms\Components\Select::make('Plans')
                    ->label('Piani in cui poter accedere')
                    ->multiple()
                    ->relationship('plans', 'description'),
                    Forms\Components\Toggle::make('allow_multi_day')
                    ->required()->label('Può prenotare infiniti gg')
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->searchable()->label("Nome Team"),
                Tables\Columns\TextColumn::make('plans.description')
                    ->numeric()
                    ->sortable()->label("Piano accessibile"),
                Tables\Columns\IconColumn::make('allow_multi_day')
                    ->boolean()->label(' 1_o_infiniti gg'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
