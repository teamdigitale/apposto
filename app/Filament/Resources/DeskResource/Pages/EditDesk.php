<?php

namespace App\Filament\Resources\DeskResource\Pages;

use App\Filament\Resources\DeskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDesk extends EditRecord
{
    protected static string $resource = DeskResource::class;

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
