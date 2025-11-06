<?php

namespace App\Filament\Yayasan\Resources\AdmissionBatches\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AdmissionBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Gelombang Pendaftaran')
                    ->columns(2)
                    ->schema([
                        // Hanya Admin Yayasan yang bisa memilih sekolah
                        // Admin Sekolah otomatis terisi school_id nya (di handle mutate)
                        Select::make('school_id')
                            ->label('Sekolah')
                            ->relationship(
                                'school',
                                'name',
                                fn(Builder $query) =>
                                $query->where('foundation_id', Filament::getTenant()->id)
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn() => auth()->user()->school_id === null), // Hanya untuk Admin Yayasan

                        TextInput::make('name')
                            ->label('Nama Gelombang')
                            ->helperText('Contoh: Gelombang 1 2025/2026')
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Deskripsi (Opsional)')
                            ->columnSpanFull(),

                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required(),

                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->afterOrEqual('start_date'), // Tanggal selesai > tanggal mulai

                        TextInput::make('fee_amount')
                            ->label('Biaya Formulir')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('Gelombang Dibuka')
                            ->helperText('Aktifkan jika pendaftaran untuk gelombang ini sedang dibuka.')
                            ->default(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
