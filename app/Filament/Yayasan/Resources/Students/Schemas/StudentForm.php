<?php

namespace App\Filament\Yayasan\Resources\Students\Schemas;

use App\Models\SchoolClass;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                TextInput::make('name')
                    ->label('Nama Lengkap Siswa')
                    ->required()
                    ->columnSpanFull(),

                // --- LOGIKA MULTI-LEVEL UNTUK SEKOLAH & KELAS ---
                
                // 1. Dropdown Sekolah (Hanya untuk Admin Yayasan)
                Select::make('school_id')
                    ->label('Sekolah')
                    ->relationship(
                        name: 'school',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => 
                            $query->where('foundation_id', Filament::getTenant()->id)
                    )
                    ->searchable()
                    ->preload()
                    ->reactive() // <-- Ini membuat field 'class_id' "mendengarkan"
                    ->required()
                    ->hidden(!$isYayasanUser), // Sembunyikan jika bukan Admin Yayasan

                // 2. Field Tersembunyi (Hanya untuk Admin Sekolah)
                Hidden::make('school_id')
                    ->default(auth()->user()->school_id)
                    ->hidden($isYayasanUser), // Sembunyikan jika user = Admin Yayasan

                // 3. Dropdown Kelas (Reaktif)
                Select::make('class_id')
                    ->label('Kelas')
                    ->required()
                    ->options(function (\Filament\Schemas\Components\Utilities\Get $get) use ($isYayasanUser) {
                        // Ambil school_id, baik dari dropdown (Yayasan) atau hidden (Sekolah)
                        $schoolId = $isYayasanUser ? $get('school_id') : auth()->user()->school_id;

                        if (!$schoolId) {
                            return []; // Jika sekolah belum dipilih, kosongkan
                        }
                        // Ambil kelas HANYA dari sekolah yang dipilih
                        return SchoolClass::where('school_id', $schoolId)
                                   ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload(),

                TextInput::make('nis')
                    ->label('NIS (Nomor Induk Siswa)')
                    ->unique(ignoreRecord: true)
                    ->nullable(),
            
                Select::make('status')
                    ->label('Status Siswa')
                    ->options([
                        'new' => 'Siswa Baru',
                        'active' => 'Aktif',
                        'inactive' => 'Non-Aktif',
                        'graduated' => 'Lulus',
                    ])
                    ->default('new')
                    ->required(),

                Select::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'Laki-laki' => 'Laki-laki',
                        'Perempuan' => 'Perempuan',
                    ]),

                DatePicker::make('birth_date')
                    ->label('Tanggal Lahir'),

                // --- KELOMPOK AKUN ORANG TUA ---
                Select::make('parent_id')
                    ->label('Akun Orang Tua (Opsional)')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->where('foundation_id', Filament::getTenant()->id)
                            ->where('role', 'orangtua') // ERD [cite: 119]
                    )
                    ->searchable()
                    ->preload()
                    // ->createOptionForm(fn(Form $form) => UserForm::configure($form)) // Kita comment dulu
                    ->helperText('Jika akun orang tua belum ada, bisa dibuat nanti.'),
            ]);
    }
}
