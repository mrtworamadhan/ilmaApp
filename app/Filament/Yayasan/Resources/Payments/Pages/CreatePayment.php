<?php

namespace App\Filament\Yayasan\Resources\Payments\Pages;

use App\Filament\Yayasan\Resources\Payments\PaymentResource;
use App\Models\Bill;
use App\Models\Student; // <-- 1. IMPORT MODEL STUDENT
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // 1. Sisipkan ID Yayasan
        $data['foundation_id'] = Filament::getTenant()->id;
        
        // 2. Sisipkan siapa yg input
        $data['created_by'] = auth()->id();

        // 3. Ambil 'school_id' dari siswa yang dipilih
        $student = Student::find($data['student_id']);
        
        if (!$student) {
            throw new \Exception("Student dengan ID {$data['student_id']} tidak ditemukan.");
        }
        
        if (empty($student->school_id)) {
            throw new \Exception("Student '{$student->name}' tidak memiliki school_id.");
        }
        
        $data['school_id'] = $student->school_id;

        // 4. Update status bill menjadi 'paid'
        if (isset($data['bill_id'])) {
            Bill::where('id', $data['bill_id'])->update(['status' => 'paid']);
        }

        \Log::info('Final Payment Data before create:', $data);

        // Create record dengan data yang sudah dimodifikasi
        return static::getModel()::create($data);
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
