<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sesuai ERD
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            // Induk Jurnalnya
            $table->foreignId('journal_id')
                  ->constrained('journals')
                  ->cascadeOnDelete();

            // Akun mana yang dipakai (dari COA)
            $table->foreignId('account_id')
                  ->constrained('accounts')
                  ->cascadeOnDelete();

            // Tipe: Debit atau Kredit
            $table->enum('type', ['debit', 'kredit']);
            
            $table->decimal('amount', 15, 2); // Nominal

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};