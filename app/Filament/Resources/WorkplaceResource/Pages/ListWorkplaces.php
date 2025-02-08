<?php

namespace App\Filament\Resources\WorkplaceResource\Pages;

use App\Filament\Resources\WorkplaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkplaces extends ListRecords
{
    protected static string $resource = WorkplaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
