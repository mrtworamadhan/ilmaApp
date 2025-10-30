<?php

namespace App\Filament\Yayasan\Resources\FeeCategories\Pages;

use App\Filament\Yayasan\Resources\FeeCategories\FeeCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeeCategories extends ListRecords
{
    protected static string $resource = FeeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
