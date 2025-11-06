<?php

namespace App\Filament\Yayasan\Pages\Kepegawaian;

use App\Filament\Traits\HasModuleAccess;
use App\Models\School;
use App\Models\Teacher; // <-- Ganti dari Student
use App\Models\TeacherAttendance; // <-- Ganti dari StudentAttendance
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class RekapAbsensiGuru extends Page implements HasForms, HasTable
{
    // 1. Gunakan semua Trait yang diperlukan
    use InteractsWithForms;
    use InteractsWithTable;
    use HasModuleAccess;

    // --- Konfigurasi Halaman ---
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|UnitEnum|null $navigationGroup = 'Kepegawaian';
    protected string $view = 'filament.yayasan.pages.kepegawaian.rekap-absensi-guru'; // Path ke file blade
    protected static ?string $title = 'Rekapitulasi Absensi Guru';
    protected static ?int $navigationSort = 3;
    
    // --- Trait Modul ---
    protected static string $requiredModule = 'attendance';

    // --- Properti Livewire ---
    public ?array $filterData = []; // Untuk menyimpan data form filter

    /**
     * Cek Izin Akses (Role + Modul)
     */
    public static function canAccess(): bool
    {
        // TODO: Sesuaikan dengan role 'Staf Kepegawaian' jika perlu
        return static::canAccessWithRolesAndModule([
            'Admin Yayasan', 
            'Admin Sekolah',
            'Staf Kesiswaan',
            'Wali Kelas',
        ]);
    }

    /**
     * Inisialisasi Halaman
     */
    public function mount(): void
    {
        // Atur nilai default untuk filter (bulan ini)
        $this->filterData = [
            'school_id' => auth()->user()->school_id ?? null,
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ];
    }

    /**
     * 2. Definisi Form Filter
     */
    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Section::make('Filter Laporan')
                    ->columns(4)
                    ->schema([
                        Select::make('school_id')
                            ->label('Sekolah')
                            ->options(School::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->reactive() // <-- PENTING agar tabel ter-update
                            ->required(fn () => auth()->user()->school_id === null) // Wajib jika Admin Yayasan
                            ->visible(fn () => auth()->user()->school_id === null), // Hanya Admin Yayasan
                        
                        // Tidak perlu filter kelas, karena guru terikat ke sekolah
                        
                        DatePicker::make('date_from')
                            ->label('Dari Tanggal')
                            ->required()
                            ->reactive(), // <-- PENTING agar tabel ter-update

                        DatePicker::make('date_to')
                            ->label('Sampai Tanggal')
                            ->required()
                            ->reactive() // <-- PENTING agar tabel ter-update
                            ->afterOrEqual('date_from'),
                    ]),
            ])
            ->statePath('filterData'); // Bind ke properti $filterData
    }

    /**
     * 3. Definisi Tabel Laporan (Pola A)
     */
    public function table(Table $table): Table
    {
        return $table
            // Kueri Kustom (Inti Logika)
            ->query(function (): Builder {
                $filterData = $this->form->getState();
                
                // Tentukan school_id
                $schoolId = auth()->user()->school_id ?? $filterData['school_id'];
                
                // Jika filter belum lengkap, jangan tampilkan apa-apa
                if (empty($schoolId) || empty($filterData['date_from']) || empty($filterData['date_to'])) {
                    return Teacher::query()->whereRaw('1 = 0'); // Kueri kosong
                }

                $dateFrom = $filterData['date_from'];
                $dateTo = $filterData['date_to'];

                // Mulai kueri dari Teacher
                return Teacher::query()
                    ->where('school_id', $schoolId)
                    // Hitung (count) relasi 'attendances' DENGAN filter
                    ->withCount([
                        'attendances as H_count' => fn(Builder $q) => $q->where('status', 'H')->whereBetween('date', [$dateFrom, $dateTo]),
                        'attendances as S_count' => fn(Builder $q) => $q->where('status', 'S')->whereBetween('date', [$dateFrom, $dateTo]),
                        'attendances as I_count' => fn(Builder $q) => $q->where('status', 'I')->whereBetween('date', [$dateFrom, $dateTo]),
                        'attendances as A_count' => fn(Builder $q) => $q->where('status', 'A')->whereBetween('date', [$dateFrom, $dateTo]),
                        'attendances as DL_count' => fn(Builder $q) => $q->where('status', 'DL')->whereBetween('date', [$dateFrom, $dateTo]),
                    ])
                    ->orderBy('full_name');
            })
            // Definisi Kolom
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nama Guru')
                    ->searchable(),
                
                TextColumn::make('H_count')
                    ->label('Hadir (H)')
                    ->alignCenter(),
                
                TextColumn::make('DL_count')
                    ->label('Dinas Luar (DL)')
                    ->alignCenter(),
                
                TextColumn::make('S_count')
                    ->label('Sakit (S)')
                    ->alignCenter(),
                
                TextColumn::make('I_count')
                    ->label('Izin (I)')
                    ->alignCenter(),
                
                TextColumn::make('A_count')
                    ->label('Alpa (A)')
                    ->alignCenter(),
                
                // Kolom Total (dihitung 'on-the-fly')
                TextColumn::make('total_kehadiran')
                    ->label('Total Hadir')
                    ->alignCenter()
                    ->state(fn (Teacher $record) => $record->H_count + $record->DL_count)
                    ->color('success')
                    ->weight('bold'),

                TextColumn::make('total_ketidakhadiran')
                    ->label('Total Absen')
                    ->alignCenter()
                    ->state(fn (Teacher $record) => $record->S_count + $record->I_count + $record->A_count)
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->paginated(false); // Matikan paginasi agar semua guru tampil
    }
}