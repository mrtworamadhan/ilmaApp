<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();

            // Wajib untuk multi-tenancy
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete();
            
            // Nama Kategori, misal: "SPP Bulanan", "Uang Makan", "Daftar Ulang"
            $table->string('name');

            // INI KUNCINYA: Relasi ke tabel Akun (COA)
            // Saat kategori ini dibayar, masuk ke akun pendapatan mana?
            $table->foreignId('account_id')
                  ->constrained('accounts')
                  ->cascadeOnDelete();
                  
            $table->boolean('is_optional')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_categories');
    }
};