<?php

namespace App\Filament\Yayasan\Resources\Bills\Pages;

use App\Filament\Yayasan\Resources\Bills\BillResource;
use App\Models\Student;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;
    
    protected function mutateDataBeforeCreate(array $data): array
    {
        // 1. Hitung total_amount dari rincian 'items'
        $total = 0;
        if (isset($data['items'])) {
            $total = collect($data['items'])->sum(fn($item) => (float)$item['amount']);
        }
        $data['total_amount'] = $total;

        // 2. Sisipkan ID Yayasan
        $data['foundation_id'] = Filament::getTenant()->id;
        
        // 3. Sisipkan school_id jika user adalah Admin Sekolah
        if (auth()->user()->school_id) {
            $data['school_id'] = auth()->user()->school_id;
        }
        // Jika Admin Yayasan, 'school_id' sudah terisi otomatis
        // oleh 'afterStateUpdated' di 'student_id'

        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}