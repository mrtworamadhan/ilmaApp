<?php

namespace App\Filament\Yayasan\Resources\Teachers\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama')
                    ->columns(2)
                    ->schema([
                        Select::make('school_id')
                            ->label('Sekolah')
                            ->relationship('school', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabledOn('edit'),
                        
                        Select::make('user_id')
                            ->label('Akun Login (Opsional)')
                            ->relationship('user', 'name', fn (Builder $query) => 
                                $query->where('foundation_id', Filament::getTenant()->id)
                            )
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih akun user jika guru ini memiliki hak login ke sistem.'),

                        TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->columnSpanFull(),
                        
                        TextInput::make('nip')
                            ->label('NIP / No. Pegawai')
                            ->unique(ignoreRecord: true),
                        
                        TextInput::make('rfid_tag_id')
                            ->label('ID Kartu RFID')
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->placeholder('Tempelkan kartu untuk membaca ID'),
                        
                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ]),
                        
                        TextInput::make('phone')
                            ->label('No. Handphone')
                            ->tel(),
                        
                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir'),
                    ]),
                
            Section::make('Informasi Tambahan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('employment_status')
                            ->label('Status Kepegawaian')
                            ->helperText('Contoh: PNS, GTY, Honorer, dll.'),
                        
                        TextInput::make('education_level')
                            ->label('Pendidikan Terakhir')
                            ->helperText('Contoh: S1, S2, D3, dll.'),
                        
                        Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->columnSpanFull(),
                        
                        FileUpload::make('photo_path')
                            ->label('Foto Guru')
                            ->image()
                            ->directory('teacher-photos')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
