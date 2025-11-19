<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;

class XenditService
{
    public static function makeVA(Student $student)
    {
        // Rate limiting: maksimal 10 request per menit
        $key = 'xendit_rate_limit_' . date('Y-m-d-H-i');
        $requests = Cache::get($key, 0);
        
        if ($requests >= 10) {
            Log::warning('Xendit rate limit exceeded', ['student_id' => $student->id]);
            throw new \Exception('Rate limit exceeded. Silakan coba lagi dalam 1 menit.');
        }
        
        Cache::put($key, $requests + 1, 60); // Simpan untuk 1 menit

        try {
            $externalId = 'VA_ILMA_' . $student->id . '_' . time();
            
            $response = Http::withBasicAuth(env('XENDIT_SECRET_KEY'), '')
                ->timeout(30) // Timeout 30 detik
                ->retry(3, 1000) // Retry 3 kali dengan delay 1 detik
                ->post('https://api.xendit.co/callback_virtual_accounts', [
                    'external_id' => $externalId,
                    'bank_code' => 'MANDIRI',
                    'name' => $student->full_name,
                    'is_reusable' => true,
                    'is_closed' => false,
                ]);

            Log::info('🚀 Response dari Xendit:', $response->json() ?? []);

            if ($response->successful()) {
                $data = $response->json();
                $va = $data['account_number'] ?? null;
                
                if ($va) {
                    $student->update(['va_number' => $va]);
                    Log::info("✅ VA berhasil dibuat", [
                        'student_id' => $student->id,
                        'va_number' => $va,
                        'xendit_id' => $data['id'] ?? 'unknown'
                    ]);
                    return $va;
                }
            }
            
            Log::error("Gagal membuat VA", [
                'student_id' => $student->id,
                'status_code' => $response->status(),
                'response' => $response->json(),
            ]);
            return null;
            
        } catch (\Throwable $e) {
            Log::error("Exception saat membuat VA", [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw untuk handle di action
        }
    }
}