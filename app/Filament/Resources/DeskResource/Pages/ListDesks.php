<?php

namespace App\Filament\Resources\DeskResource\Pages;

use App\Filament\Resources\DeskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDesks extends ListRecords
{
    protected static string $resource = DeskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
