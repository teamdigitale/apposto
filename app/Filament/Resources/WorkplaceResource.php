<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkplaceResource\Pages;
use App\Filament\Resources\WorkplaceResource\RelationManagers;
use App\Models\Workplace;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkplaceResource extends Resource
{
    protected static ?string $model = Workplace::class;
    protected static ?string $modelLabel = 'Sede';
    protected static ?string $pluralModelLabel = 'Sedi';
    protected static ?string $navigationLabel = 'Sezione Sedi';

    protected static ?string $navigationIcon = 'heroicon-s-building-office-2';

    protected static ?string $navigationGroup = 'Config';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()->label("Nome Sede"),
                Forms\Components\TextInput::make('address')->label("Indirizzo"),
                Forms\Components\TextInput::make('num_place')
                    ->required()
                    ->numeric()
                    ->default(1)->label("Numero Piani"),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->label("Nome Sede"),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()->label("Indirizzo"),
                Tables\Columns\TextColumn::make('num_place')
                    ->numeric()
                    ->sortable()->label("Numero Piani"),
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
            'index' => Pages\ListWorkplaces::route('/'),
            'create' => Pages\CreateWorkplace::route('/create'),
            'edit' => Pages\EditWorkplace::route('/{record}/edit'),
        ];
    }
}
