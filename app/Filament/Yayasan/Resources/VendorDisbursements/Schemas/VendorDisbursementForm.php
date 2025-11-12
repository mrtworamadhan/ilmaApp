<?php

namespace App\Filament\Yayasan\Resources\VendorDisbursements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

class VendorDisbursementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()->schema([
                    TextInput::make('vendor.name')
                        ->label('Vendor/Kantin')
                        ->disabled(),
                    TextInput::make('school.name')
                        ->label('Sekolah')
                        ->disabled(),
                ])->columns(2),
                TextInput::make('amount')
                    ->label('Jumlah Diajukan')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
                Textarea::make('notes')
                    ->label('Catatan dari Vendor')
                    ->disabled()
                    ->columnSpanFull(),
                
                Fieldset::make('Status Pemrosesan')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'requested' => 'Diajukan',
                                'approved' => 'Disetujui',
                                'paid' => 'Dibayar',
                                'rejected' => 'Ditolak',
                            ])
                            ->disabled(),
                        DateTimePicker::make('processed_at')
                            ->label('Waktu Diproses')
                            ->disabled(),
                        TextInput::make('processor.name')
                            ->label('Diproses Oleh')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }
}
