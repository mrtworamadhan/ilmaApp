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
        Schema::create('saving_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // Relasi 1-to-1 dengan siswa. 1 siswa HANYA punya 1 rekening.
            $table->foreignId('student_id')->constrained()->cascadeOnDelete()->unique(); 

            $table->string('account_number')->unique(); // Nomor rekening unik
            $table->decimal('balance', 15, 2)->default(0); // Saldo akhir
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_accounts');
    }
};
