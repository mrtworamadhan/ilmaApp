<?php

namespace App\Filament\Yayasan\Resources\AdmissionRegistrations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdmissionRegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Status Pendaftaran')
                    ->schema([
                        Select::make('status')
                            ->label('Status Pendaftaran')
                            ->options([
                                'baru' => 'Baru',
                                'diverifikasi' => 'Terverifikasi (Bayar)',
                                'seleksi' => 'Proses Seleksi',
                                'diterima' => 'Diterima',
                                'ditolak' => 'Ditolak',
                                'menjadi_siswa' => 'Sudah Menjadi Siswa',
                            ])
                            ->required()
                            ->default('baru'),
                        TextInput::make('registration_wave')
                            ->label('Gelombang Pendaftaran')
                            ->disabled(), // Diisi form publik
                        TextInput::make('registration_number')
                            ->label('Nomor Pendaftaran')
                            ->disabled(), // Diisi form publik
                    ])->columns(3),

                Section::make('Data Calon Siswa (Read-Only)')
                    ->description('Data ini diisi oleh pendaftar melalui form publik.')
                    ->schema([
                        Select::make('school_id')
                            ->relationship('school', 'name')
                            ->label('Sekolah Pilihan')
                            ->disabled(),
                        TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->disabled(),
                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options(['L' => 'Laki-laki', 'P' => 'Perempuan'])
                            ->disabled(),
                        TextInput::make('birth_place')
                            ->label('Tempat Lahir')
                            ->disabled(),
                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->disabled(),
                        TextInput::make('religion')
                            ->label('Agama')
                            ->disabled(),
                        TextInput::make('previous_school')
                            ->label('Asal Sekolah')
                            ->disabled(),
                    ])->columns(2),
                
                Section::make('Data Orang Tua / Wali (Read-Only)')
                    ->schema([
                        TextInput::make('parent_name')
                            ->label('Nama Orang Tua/Wali')
                            ->disabled(),
                        TextInput::make('parent_phone')
                            ->label('No. HP Orang Tua/Wali')
                            ->disabled(),
                        TextInput::make('parent_email')
                            ->label('Email Orang Tua/Wali')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Berkas Pendaftaran')
                    ->schema([
                        FileUpload::make('payment_proof_path')
                            ->label('Bukti Bayar Formulir')
                            ->directory('ppdb-payments')
                            ->disabled(),
                    ]),
            ]);
    }
}
