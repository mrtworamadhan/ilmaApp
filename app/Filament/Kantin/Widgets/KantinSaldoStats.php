<?php

namespace App\Filament\Kantin\Widgets;

use App\Models\Pos\VendorLedger;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class KantinSaldoStats extends StatsOverviewWidget
{
    public function getColumns(): int | array
    {
        return [
            'md' => 1,
            'xl' => 2,
        ];
    }
    protected function getStats(): array
    {
        $vendor = Auth::user()->vendor;
        $currentBalance = 0;

        if ($vendor) {
            // 2. Ambil saldo terakhir dari ledger vendor
            $currentBalance = VendorLedger::where('vendor_id', $vendor->id)
                                ->latest() // Ambil yang terbaru (latest timestamp)
                                ->first()?->balance_after ?? 0; // Ambil saldo akhirnya
        }

        // 3. Tampilkan sebagai Stat Card
        return [
            Stat::make('Saldo Vendor Saat Ini', 'Rp ' . number_format($currentBalance, 0, ',', '.'))
                ->description('Total saldo yang siap dicairkan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
