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
        Schema::create('student_optional_fees', function (Blueprint $table) {
            $table->id();
            // Siswa mana?
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            // Mendaftar ke aturan biaya mana?
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_optional_fees');
    }
};
