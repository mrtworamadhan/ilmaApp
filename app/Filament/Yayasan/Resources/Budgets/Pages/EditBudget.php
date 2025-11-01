<?php

namespace App\Filament\Yayasan\Resources\Budgets\Pages;

use App\Filament\Yayasan\Resources\Budgets\BudgetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBudget extends EditRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
