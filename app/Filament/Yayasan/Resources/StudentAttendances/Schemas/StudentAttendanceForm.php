<?php

namespace App\Filament\Yayasan\Resources\StudentAttendances\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentAttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Edit Detail Absensi')
                    ->columns(2)
                    ->schema([
                        Select::make('student_id')
                            ->relationship('student', 'full_name')
                            ->label('Siswa')
                            ->disabled() // Tidak boleh ganti siswa
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
                            ])
                            ->required(), // Status Boleh diubah
                        TextInput::make('notes')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                        Select::make('reported_by_user_id')
                            ->relationship('reporter', 'name')
                            ->label('Diinput Oleh')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
