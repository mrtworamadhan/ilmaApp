<?php

namespace App\Filament\Kantin\Resources\VendorDisbursements\Schemas;

use App\Models\Pos\VendorLedger;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class VendorDisbursementForm
{
    public static function configure(Schema $schema): Schema
    {
        $vendorId = Auth::user()->vendor?->id;
        $currentBalance = VendorLedger::where('vendor_id', $vendorId)
                            ->latest()
                            ->first()?->balance_after ?? 0;
        return $schema
            ->components([
                TextInput::make('amount')
                    ->label('Jumlah Pencairan')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->maxValue($currentBalance) // Tidak boleh minta lebih dari saldo
                    ->helperText('Saldo Anda saat ini: Rp ' . number_format($currentBalance)),
                Textarea::make('notes')
                    ->label('Catatan (Opsional)')
                    ->placeholder('Contoh: Untuk belanja modal')
                    ->columnSpanFull(),
                
                // --- Field Read-Only (hanya tampil saat Edit/View) ---
                Select::make('status')
                    ->options([
                        'requested' => 'Diajukan',
                        'approved' => 'Disetujui',
                        'paid' => 'Dibayar',
                        'rejected' => 'Ditolak',
                    ])
                    ->disabled()
                    ->dehydrated(false) // Jangan simpan saat edit
                    ->visibleOn('edit'),
                DateTimePicker::make('processed_at')
                    ->label('Waktu Diproses')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
                TextInput::make('processor.name') // Relasi processor
                    ->label('Diproses Oleh')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
            ]);
    }
}
