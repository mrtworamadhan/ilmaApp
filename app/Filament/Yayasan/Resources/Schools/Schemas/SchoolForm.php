<?php

namespace App\Filament\Yayasan\Resources\Schools\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SchoolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Sekolah')
                    ->required(),
                
                Select::make('level')
                    ->label('Jenjang')
                    ->options([
                        // Sesuai ERD 
                        'tk' => 'TK / PAUD',
                        'sd' => 'SD / MI',
                        'smp' => 'SMP / MTS',
                        'sma' => 'SMA / MA',
                        'pondok' => 'Pondok Pesantren',
                    ])
                    ->required(),

                TextInput::make('headmaster')
                    ->label('Nama Kepala Sekolah'),
                
                TextInput::make('phone')
                    ->label('Telepon Sekolah'),

                Textarea::make('address')
                    ->label('Alamat')
                    ->columnSpanFull(),
            ]);
    }
}
