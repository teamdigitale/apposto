<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $modelLabel = 'Prenotazione';
    protected static ?string $pluralModelLabel = 'Prenotazioni';
    protected static ?string $navigationLabel = 'Sezione Prenotazioni';

    protected static ?string $navigationIcon = 'heroicon-s-calendar';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
                Forms\Components\Select::make('user_id')
                    ->label('Prenotante')
                    ->relationship(name: 'user',titleAttribute: 'name')->required(),
                Forms\Components\TextInput::make('desk_id')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('from_date'),
                Forms\Components\DateTimePicker::make('to_date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        

         // 0 confermata
            // 1 cancellata
            // 2 "rubata da qualche utente con profilo superiore"
            
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('desk.identifier')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('desk.plan.description')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('desk.plan.workplace.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->formatStateUsing(function ($state): string {
                        $array_val = ["confermata", "cancellata", "rubata", "conclusa" ];                  
                        return $array_val[$state];
                    }),
                Tables\Columns\TextColumn::make('from_date')
                    ->dateTime()
                    ->sortable()->formatStateUsing(function ($state): string {
                        return \Carbon\Carbon::parse($state)->format('d-m-Y H:i') ;                  
                    }),
                Tables\Columns\TextColumn::make('to_date')
                    ->dateTime()
                    ->sortable()->formatStateUsing(function ($state): string {
                        return \Carbon\Carbon::parse($state)->format('d-m-Y H:i') ;                  
                    }),
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
                SelectFilter::make('status')
                    ->options([
                        0 => "confermata", 
                        1 => "cancellata", 
                        2 => "rubata", 
                        3 => "conclusa"
                    ])
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
