<?php

namespace App\Filament\Yayasan\Resources\FeeCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class FeeCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kategori Biaya')
                    ->required()
                    ->helperText('Contoh: SPP Bulanan, Uang Gedung, Ekskul, Katering, dll.'),

                // Ini adalah Select yang cerdas
                Select::make('account_id')
                    ->label('Masuk ke Akun Pendapatan')
                    ->required()
                    ->relationship(
                        name: 'account', // Nama relasi di model FeeCategory
                        titleAttribute: 'name', // Tampilkan kolom 'name' dari tabel Account
                        // Kita filter pilihannya:
                        modifyQueryUsing: fn (Builder $query) => $query
                            // 1. Hanya tampilkan Akun milik Yayasan ini
                            ->where('foundation_id', Filament::getTenant()->id)
                            // 2. Hanya tampilkan Akun dengan tipe 'pendapatan'
                            ->where('type', 'pendapatan') 
                    )
                    ->searchable()
                    ->preload(),
            ]);
    }
}
