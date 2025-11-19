<?php

namespace App\Filament\Yayasan\Resources\Bills\Pages;

use App\Filament\Yayasan\Resources\Bills\BillResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBill extends EditRecord
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateDataBeforeSave(array $data): array
    {
        $total = 0;
        if (isset($data['items'])) {
            $total = collect($data['items'])->sum(fn($item) => (float)$item['amount']);
        }
        $data['total_amount'] = $total;

        return $data;
    }
}
