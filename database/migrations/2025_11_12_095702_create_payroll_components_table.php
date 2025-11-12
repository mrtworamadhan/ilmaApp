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
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            // Standar multi-tenancy kita
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            
            $table->string('name'); // Cth: "Gaji Pokok", "Tunjangan Jabatan", "Potongan BPJS"
            
            // 'allowance' = Pendapatan/Tunjangan
            // 'deduction' = Potongan
            $table->enum('type', ['allowance', 'deduction']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};