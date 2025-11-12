<?php

namespace App\Filament\Kantin\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image')
                    ->label('Foto Produk')
                    ->image() 
                    ->directory('product-images')
                    ->disk('public')
                    ->visibility('public')
                    ->nullable()
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label('Nama Produk')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->nullable()
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Select::make('status')
                    ->options([
                        'available' => 'Tersedia',
                        'unavailable' => 'Habis',
                    ])
                    ->required()
                    ->default('available'),
            ]);
    }
}
