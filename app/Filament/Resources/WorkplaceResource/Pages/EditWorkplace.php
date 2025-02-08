<?php

namespace App\Filament\Resources\WorkplaceResource\Pages;

use App\Filament\Resources\WorkplaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkplace extends EditRecord
{
    protected static string $resource = WorkplaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
