<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeskResource\Pages;
use App\Filament\Resources\DeskResource\RelationManagers;
use App\Models\Desk;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeskResource extends Resource
{
    protected static ?string $model = Desk::class;

    protected static ?string $modelLabel = 'Postazione';
    protected static ?string $pluralModelLabel = 'Potazioni';
    protected static ?string $navigationLabel = 'Sezione Postazioni';

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationGroup = 'Config';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('identifier')
                    ->required()->label('Identificativo')->unique(ignoreRecord: true),
                Forms\Components\Select::make('plan_id')
                    ->label('Piano - Zona')
                    ->relationship(name: 'plan',titleAttribute: 'description')->required(),
                Forms\Components\Toggle::make('active')
                    ->required()->label('Abilitata')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('identifier')
                    ->searchable()->label('Identificativo'),
                Tables\Columns\TextColumn::make('plan.description')
                    ->numeric()
                    ->sortable()->label('Piano - Zona'),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()->label('Abilitata'),
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
            'index' => Pages\ListDesks::route('/'),
            'create' => Pages\CreateDesk::route('/create'),
            'edit' => Pages\EditDesk::route('/{record}/edit'),
        ];
    }
}
