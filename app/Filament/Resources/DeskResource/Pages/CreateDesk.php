<?php

namespace App\Filament\Resources\DeskResource\Pages;

use App\Filament\Resources\DeskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDesk extends CreateRecord
{
    protected static string $resource = DeskResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
