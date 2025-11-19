<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom untuk Akuntansi Dana Terikat (ISAK 35).
     */
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            
            // Kolom 1: Untuk 'mengunci' sumber kas (Rekening Bank spesifik)
            $table->foreignId('cash_source_account_id')
                  ->nullable()
                  ->constrained('accounts') // <-- Relasi ke tabel 'accounts'
                  ->nullOnDelete() // Jika Akun Kas dihapus, set null
                  ->after('status'); // Posisi setelah kolom 'status'

            // Kolom 2: Untuk menandai 'amplop' Aset Neto mana yg terikat
            $table->foreignId('restricted_fund_account_id')
                  ->nullable()
                  ->constrained('accounts') // <-- Relasi ke tabel 'accounts'
                  ->nullOnDelete() // Jika Akun Aset Neto dihapus, set null
                  ->after('cash_source_account_id'); // Posisi setelah kolom baru di atas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Hapus foreign key dan kolomnya
            $table->dropConstrainedForeignId('cash_source_account_id');
            $table->dropConstrainedForeignId('restricted_fund_account_id');
        });
    }
};