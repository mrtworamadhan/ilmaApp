<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // === 1. VERIFIKASI CALLBACK ===
        $xenditWebhookSecret = env('XENDIT_WEBHOOK_SECRET');
        $signature = $request->header('x-callback-token');

        if (!$xenditWebhookSecret) {
            Log::error('Xendit Webhook: XENDIT_WEBHOOK_SECRET tidak dikonfigurasi di .env');
            return response()->json(['message' => 'Webhook not configured'], 500);
        }

        if (!$signature) {
            Log::warning('Xendit Webhook: Signature header (x-callback-token) tidak ada');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!hash_equals($xenditWebhookSecret, $signature)) {
            Log::warning('Xendit Webhook: Signature tidak valid', [
                'expected' => $xenditWebhookSecret,
                'received' => $signature
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // === 2. EXTRACT PAYLOAD ===
        $payload = $request->all();
        Log::info('🔔 Xendit Webhook Received:', $payload);

        // Handle nested structure (Xendit sometimes uses 'data' key)
        $data = $payload['data'] ?? $payload;
        
        $vaNumber = $data['account_number'] ?? null;
        $amountPaid = $data['amount'] ?? null;
        $externalId = $data['external_id'] ?? null;
        $paymentIdXendit = $data['payment_id'] ?? $data['id'] ?? null;
        $bankCode = $data['bank_code'] ?? 'VA';
        $paymentStatus = $data['status'] ?? 'PAID';

        // === 3. VALIDASI DATA ===
        if (!$vaNumber || !$amountPaid || !$paymentIdXendit) {
            Log::warning('❌ Xendit Webhook: Data tidak lengkap', [
                'va_number' => $vaNumber,
                'amount' => $amountPaid,
                'payment_id' => $paymentIdXendit
            ]);
            return response()->json(['message' => 'Incomplete data'], 400);
        }

        // === 4. PROSES PEMBAYARAN ===
        try {
            // Cari siswa berdasarkan VA Number
            $student = Student::where('va_number', $vaNumber)->first();

            if (!$student) {
                Log::warning('❌ Xendit Webhook: Siswa dengan VA ' . $vaNumber . ' tidak ditemukan');
                return response()->json(['message' => 'Student not found but acknowledged'], 200);
            }

            Log::info('👤 Student ditemukan:', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'va_number' => $vaNumber
            ]);

            // === 5. CARI & UPDATE BILL ===
            $bill = Bill::where('student_id', $student->id)
                        ->whereIn('status', ['unpaid', 'overdue'])
                        ->where('amount', $amountPaid)
                        ->orderBy('due_date', 'asc')
                        ->first();

            if (!$bill) {
                Log::warning('⚠️ Xendit Webhook: Tagihan tidak ditemukan', [
                    'student_id' => $student->id,
                    'amount_paid' => $amountPaid,
                    'expected_amounts' => Bill::where('student_id', $student->id)
                                            ->whereIn('status', ['unpaid', 'overdue'])
                                            ->pluck('amount')
                ]);

                // Option: Create payment record without bill association
                $this->createOrphanPayment($student, $amountPaid, $paymentIdXendit, $bankCode);
                return response()->json(['message' => 'Bill not found, orphan payment recorded'], 200);
            }

            // === 6. CEK DUPLIKASI PAYMENT ===
            $existingPayment = Payment::where('xendit_invoice_id', $paymentIdXendit)->first();
            if ($existingPayment) {
                Log::info('🔁 Xendit Webhook: Payment sudah diproses', [
                    'payment_id' => $existingPayment->id,
                    'xendit_invoice_id' => $paymentIdXendit
                ]);
                return response()->json(['message' => 'Already processed'], 200);
            }

            // === 7. BUAT PAYMENT RECORD ===
            $payment = Payment::create([
                'foundation_id' => $student->foundation_id,
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'bill_id' => $bill->id,
                'payment_for' => 'bill',
                'payment_method' => 'virtual_account',
                'amount_paid' => $amountPaid,
                'paid_at' => now(),
                'status' => 'success',
                'xendit_invoice_id' => $paymentIdXendit,
                'description' => 'Pembayaran via Virtual Account ' . $bankCode,
                'created_by' => null,
            ]);

            // === 8. UPDATE BILL STATUS ===
            $bill->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            Log::info('✅ Pembayaran berhasil diproses', [
                'payment_id' => $payment->id,
                'bill_id' => $bill->id,
                'student_id' => $student->id,
                'amount' => $amountPaid
            ]);

            // === 9. KIRIM RESPONSE ===
            return response()->json(['message' => 'Webhook processed successfully']);

        } catch (\Exception $e) {
            Log::error('💥 Xendit Webhook: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Handle payment without associated bill (orphan payment)
     */
    private function createOrphanPayment($student, $amount, $xenditId, $bankCode)
    {
        try {
            Payment::create([
                'foundation_id' => $student->foundation_id,
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'bill_id' => null,
                'payment_for' => 'unknown',
                'payment_method' => 'virtual_account',
                'amount_paid' => $amount,
                'paid_at' => now(),
                'status' => 'success',
                'xendit_invoice_id' => $xenditId,
                'description' => 'Pembayaran orphan via VA ' . $bankCode . ' - Tidak ada tagihan yang cocok',
                'created_by' => null,
            ]);

            Log::info('📝 Orphan payment recorded', [
                'student_id' => $student->id,
                'amount' => $amount
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create orphan payment', [
                'error' => $e->getMessage()
            ]);
        }
    }
}