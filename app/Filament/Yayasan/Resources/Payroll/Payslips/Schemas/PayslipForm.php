<?php

namespace App\Filament\Yayasan\Resources\Payroll\Payslips\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;

class PayslipForm
{
    
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Slip Gaji')
                    ->components([
                        TextInput::make('paylsip.teacher.full_name')
                            ->label('Nama Guru')
                            ->disabled(),
                        TextInput::make('month')
                            ->label('Bulan')
                            ->formatStateUsing(fn (string $state): string => date('F', mktime(0, 0, 0, (int)$state, 1)))
                            ->disabled(),
                        TextInput::make('year')
                            ->label('Tahun')
                            ->disabled(),
                    ])->columnSpanFull(),
                
                Section::make('Ringkasan Gaji')
                    ->components([
                        TextInput::make('total_allowance')
                            ->label('Total Pendapatan')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),
                        TextInput::make('total_deduction')
                            ->label('Total Potongan')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),
                        TextInput::make('net_pay')
                            ->label('Gaji Bersih (THP)')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),
                    ])->columnSpanFull(),

                // --- KUNCI #2: MENAMPILKAN RINCIAN SLIP (FOTO HISTORIS) ---
                Section::make('Rincian Komponen Gaji')
                    ->components([
                        Repeater::make('details') // <-- Relasi ke 'payslipDetails'
                            ->label('Rincian')
                            ->relationship()
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('component_name')
                                    ->label('Nama Komponen')
                                    ->disabled(),
                                TextInput::make('type')
                                    ->label('Tipe')
                                    ->formatStateUsing(fn (string $state): string => $state === 'allowance' ? 'Pendapatan' : 'Potongan')
                                    ->disabled(),
                                TextInput::make('amount')
                                    ->label('Nominal')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled(),
                            ])
                            ->disabled(),
                    ])->columnSpanFull(),
            ]);
    }
}
