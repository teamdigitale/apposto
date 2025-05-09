<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Team;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use App\Filament\Resources\UserResource\RelationManagers\PresenceRelationManager;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $modelLabel = 'Utente';
    protected static ?string $pluralModelLabel = 'Utenti';
    protected static ?string $navigationLabel = 'Sezione Utenti';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()->label("Nome e Cognome"),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state)),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(1)->label("Priorità"),
                Forms\Components\Select::make('team_id')
                    ->label('Gruppo')
                    ->relationship(name: 'team',titleAttribute: 'label')
                    //->options(Team::all()->pluck('label', 'id'))
                    ->searchable()->required(),
                Forms\Components\Select::make('default_workstation_id')
                    ->label('Postazione di default')
                    ->relationship(name: 'defaultWorkstation',titleAttribute: 'identifier')
                    //->options(Team::all()->pluck('label', 'id'))
                    ->searchable(),
                    Forms\Components\TextInput::make('phone'),    
                Forms\Components\Toggle::make('allow_view')
                    ->required()->label('Condivide Info'),    
                Forms\Components\Toggle::make('gestiamopresenze')
                    ->required()->label('Gestiamo presenze'),
                Forms\Components\Toggle::make('superuser')
                    ->required()->label('Super User'),
                Forms\Components\Toggle::make('addetto_emergenza')
                    ->label('Add Emergenza/Antincendio'),
                Forms\Components\Toggle::make('addetto_al_primo_soccorso')
                    ->label('Certificato Primo Soccorso'),
                Forms\Components\TextInput::make('ruolo')
                    ->label('Ruolo'),    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->label("Nome e Cognome"),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()->label("Mail"),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable()->label("Priorità"),
                Tables\Columns\TextColumn::make('team.label')
                ->sortable()->label("Team appartenenza"),
                Tables\Columns\IconColumn::make('allow_view')
                    ->boolean()->label('Condivide Info'),
                Tables\Columns\IconColumn::make('superuser')
                    ->boolean()->label('Super User'),
                Tables\Columns\IconColumn::make('gestiamopresenze')
                    ->boolean()->label('Timesheet'),
                Tables\Columns\TextColumn::make('ferie_totali')
                    ->numeric()->label('Ferie Tot'),
                Tables\Columns\TextColumn::make('ferie_usate')
                    ->numeric()->label('Ferie Usate'),
                Tables\Columns\TextColumn::make('giorni_in_smart')
                    ->numeric()->label('Smart'),
            ])
            ->filters([
                //
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), 
                Tables\Actions\RestoreAction::make(), 
                Tables\Actions\ReplicateAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(), 
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PresenceRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
