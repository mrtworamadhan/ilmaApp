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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year'); // Misal: "2024/2025"
            $table->string('status')->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING'); // Alur kerja: DRAFT, SUBMITTED, APPROVED, REJECTED
            $table->decimal('total_planned_amount', 15, 2)->default(0); // Total nilai anggaran
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
