<?php

namespace App\Filament\Kantin\Resources\SaleTransactions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

class SaleTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('transaction_code')
                    ->label('Kode Transaksi')
                    ->disabled(),
                TextInput::make('buyer.name') // Ambil dari relasi morphs
                    ->label('Pembeli')
                    ->disabled(),
                TextInput::make('total_amount')
                    ->label('Total Belanja')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
                DateTimePicker::make('created_at')
                    ->label('Waktu Transaksi')
                    ->disabled(),

                // Tampilkan detail "items" (JSON)
                Repeater::make('items')
                    ->label('Detail Barang')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->disabled(),
                        TextInput::make('quantity')
                            ->label('Jml')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('price')
                            ->label('Harga Satuan')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),
                    ])
                    ->disabled() // Repeater-nya di-disable
                    ->columnSpanFull(),
            ]);
    }
}
