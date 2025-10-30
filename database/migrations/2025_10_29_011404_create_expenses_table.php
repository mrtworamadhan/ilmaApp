<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sesuai ERD
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // 1. Wajib untuk Multi-Tenancy
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete();

            // 2. Wajib untuk Multi-Level (Nullable)
            // Null jika ini pengeluaran level Yayasan
            $table->foreignId('school_id')
                  ->nullable() 
                  ->constrained('schools')
                  ->cascadeOnDelete();

            // 3. Kunci Akuntansi: Uang ini dipakai untuk apa?
            // (Relasi ke Akun 'Beban', misal: "Beban Gaji", "Beban ATK")
            $table->foreignId('expense_account_id')
                  ->constrained('accounts') // <- Relasi ke tabel 'accounts'
                  ->cascadeOnDelete();
            
            // 4. Kunci Akuntansi: Uang diambil dari mana?
            // (Relasi ke Akun 'Kas/Bank', misal: "Kas SD", "Bank Yayasan")
            $table->foreignId('cash_account_id')
                  ->constrained('accounts') // <- Relasi ke tabel 'accounts'
                  ->cascadeOnDelete();
            
            $table->decimal('amount', 15, 2); // Nominal pengeluaran
            $table->date('date'); // Tanggal pengeluaran
            $table->text('description')->nullable(); // Keterangan

            // Bukti nota/invoice
            $table->string('proof_file')->nullable(); 

            // Siapa yg input
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};