<?php

namespace App\Filament\Yayasan\Resources\TeacherAttendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeacherAttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Edit Detail Absensi Guru')
                    ->columns(2)
                    ->schema([
                        Select::make('teacher_id')
                            ->relationship('teacher', 'full_name')
                            ->label('Guru')
                            ->disabled() // Tidak boleh ganti guru
                            ->columnSpanFull(),
                        
                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->disabled(), // Tidak boleh ganti tanggal

                        Select::make('status')
                            ->label('Status Absensi')
                            ->options([
                                'H' => 'Hadir',
                                'S' => 'Sakit',
                                'I' => 'Izin',
                                'A' => 'Alpa',
                                'DL' => 'Dinas Luar',
                            ])
                            ->required() // Status Boleh diubah
                            ->reactive(), // Agar jam tampil/sembunyi
                        
                        TimePicker::make('timestamp_in')
                            ->label('Jam Masuk')
                            ->visible(fn ($get) => $get('status') === 'H' || $get('status') === 'DL'),

                        TimePicker::make('timestamp_out')
                            ->label('Jam Pulang')
                            ->visible(fn ($get) => $get('status') === 'H' || $get('status') === 'DL'),
                        
                        TextInput::make('notes')
                            ->label('Keterangan')
                            ->columnSpanFull()
                            ->visible(fn ($get) => !in_array($get('status'), ['H', 'DL'])), // Tampil jika Sakit/Izin/Alpa

                        Select::make('reported_by_user_id')
                            ->relationship('reporter', 'name')
                            ->label('Diinput Oleh')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
