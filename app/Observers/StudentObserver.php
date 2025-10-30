<?php

namespace App\Observers;

use App\Models\Student;
use Illuminate\Support\Facades\Log;
use App\Services\XenditService;

class StudentObserver
{
    public function created(Student $student): void
    {
        if (!$student->va_number) {
            XenditService::makeVA($student);
        }
    }
    public function updated(Student $student): void
    {
        if (!$student->va_number) {
            XenditService::makeVA($student);
        }
    }
}
