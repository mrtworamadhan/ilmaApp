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
        Schema::table('expenses', function (Blueprint $table) {
            // Saat bendahara input 'Expense', dia bisa pilih ini pengeluaran atas dasar 'Disbursement' yg mana
            $table->foreignId('disbursement_request_id')
                ->nullable()
                ->after('expense_account_id')
                ->constrained('disbursement_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['disbursement_request_id']);
            $table->dropColumn('disbursement_request_id');
        });
    }
};
