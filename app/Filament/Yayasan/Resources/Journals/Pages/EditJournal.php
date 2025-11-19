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
    protected function mutateDataBeforeSave(array $data): array
    {
        // Terjemahkan 'entries' dari UX baru ke skema DB lama
        $processedEntries = [];
        foreach ($data['entries'] as $entry) {
            if (!empty($entry['debit_amount'])) {
                $processedEntries[] = [
                    'account_id' => $entry['account_id'],
                    'type' => 'debit',
                    'amount' => $entry['debit_amount'],
                ];
            } elseif (!empty($entry['kredit_amount'])) {
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
    
    // ===================================================
    // TAMBAHKAN LOGIKA "PENERJEMAH" (LOAD)
    // ===================================================
    protected function mutateDataBeforeFill(array $data): array
    {
        // Terjemahkan data DB lama ke UX baru
        $processedEntries = [];
        foreach ($data['entries'] as $entry) {
            $processedEntries[] = [
                'account_id' => $entry['account_id'],
                // Jika tipenya 'debit', isi 'debit_amount', jika bukan, isi 'kredit_amount'
                'debit_amount' => ($entry['type'] == 'debit') ? $entry['amount'] : null,
                'kredit_amount' => ($entry['type'] == 'kredit') ? $entry['amount'] : null,
            ];
        }
        $data['entries'] = $processedEntries;

        return $data;
    }
}
