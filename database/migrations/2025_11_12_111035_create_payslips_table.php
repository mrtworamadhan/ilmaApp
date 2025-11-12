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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            // Standar multi-tenancy
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            
            // Guru yang menerima slip gaji
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            
            // Periode Gaji
            $table->unsignedSmallInteger('month'); // Cth: 11 (untuk November)
            $table->unsignedSmallInteger('year'); // Cth: 2025
            
            // Nominal "Foto" Gaji
            $table->decimal('total_allowance', 15, 2)->default(0); // Total Pendapatan
            $table->decimal('total_deduction', 15, 2)->default(0); // Total Potongan
            $table->decimal('net_pay', 15, 2)->default(0); // Gaji Bersih (Take Home Pay)

            // Status
            $table->string('status')->default('generated'); // Cth: 'generated', 'paid'
            
            // Integrasi ke Expense (Opsional tapi sangat disarankan)
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();

            $table->timestamps();

            // Kunci unik agar tidak ada slip ganda
            $table->unique(['teacher_id', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};