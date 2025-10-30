<?php

namespace App\Filament\Yayasan\Resources\Journals\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class JournalForm
{
    public static function configure(Schema $schema): Schema
    {
        $isYayasanUser = auth()->user()->school_id === null;
        $userSchoolId = auth()->user()->school_id;
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal Transaksi')
                    ->default(now())
                    ->required(),

                // 1. Dropdown Sekolah (Hanya untuk Admin Yayasan)
                Select::make('school_id')
                    ->label('Jurnal untuk Sekolah (Opsional)')
                    ->helperText('Kosongkan jika ini jurnal level Yayasan')
                    ->relationship(
                        name: 'school',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => 
                            $query->where('foundation_id', Filament::getTenant()->id)
                    )
                    ->searchable()
                    ->preload()
                    ->hidden(!$isYayasanUser), // Sembunyikan jika bukan Admin Yayasan

                // 2. Field Tersembunyi (Hanya untuk Admin Sekolah)
                Hidden::make('school_id')
                    ->default($userSchoolId)
                    ->hidden($isYayasanUser), // Sembunyikan jika Admin Yayasan

                Textarea::make('description')
                    ->label('Deskripsi Transaksi')
                    ->required()
                    ->columnSpanFull(),

                // --- REPEATER UNTUK DEBIT/KREDIT ---
                Repeater::make('entries')
                    ->label('Entri Jurnal (Debit/Kredit)')
                    ->relationship() // Relasi ke 'entries()' di model Journal
                    ->columns(3)
                    ->columnSpanFull()
                    ->live() // <-- Penting untuk kalkulasi
                    ->schema([
                        Select::make('account_id')
                            ->label('Akun COA')
                            ->relationship(
                                name: 'account',
                                titleAttribute: 'name',
                                // Ambil SEMUA akun milik yayasan ini
                                modifyQueryUsing: fn (Builder $query) => 
                                    $query->where('foundation_id', Filament::getTenant()->id)
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'debit' => 'Debit',
                                'kredit' => 'Kredit',
                            ])
                            ->required(),
                        
                        TextInput::make('amount')
                            ->label('Nominal (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->live(onBlur: true), // Update saat user pindah field
                    ])
                    ->defaultItems(2) // Default 2 baris (1 debit, 1 kredit)
                    ->addActionLabel('Tambah Baris')
                    
                    // --- VALIDASI TOTAL BALANCE ---
                    ->registerActions([
                        Action::make('validateBalance')
                            ->label('Hitung Ulang Total')
                            ->action(fn (Get $get) => null) // Aksi palsu untuk trigger re-kalkulasi
                    ])
                    ->rules([
                        // Rule kustom untuk cek balance
                        function (Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $entries = $get('entries');
                                $totalDebit = 0;
                                $totalKredit = 0;

                                foreach ($entries as $entry) {
                                    if ($entry['type'] == 'debit') {
                                        $totalDebit += (float)$entry['amount'];
                                    } else {
                                        $totalKredit += (float)$entry['amount'];
                                    }
                                }

                                if ($totalDebit !== $totalKredit) {
                                    $fail('Total DEBIT dan KREDIT tidak seimbang (unbalanced).');
                                }
                            };
                        }
                    ]),
                
                // --- TOTAL KALKULASI (LIVE) ---
                Placeholder::make('total_debit')
                    ->label('Total Debit')
                    ->content(function (Get $get): string {
                        $total = collect($get('entries'))
                            ->where('type', 'debit')
                            ->sum(fn($entry) => (float)$entry['amount']);
                        return Number::currency($total, 'IDR');
                    }),
                
                Placeholder::make('total_kredit')
                    ->label('Total Kredit')
                    ->content(function (Get $get): string {
                        $total = collect($get('entries'))
                            ->where('type', 'kredit')
                            ->sum(fn($entry) => (float)$entry['amount']);
                        return Number::currency($total, 'IDR');
                    }),
                
                Placeholder::make('selisih')
                    ->label('Selisih')
                    ->content(function (Get $get): string {
                        $entries = $get('entries');
                        $totalDebit = collect($entries)->where('type', 'debit')->sum(fn($entry) => (float)$entry['amount']);
                        $totalKredit = collect($entries)->where('type', 'kredit')->sum(fn($entry) => (float)$entry['amount']);
                        $selisih = $totalDebit - $totalKredit;
                        
                        return Number::currency(abs($selisih), 'IDR');
                    })
                    ->color(function (Get $get): string {
                        $entries = $get('entries');
                        $totalDebit = collect($entries)->where('type', 'debit')->sum(fn($entry) => (float)$entry['amount']);
                        $totalKredit = collect($entries)->where('type', 'kredit')->sum(fn($entry) => (float)$entry['amount']);
                        return ($totalDebit === $totalKredit) ? 'success' : 'danger';
                    }),
            ]);
    }
}
