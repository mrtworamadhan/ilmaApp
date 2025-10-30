<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // 1. Wajib untuk Multi-Tenancy
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete();

            // 2. Wajib untuk Multi-Level
            $table->foreignId('school_id')
                  ->constrained('schools')
                  ->cascadeOnDelete();

            // 3. Siswa yang membayar
            $table->foreignId('student_id')
                  ->constrained('students') 
                  ->cascadeOnDelete();

            // 4. Tagihan terkait (WAJIB NULLABLE)
            // Nullable karena bisa untuk 'Top-Up Tabungan' atau 'PPDB'
            $table->foreignId('bill_id')
                  ->nullable()
                  ->constrained('bills') 
                  ->nullOnDelete(); // Jika tagihan dihapus, payment tetap ada

            // 5. Untuk apa pembayaran ini? (Sesuai diskusi kita)
            $table->enum('payment_for', ['bill', 'ppdb', 'savings_topup'])
                  ->default('bill');
            
            // 6. Metode bayar (Sesuai ERD + 'savings') 
            $table->enum('payment_method', ['va', 'qris', 'ewallet', 'cash', 'savings']);

            $table->decimal('amount_paid', 15, 2); // Nominal yang dibayar
            $table->timestamp('paid_at')->default(now()); // Tgl/Jam Bayar

            // 7. Status pembayaran (untuk Xendit) 
            $table->enum('status', ['pending', 'success', 'failed'])
                  ->default('success');

            // 8. ID dari Xendit (untuk rekonsiliasi) 
            $table->string('xendit_invoice_id')->nullable()->index();

            $table->text('description')->nullable(); // Catatan manual

            // 9. Siapa yg input (jika manual)
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};