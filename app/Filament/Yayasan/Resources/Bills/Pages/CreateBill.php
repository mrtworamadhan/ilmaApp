<?php

namespace App\Filament\Yayasan\Resources\Bills\Pages;

use App\Filament\Yayasan\Resources\Bills\BillResource;
use App\Models\Student;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;
    
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // 1. Selalu sisipkan ID Yayasan
        $data['foundation_id'] = Filament::getTenant()->id;
        
        // 2. Ambil 'school_id' dari siswa yang dipilih
        $student = Student::find($data['student_id']);
        
        if (!$student) {
            throw new \Exception("Student dengan ID {$data['student_id']} tidak ditemukan.");
        }
        
        if (empty($student->school_id)) {
            throw new \Exception("Student '{$student->full_name}' tidak memiliki school_id.");
        }
        
        $data['school_id'] = $student->school_id;

        // Debug final data
        \Log::info('Final Bill Data before create:', $data);

        // Create record dengan data yang sudah dimodifikasi
        return static::getModel()::create($data);
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}