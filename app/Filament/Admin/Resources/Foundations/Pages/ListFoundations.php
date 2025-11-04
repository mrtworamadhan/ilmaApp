<?php

namespace App\Filament\Admin\Resources\Foundations\Pages;

use App\Filament\Admin\Resources\Foundations\FoundationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFoundations extends ListRecords
{
    protected static string $resource = FoundationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
