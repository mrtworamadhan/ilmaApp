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
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();

            // INI KUNCINYA: Terikat ke Chart of Account (COA)
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();

            $table->string('description'); // Misal: "Pembelian ATK Bulan Juli"
            $table->decimal('planned_amount', 15, 2); // Jumlah yang dianggarkan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
