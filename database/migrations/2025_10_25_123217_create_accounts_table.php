<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sesuai ERD
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            
            // Wajib untuk multi-tenancy
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete();

            $table->string('code')->nullable(); // Kode Akun (misal: 1-100)
            $table->string('name'); // Nama Akun (misal: Kas, Pendapatan SPP)
            
            // Jenis Akun
            $table->enum('type', ['aktiva', 'kewajiban', 'ekuitas', 'pendapatan', 'beban']);
            
            $table->string('category')->nullable(); // Subkategori (misal: Kas, Bank, Gaji)

            // Untuk hierarki (opsional tapi bagus)
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};