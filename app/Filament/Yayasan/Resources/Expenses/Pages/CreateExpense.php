<?php

namespace App\Filament\Yayasan\Resources\Expenses\Pages;

use App\Filament\Yayasan\Resources\Expenses\ExpenseResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;
    protected function mutateDataBeforeCreate(array $data): array
    {
        // 1. Sisipkan ID Yayasan
        $data['foundation_id'] = Filament::getTenant()->id;
        
        // 2. Sisipkan siapa yg input
        $data['created_by'] = auth()->id();

        // 3. Jika user Admin Sekolah, pastikan school_id terisi
        if (auth()->user()->school_id) {
             $data['school_id'] = auth()->user()->school_id;
        } 
        // 4. Jika Admin Yayasan, $data['school_id']
        //    sudah terisi (atau null) dari form

        return $data;
    }
}
