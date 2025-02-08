<?php

namespace App\Filament\Resources\WorkplaceResource\Pages;

use App\Filament\Resources\WorkplaceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkplace extends CreateRecord
{
    protected static string $resource = WorkplaceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
