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
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;
use Filament\Tables\Actions\Action;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\TemporaryUploadedFile;
use App\Imports\BookingImport;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;

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
                Forms\Components\Toggle::make('is_exclusive')
                    ->required()->label('ESCLUSIVO')
            ]);
    }

    public static function table(Table $table): Table
    {
        if (!Storage::disk('public')->exists('uploads')) {
            Storage::disk('public')->makeDirectory('uploads');
        }

         // 0 confermata
            // 1 cancellata
            // 2 "rubata da qualche utente con profilo superiore"
            
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable()->searchable(),
                Tables\Columns\TextColumn::make('desk.identifier')
                    ->numeric()
                    ->sortable()->searchable(),
                Tables\Columns\TextColumn::make('desk.plan.workplace.name')
                    ->numeric()
                    ->sortable()->label("Sede"),
                Tables\Columns\TextColumn::make('desk.plan.description')
                    ->numeric()
                    ->sortable()->label("Zona"),
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
                Tables\Columns\IconColumn::make('is_exclusive')
                    ->boolean()->label('ESCLUSIVO'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        0 => "confermata", 
                        1 => "cancellata", 
                        2 => "rubata", 
                        3 => "conclusa"
                    ]),

                    Filter::make('user_name')
                    ->form([
                        TextInput::make('user_name')
                            ->label('Nome Utente')
                            ->placeholder('Inserisci nome utente'),
                    ])
                    ->query(fn ($query, $data) => 
                        $query->when($data['user_name'], fn ($q, $value) => 
                            $q->whereHas('user', fn ($q) => 
                                $q->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($value)."%"])
                            )
                        )
                    ),

                    Filter::make('date_range')
                    ->form([
                        DatePicker::make('from_date')->label('Da Data'),
                        DatePicker::make('to_date')->label('A Data'),
                    ])
                    ->query(fn ($query, $data) => 
                        $query->when($data['from_date'] && $data['to_date'], fn ($q) => 
                            $q->whereBetween('from_date', [$data['from_date'], $data['to_date']])
                        )
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('importBookings')
                    ->label('Importa Prenotazioni')
                    ->icon('heroicon-s-newspaper')
                    ->form([
                        FileUpload::make('file')
                        ->label('Seleziona File')
                        ->disk('public')  // Usa il disco "public"
                        ->directory('uploads') // Salva in storage/app/public/uploads
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                        ->required(),
                    ])
                    ->action(function (array $data) {
                        $filePath = Storage::disk('public')->path($data['file']);
                        Excel::import(new BookingImport, $filePath);
                    })
                    ->successNotificationTitle('Importazione completata!'),
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
