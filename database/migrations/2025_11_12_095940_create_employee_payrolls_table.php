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
        Schema::create('employee_payrolls', function (Blueprint $table) {
            $table->id();
            // Standar multi-tenancy kita
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            // Komponen apa yang didapat
            $table->foreignId('payroll_component_id')->constrained('payroll_components')->cascadeOnDelete();

            // V-- INI PERBAIKANNYA --V
            // Langsung terhubung ke Guru (Teacher)
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            // ^-- BATAS AKHIR PERBAIKAN --^

            // Berapa nominalnya
            $table->decimal('amount', 15, 2)->default(0);
            
            $table->timestamps();
            
            // Kita tidak perlu 'school_id' lagi di sini, karena 'teacher' sudah punya 'school_id'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payrolls');
    }
};