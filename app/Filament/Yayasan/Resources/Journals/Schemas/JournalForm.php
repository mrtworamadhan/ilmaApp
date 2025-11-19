<?php

namespace App\Filament\Yayasan\Resources\Journals\Schemas;

use App\Models\Account;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
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
        
        $allAccounts = Account::where('foundation_id', Filament::getTenant()->id)
                            ->pluck('name', 'id');
        
        $accountNormalBalanceMap = Account::where('foundation_id', Filament::getTenant()->id)
                                          ->pluck('normal_balance', 'id');
        
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal Transaksi')
                    ->default(now())
                    ->required(),

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
                    ->hidden(!$isYayasanUser), 

                Hidden::make('school_id')
                    ->default($userSchoolId)
                    ->hidden($isYayasanUser), 

                Textarea::make('description')
                    ->label('Deskripsi Transaksi')
                    ->required()
                    ->columnSpanFull(),

                Repeater::make('entries')
                    ->label('Entri Jurnal')
                    ->relationship()
                    ->columns(3)
                    ->columnSpanFull()
                    ->live() 
                    ->schema([
                        Select::make('account_id')
                            ->label('Akun COA')
                            ->options($allAccounts) 
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live() 
                            ->afterStateUpdated(function (Set $set, ?string $state) use ($accountNormalBalanceMap) {
                                if (isset($accountNormalBalanceMap[$state])) {
                                    $normalBalance = $accountNormalBalanceMap[$state];
                                    if ($normalBalance == 'Debit') {
                                        $set('kredit_amount', null); 
                                        $set('type', 'debit'); // <-- Set tipe di sini
                                    } else {
                                        $set('debit_amount', null); 
                                        $set('type', 'kredit'); // <-- Set tipe di sini
                                    }
                                }
                            }),
                        
                        TextInput::make('debit_amount')
                            ->label('Debit (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->disabled(function (Get $get) use ($accountNormalBalanceMap) {
                                $accountId = $get('account_id');
                                if (blank($accountId)) return true; 
                                return $accountNormalBalanceMap[$accountId] === 'Kredit';
                            })
                            // ===================================
                            // LOGIKA PENGISI OTOMATIS
                            // ===================================
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (!empty($state) && $state > 0) {
                                    $set('amount', $state);
                                    $set('type', 'debit');
                                }
                            })
                            ->requiredWithout('kredit_amount'), 

                        TextInput::make('kredit_amount')
                            ->label('Kredit (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->disabled(function (Get $get) use ($accountNormalBalanceMap) {
                                $accountId = $get('account_id');
                                if (blank($accountId)) return true; 
                                return $accountNormalBalanceMap[$accountId] === 'Debit';
                            })
                            // ===================================
                            // LOGIKA PENGISI OTOMATIS
                            // ===================================
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (!empty($state) && $state > 0) {
                                    $set('amount', $state);
                                    $set('type', 'kredit');
                                }
                            })
                            ->requiredWithout('debit_amount'), 

                        // ===================================
                        // FIELD DB ASLI (HARUS DEHYDRATED)
                        // ===================================
                        Hidden::make('type')
                            ->dehydrated(), // <-- WAJIB DEHYDRATED

                        Hidden::make('amount')
                            ->dehydrated(), // <-- WAJIB DEHYDRATED

                    ])
                    ->defaultItems(2)
                    ->addActionLabel('Tambah Baris')
                    
                    // --- Validasi & Total (Kode Anda sudah benar) ---
                    ->rules([
                        function (Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $entries = $get('entries');
                                $totalDebit = 0;
                                $totalKredit = 0;

                                foreach ($entries as $entry) {
                                    $totalDebit += (float)($entry['debit_amount'] ?? 0);
                                    $totalKredit += (float)($entry['kredit_amount'] ?? 0);
                                }

                                if (round($totalDebit) !== round($totalKredit)) {
                                    $fail('Total DEBIT dan KREDIT tidak seimbang (unbalanced).');
                                }
                            };
                        }
                    ]),
                
                Placeholder::make('total_debit')
                    ->label('Total Debit')
                    ->content(function (Get $get): string {
                        $total = collect($get('entries'))->sum(fn($entry) => (float)($entry['debit_amount'] ?? 0));
                        return Number::currency($total, 'IDR');
                    }),
                
                Placeholder::make('total_kredit')
                    ->label('Total Kredit')
                    ->content(function (Get $get): string {
                        $total = collect($get('entries'))->sum(fn($entry) => (float)($entry['kredit_amount'] ?? 0));
                        return Number::currency($total, 'IDR');
                    }),
                
                Placeholder::make('selisih')
                    // ... (Kode Placeholder Anda sisanya sudah benar)
                    ->content(function (Get $get): string {
                        $entries = $get('entries');
                        $totalDebit = collect($entries)->sum(fn($entry) => (float)($entry['debit_amount'] ?? 0));
                        $totalKredit = collect($entries)->sum(fn($entry) => (float)($entry['kredit_amount'] ?? 0));
                        $selisih = $totalDebit - $totalKredit;
                        return Number::currency(abs($selisih), 'IDR');
                    })
                    ->color(function (Get $get): string {
                        $entries = $get('entries');
                        $totalDebit = collect($entries)->sum(fn($entry) => (float)($entry['debit_amount'] ?? 0));
                        $totalKredit = collect($entries)->sum(fn($entry) => (float)($entry['kredit_amount'] ?? 0));
                        return (round($totalDebit) === round($totalKredit)) ? 'success' : 'danger';
                    }),
            ]);
    }
}