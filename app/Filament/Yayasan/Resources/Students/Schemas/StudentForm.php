<?php

namespace App\Filament\Yayasan\Resources\Students\Schemas;

use App\Models\SchoolClass;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Forms\Get; // <-- Penting untuk form reaktif
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        $isYayasanUser = auth()->user()->school_id === null;
        return $schema
            ->components([
                Tabs::make('Data Siswa')->tabs([
                
                // ===========================================
                // TAB 1: DATA SISWA
                // ===========================================
                Tab::make('Data Siswa')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Section::make('Informasi Akademik & Status')
                            ->columns(2)
                            ->schema([
                                Select::make('school_id')
                                    ->label('Sekolah')
                                    ->relationship('school', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (callable $set) => $set('class_id', null)),

                                Select::make('class_id')
                                    ->label('Kelas')
                                    ->options(function (callable $get) {
                                        $schoolId = $get('school_id');
                                        if (!$schoolId) {
                                            return [];
                                        }
                                        return SchoolClass::where('school_id', $schoolId)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                
                                Select::make('parent_id')
                                    ->label('Orang Tua (User)')
                                    ->relationship('parent', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Pilih akun user orang tua/wali yang akan login.'),

                                Select::make('status')
                                    ->label('Status Siswa')
                                    ->options([
                                        'active' => 'Aktif',
                                        'graduated' => 'Lulus',
                                        'moved' => 'Pindah',
                                        'inactive' => 'Nonaktif',
                                    ])
                                    ->required()
                                    ->default('active'),
                            ]),

                        Section::make('Identitas Pribadi')
                            ->columns(2)
                            ->schema([
                                TextInput::make('full_name') // Ganti dari 'name'
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('nickname')
                                    ->label('Nama Panggilan'),
                                TextInput::make('nis')
                                    ->label('NIS (Nomor Induk Siswa)')
                                    ->required(),
                                TextInput::make('nisn')
                                    ->label('NISN')
                                    ->unique(ignoreRecord: true),
                                TextInput::make('rfid_tag_id')
                                    ->label('ID Kartu RFID')
                                    ->unique(ignoreRecord: true) // Validasi unique, tapi abaikan record saat ini (untuk edit)
                                    ->nullable()
                                    ->placeholder('Tempelkan kartu untuk membaca ID'),
                                Select::make('gender')
                                    ->label('Jenis Kelamin')
                                    ->options([
                                        'L' => 'Laki-laki',
                                        'P' => 'Perempuan',
                                    ]),
                                TextInput::make('birth_place')
                                    ->label('Tempat Lahir'),
                                DatePicker::make('birth_date')
                                    ->label('Tanggal Lahir'),
                            ]),

                        Section::make('Detail Tambahan & Kontak')
                            ->columns(2)
                            ->schema([
                                TextInput::make('religion')
                                    ->label('Agama'),
                                TextInput::make('citizenship')
                                    ->label('Kewarganegaraan')
                                    ->default('WNI'),
                                TextInput::make('child_order')
                                    ->label('Anak Ke-')
                                    ->numeric(),
                                TextInput::make('siblings_count')
                                    ->label('Jumlah Saudara')
                                    ->numeric(),
                                Textarea::make('address')
                                    ->label('Alamat Lengkap')
                                    ->columnSpanFull(),
                                TextInput::make('phone')
                                    ->label('No. Handphone')
                                    ->tel(),
                                FileUpload::make('photo_path')
                                    ->label('Foto Siswa')
                                    ->image()
                                    ->disk('public')
                                    ->directory('student-photos')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // ===========================================
                // TAB 2: DATA ORANG TUA
                // ===========================================
                Tab::make('Data Orang Tua')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Section::make('Data Ayah')
                            ->columns(2)
                            ->schema([
                                TextInput::make('father_name')
                                    ->label('Nama Ayah'),
                                TextInput::make('father_education')
                                    ->label('Pendidikan Terakhir Ayah'),
                                TextInput::make('father_job')
                                    ->label('Pekerjaan Ayah'),
                            ]),
                        
                        Section::make('Data Ibu')
                            ->columns(2)
                            ->schema([
                                TextInput::make('mother_name')
                                    ->label('Nama Ibu'),
                                TextInput::make('mother_education')
                                    ->label('Pendidikan Terakhir Ibu'),
                                TextInput::make('mother_job')
                                    ->label('Pekerjaan Ibu'),
                            ]),
                    ]),

                // ===========================================
                // TAB 3: DATA WALI (OPSIONAL)
                // ===========================================
                Tab::make('Data Wali')
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Section::make('Data Wali (Isi jika berbeda dengan Orang Tua)')
                            ->columns(2)
                            ->schema([
                                TextInput::make('guardian_name')
                                    ->label('Nama Wali'),
                                TextInput::make('guardian_relationship')
                                    ->label('Hubungan dengan Siswa'),
                                TextInput::make('guardian_phone')
                                    ->label('No. Handphone Wali')
                                    ->tel(),
                                Textarea::make('guardian_address')
                                    ->label('Alamat Wali')
                                    ->columnSpanFull(),
                            ]),
                    ]),

            ])->columnSpanFull(),
        ]);
    }
}
