<?php

namespace App\Filament\Yayasan\Resources\Accounts\Schemas;

use App\Models\Account;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // -----------------------------------------------------------
                // GRUP 1 — NAMA & PARENT
                // -----------------------------------------------------------
                Group::make([
                    TextInput::make('name')
                        ->label('Nama Akun')
                        ->required()
                        ->helperText('Contoh: Beban ATK, Pendapatan Karyawisata, Bank BSI - Dana BOS'),

                    Select::make('parent_id')
                        ->label('Induk Akun (Opsional)')
                        ->placeholder('Tidak ada (Akun Induk)')
                        ->options(function (Get $get, ?Account $record) {
                            return Account::query()
                                ->where('foundation_id', Auth::user()->foundation_id)
                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload(),
                ])->columnSpan(1),

                // -----------------------------------------------------------
                // GRUP 2 — TIPE AKUN & KODE
                // -----------------------------------------------------------
                Group::make([
                    Select::make('type')
                        ->label('Tipe Akun')
                        ->required()
                        ->options([
                            'Aset'       => 'Aset (Kas, Bank, Piutang)',
                            'Liabilitas' => 'Liabilitas (Utang)',
                            'Aset Neto'  => 'Aset Neto (Modal / Dana Terikat)',
                            'Pendapatan' => 'Pendapatan (SPP, Sumbangan, BOS)',
                            'Beban'      => 'Beban (Gaji, Listrik, ATK)',
                        ])
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            $map = [
                                'Aset' => ['Debit', 'Aset Lancar'],
                                'Liabilitas' => ['Kredit', 'Kewajiban Jangka Pendek'],
                                'Aset Neto' => ['Kredit', 'Aset Neto'],
                                'Pendapatan' => ['Kredit', 'Laporan Penghasilan Komprehensif'],
                                'Beban' => ['Debit', 'Laporan Penghasilan Komprehensif'],
                            ];

                            if (isset($map[$state])) {
                                [$normalBalance, $category] = $map[$state];
                                $set('normal_balance', $normalBalance);
                                $set('category', $category);
                            } else {
                                $set('normal_balance', null);
                                $set('category', null);
                            }
                        }),

                    TextInput::make('code')
                        ->label('Kode Akun')
                        ->helperText('Contoh: 1111, 4102'),
                ])->columnSpan(1),

                Select::make('normal_balance')
                    ->label('Saldo Normal (Otomatis)') // <-- Label diubah
                    ->options([
                        'Debit'  => 'Debit',
                        'Kredit' => 'Kredit',
                    ])
                    ->required()
                    ->disabled()     // <-- 1. TETAP TAMPIL, TAPI DIKUNCI
                    ->dehydrated(),  // <-- 2. INI KUNCINYA AGAR TETAP TERKIRIM

                Select::make('category')
                    ->label('Kategori Laporan (Otomatis)') // <-- Label diubah
                    ->options([
                        'Aset Lancar' => 'Aset Lancar',
                        'Aset Tidak Lancar' => 'Aset Tidak Lancar',
                        'Kewajiban Jangka Pendek' => 'Kewajiban Jangka Pendek',
                        'Kewajiban Jangka Panjang' => 'Kewajiban Jangka Panjang',
                        'Aset Neto' => 'Aset Neto',
                        'Laporan Penghasilan Komprehensif' => 'Laporan Penghasilan Komprehensif',
                    ])
                    ->required()
                    ->disabled()     // <-- 1. TETAP TAMPIL, TAPI DIKUNCI
                    ->dehydrated(),  // <-- 2. INI KUNCINYA AGAR TETAP TERKIRIM

                // System Code (Tetap hidden, sudah benar)
                TextInput::make('system_code')
                    ->unique(
                        table: 'accounts',
                        column: 'system_code',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('foundation_id', Auth::user()->foundation_id)
                    )
                    ->disabled(fn (?Account $record) => $record?->system_code !== null)
                    ->dehydrated()
                    ->hidden(),

            ])
            ->columns(2);
    }
}
