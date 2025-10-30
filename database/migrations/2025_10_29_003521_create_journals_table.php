<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sesuai ERD
        Schema::create('journals', function (Blueprint $table) {
            $table->id();

            // 1. Wajib untuk Multi-Tenancy
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete();

            // 2. Wajib untuk Multi-Level (Nullable)
            // Null jika ini jurnal level Yayasan (misal: transfer antar bank)
            $table->foreignId('school_id')
                  ->nullable() 
                  ->constrained('schools')
                  ->cascadeOnDelete();

            $table->date('date'); // Tanggal transaksi
            $table->text('description'); // Deskripsi (misal: "Pembayaran SPP Budi")
            
            // Kolom ini untuk relasi polimorfik (opsional tapi canggih)
            // Bisa merujuk ke 'payments.id' atau 'expenses.id'
            $table->morphs('referenceable'); // Akan membuat 'referenceable_id' dan 'referenceable_type'

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};