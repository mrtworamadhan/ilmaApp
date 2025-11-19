<?php

namespace App\Filament\Yayasan\Resources\Journals\Pages;

use App\Filament\Yayasan\Resources\Journals\JournalResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        // Sisipkan ID Yayasan & User
        $data['foundation_id'] = Filament::getTenant()->id;
        $data['created_by'] = auth()->id();

        // Terjemahkan 'entries' dari UX baru ke skema DB lama
        $processedEntries = [];
        foreach ($data['entries'] as $entry) {
            if (!empty($entry['debit_amount'])) {
                // Ini adalah entri DEBIT
                $processedEntries[] = [
                    'account_id' => $entry['account_id'],
                    'type' => 'debit',
                    'amount' => $entry['debit_amount'],
                ];
            } elseif (!empty($entry['kredit_amount'])) {
                // Ini adalah entri KREDIT
                $processedEntries[] = [
                    'account_id' => $entry['account_id'],
                    'type' => 'kredit',
                    'amount' => $entry['kredit_amount'],
                ];
            }
        }
        $data['entries'] = $processedEntries;

        return $data;
    }
}
