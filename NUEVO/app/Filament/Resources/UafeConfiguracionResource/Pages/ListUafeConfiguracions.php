<?php

namespace App\Filament\Resources\UafeConfiguracionResource\Pages;

use App\Filament\Resources\UafeConfiguracionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUafeConfiguracions extends ListRecords
{
    protected static string $resource = UafeConfiguracionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
