<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Membuat tabel 'schools' sesuai ERD 
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            
            // Ini adalah relasi inti ke yayasan/tenant
            $table->foreignId('foundation_id')
                  ->constrained('foundations')
                  ->cascadeOnDelete(); // Jika yayasan dihapus, sekolahnya ikut terhapus

            $table->string('name'); // Contoh: SD Asa Cendekia
            $table->enum('level', ['tk', 'sd', 'smp', 'sma', 'pondok']); // Sesuai blueprint 
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('headmaster')->nullable(); // Nama kepala sekolah
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};