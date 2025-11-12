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
        Schema::create('payslip_details', function (Blueprint $table) {
            $table->id();
            // Terhubung ke slip gaji induk
            $table->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();

            // "Foto" komponen (Kita simpan sebagai string)
            // Kenapa string? Agar jika master komponen berubah, data slip gaji historis tetap aman
            $table->string('component_name'); // Cth: "Gaji Pokok"
            $table->enum('type', ['allowance', 'deduction']); // Pendapatan atau Potongan
            $table->decimal('amount', 15, 2); // Nominal "foto"
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_details');
    }
};