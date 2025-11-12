<?php

namespace App\Filament\Admin\Resources\Foundations\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FoundationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                ->schema([
                    // Field Input Data Yayasan
                    Section::make('Informasi Yayasan')
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama Yayasan')
                                ->required()
                                ->string(),
                            TextInput::make('address')
                                ->label('Alamat')
                                ->string(),
                            TextInput::make('phone')
                                ->label('Telepon')
                                ->tel(),
                            TextInput::make('email')
                                ->label('Email Kontak')
                                ->email(),
                            TextInput::make('npwp')
                                ->label('NPWP'),
                        ]),
                ])->columnSpan(1),

            // Kolom untuk Modul
            Group::make()
                ->schema([
                    Section::make('Modul Aktif')
                        ->schema([
                            CheckboxList::make('enabled_modules')
                                ->label('Silakan centang modul yang dibeli:')
                                ->options([
                                    'finance'       => 'Keuangan (Akuntansi, SPP, Budgeting)',
                                    'payroll'       => 'Payroll Gaji Guru & Karyawan',
                                    'savings'       => 'Tabungan Siswa (Dasar)',
                                    'cashless'      => 'Cashless (Kantin & RFID)',
                                    'ppdb'          => 'PPDB (Penerimaan Siswa Baru)',
                                    'attendance'    => 'Absensi (Siswa & Guru)',
                                    'student_records' => 'Catatan Siswa (Pelanggaran/Prestasi)',
                                    'lms'           => 'LMS (Akademik & Raport)',
                                    'announcement'  => 'Pengumuman',
                                ])
                                ->columns(1)
                                ->helperText('Setiap modul yang dicentang akan aktif di panel Yayasan ini.'),
                        ]),
                ])->columnSpan(1), // Grup ini mengambil 1 kolom
            ])->columns(2);
    }
}
