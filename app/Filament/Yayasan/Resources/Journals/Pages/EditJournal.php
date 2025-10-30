<?php

namespace App\Filament\Yayasan\Resources\Journals\Pages;

use App\Filament\Yayasan\Resources\Journals\JournalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJournal extends EditRecord
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
