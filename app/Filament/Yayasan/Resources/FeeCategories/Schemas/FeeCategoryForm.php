<?php

namespace App\Filament\Yayasan\Resources\FeeCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Facades\Filament;
use Filament\Forms\Components\Toggle;
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

                Select::make('account_id')
                    ->label('Masuk ke Akun Pendapatan')
                    ->required()
                    ->relationship(
                        name: 'account', // Nama relasi di model FeeCategory
                        titleAttribute: 'name', // Tampilkan kolom 'name' dari tabel Account
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->where('foundation_id', Filament::getTenant()->id)
                            ->where('type', 'pendapatan') 
                    )
                    ->searchable()
                    ->preload(),

                Toggle::make('is_optional')
                    ->label('Biaya Ini Opsional?')
                    ->helperText('Jika AKTIF, biaya ini tidak akan ditagih otomatis ke semua siswa. Biaya ini harus di-assign manual ke siswa tertentu (Contoh: Jemputan, Ekskul).')
                    ->default(false)
                    ->columnSpanFull(),
            ]);
    }
}
