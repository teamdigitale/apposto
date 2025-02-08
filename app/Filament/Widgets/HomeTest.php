<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class HomeTest extends BaseWidget
{

    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        $table->heading('Prenotazioni - Real Time') ;
        return $table
            ->query(
                Booking::where('status',0)
            )->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable()->label('Nome e cognome'),
                Tables\Columns\TextColumn::make('desk.identifier')
                    ->numeric()
                    ->sortable()->label('Postazione'),
                Tables\Columns\TextColumn::make('desk.plan.description')
                    ->numeric()
                    ->sortable()->label('Piano'),
                Tables\Columns\TextColumn::make('desk.plan.workplace.name')
                    ->numeric()
                    ->sortable()->label('Sede'),
                Tables\Columns\TextColumn::make('status')->formatStateUsing(function ($state): string {
                    $array_val = ["confermata", "cancellata", "rubata", "conclusa" ];                  
                    return $array_val[$state];
                    })->label('Stato'),
                Tables\Columns\TextColumn::make('from_date')
                    ->dateTime()
                    ->sortable()->formatStateUsing(function ($state): string {
                        return \Carbon\Carbon::parse($state)->format('d-m-Y h:m') ;                  
                    })->label('Da Data'),
                Tables\Columns\TextColumn::make('to_date')
                    ->dateTime()
                    ->sortable()->formatStateUsing(function ($state): string {
                        return \Carbon\Carbon::parse($state)->format('d-m-Y h:m') ;                  
                    })->label('A Data'),
              
            ]);
    }
}
