<?php

namespace App\Filament\Yayasan\Resources\FeeStructures\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class FeeStructureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Aturan Biaya')
                    ->required()
                    ->columnSpanFull()
                    ->helperText('Contoh: SPP Bulanan SD, Uang Gedung SMP 2025'),
                
                Select::make('school_id')
                    ->label('Berlaku untuk Sekolah')
                    ->required()
                    ->relationship(
                        name: 'school',
                        titleAttribute: 'name',
                        // Hanya tampilkan sekolah milik yayasan ini
                        modifyQueryUsing: fn (Builder $query) => 
                            $query->where('foundation_id', Filament::getTenant()->id)
                    )
                    ->preload()
                    ->searchable(),
                
                Select::make('fee_category_id')
                    ->label('Kategori Biaya')
                    ->required()
                    ->relationship(
                        name: 'feeCategory',
                        titleAttribute: 'name',
                        // Hanya tampilkan kategori milik yayasan ini
                        modifyQueryUsing: fn (Builder $query) => 
                            $query->where('foundation_id', Filament::getTenant()->id)
                    )
                    ->preload()
                    ->searchable(),
                    
                TextInput::make('grade_level')
                    ->label('Target Tingkat (Opsional)')
                    ->helperText('Kosongkan jika berlaku untuk semua tingkat. Isi jika spesifik (misal: 1, 7, 10).')
                    ->nullable(),

                TextInput::make('amount')
                    ->label('Nominal (Rp)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),

                Select::make('billing_cycle')
                    ->label('Siklus Tagihan')
                    ->options([
                        'monthly' => 'Bulanan (Monthly)',
                        'yearly' => 'Tahunan (Yearly)',
                        'one_time' => 'Sekali Bayar (One-time)',
                    ])
                    ->required(),
                
                Toggle::make('is_active')
                    ->label('Aturan ini Aktif?')
                    ->default(true),
            ]);
    }
}
