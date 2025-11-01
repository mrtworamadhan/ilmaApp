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
        Schema::create('disbursement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            // Tahu ini pengajuan untuk pos anggaran yg mana
            $table->foreignId('budget_item_id')->constrained('budget_items')->cascadeOnDelete();
            // Siapa yg mengajukan
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            // Siapa yg menyetujui (Bendahara/Admin Sekolah)
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('requested_amount', 15, 2);
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, DISBURSED, REJECTED

            // Kolom untuk Laporan Realisasi
            $table->string('realization_attachment')->nullable(); // Upload nota
            $table->decimal('realization_amount', 15, 2)->nullable(); // Jumlah realisasi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursement_requests');
    }
};
