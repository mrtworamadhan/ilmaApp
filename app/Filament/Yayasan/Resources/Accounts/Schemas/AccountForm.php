<?php

namespace App\Filament\Yayasan\Resources\Accounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode Akun'),
                TextInput::make('name')
                    ->label('Nama Akun')
                    ->required(),
                Select::make('type')
                    ->label('Tipe Akun')
                    ->options([
                        'aktiva' => 'Aktiva',
                        'kewajiban' => 'Kewajiban',
                        'ekuitas' => 'Ekuitas',
                        'pendapatan' => 'Pendapatan',
                        'beban' => 'Beban',
                    ])
                    ->required(),
                TextInput::make('category')
                    ->label('Kategori')
                    ->helperText('Contoh: Kas, Bank, Piutang, Gaji, ATK'),
            ]);
    }
}
