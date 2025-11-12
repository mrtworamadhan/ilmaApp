<?php

namespace App\Filament\Yayasan\Resources\PayrollComponents\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PayrollComponentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Komponen')
                    ->placeholder('Cth: Gaji Pokok, Tunjangan Transport, Potongan BPJS')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('type')
                    ->label('Tipe Komponen')
                    ->options([
                        'allowance' => 'Pendapatan (Tunjangan)',
                        'deduction' => 'Potongan',
                    ])
                    ->required(),
            ]);
    }
}
