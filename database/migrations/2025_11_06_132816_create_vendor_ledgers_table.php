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
        Schema::create('vendor_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();

            $table->string('type'); // 'credit' (masuk) atau 'debit' (keluar/cair)
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2); // Saldo setelah transaksi
            $table->string('description');
            
            // Opsional: Relasi ke transaksi yg menyebabkannya (Penjualan / Pencairan)
            $table->nullableMorphs('reference'); // reference_id, reference_type

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_ledgers');
    }
};