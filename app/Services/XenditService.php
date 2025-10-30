<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Student;

class XenditService
{
    public static function makeVA(Student $student)
    {
        try {
            $externalId = 'VA_ILMA_' . $student->id . '_' . time();
            
            $response = Http::withBasicAuth(env('XENDIT_SECRET_KEY'), '')
                ->post('https://api.xendit.co/callback_virtual_accounts', [
                    'external_id' => $externalId,
                    'bank_code' => 'MANDIRI',
                    'name' => $student->name,
                    'is_reusable' => true,
                    'is_closed' => false,
                    // 'expected_amount' => null,
                    // 'expiration_date' => null,
                    'description' => 'Virtual Account untuk pembayaran siswa ' . $student->name,
                ]);

            Log::info('🚀 Response dari Xendit:', $response->json() ?? []);

            if ($response->successful()) {
                $data = $response->json();
                $va = $data['account_number'] ?? null;
                
                if ($va) {
                    $student->updateQuietly(['va_number' => $va]);
                    Log::info("✅ VA berhasil dibuat", [
                        'student_id' => $student->id,
                        'va_number' => $va,
                        'xendit_id' => $data['id'] ?? 'unknown'
                    ]);
                    return $va;
                } else {
                    Log::warning("VA tidak dikembalikan dalam response", [
                        'response' => $data,
                    ]);
                }
            } else {
                Log::error("Gagal membuat VA", [
                    'student_id' => $student->id,
                    'status_code' => $response->status(),
                    'response' => $response->json(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Exception saat membuat VA", [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return null;
    }
}