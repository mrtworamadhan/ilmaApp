<?php

namespace App\Filament\Yayasan\Pages\Kesiswaan;

use App\Filament\Traits\HasModuleAccess;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Support\Enums\IconPosition;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions;
use BackedEnum;
use UnitEnum;

class RekapAbsensiSiswa extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasModuleAccess;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|UnitEnum|null $navigationGroup = 'Kesiswaan';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Rekapitulasi Absensi Siswa';
    protected string $view = 'filament.yayasan.pages.kesiswaan.rekap-absensi-siswa';

    protected static string $requiredModule = 'attendance';

    public ?array $filterData = [];
    public Collection $reportData;
    public ?SchoolClass $selectedClass = null;

    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule([
            'Admin Yayasan',
            'Admin Sekolah',
            'Staf Kesiswaan',
            'Wali Kelas',
        ]);
    }

    public function mount(): void
    {
        // Atur nilai default untuk filter (bulan ini)
        $this->filterData = [
            'school_id' => auth()->user()->school_id ?? null,
            'class_id' => null,
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
        ];
    }

    /**
     * 3. Definisi Form Filter
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
                            ->reactive() // <-- PENTING agar kelas ter-update
                            ->afterStateUpdated(fn ($set) => $set('class_id', null))
                            ->visible(fn () => auth()->user()->school_id === null), // Hanya Admin Yayasan
                        
                        Select::make('class_id')
                            ->label('Pilih Kelas')
                            ->options(function (callable $get) {
                                $schoolId = $get('school_id') ?? auth()->user()->school_id;
                                if (!$schoolId) return [];
                                return SchoolClass::where('school_id', $schoolId)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->reactive() // <-- PENTING agar tabel ter-update
                            ->required(),
                        
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
     * 4. Definisi Tabel Laporan
     */
    public function table(Table $table): Table
    {
        return $table
            // 5. Kueri Kustom (Ini adalah intinya)
            ->query(function (): Builder {
                // Ambil data filter langsung dari property
                $filterData = $this->filterData ?? [];

                if (empty($filterData['class_id']) || empty($filterData['date_from']) || empty($filterData['date_to'])) {
                    return Student::query()->whereRaw('1 = 0'); // kosong
                }

                $dateFrom = $filterData['date_from'];
                $dateTo = $filterData['date_to'];

                return Student::query()
                    ->where('class_id', $filterData['class_id'])
                    ->where('status', 'active')
                    ->withCount([
                        'attendances as H_count' => fn(Builder $q) => $q->where('status', 'H')->whereBetween('date', [$dateFrom, $dateTo]),
                        'attendances as S_count' => fn(Builder $q) => $q->where('status', 'S')->whereBetween('date', [$dateFrom, $dateTo]),
                        'attendances as I_count' => fn(Builder $q) => $q->where('status', 'I')->whereBetween('date', [$dateFrom, $dateTo]),
                        'attendances as A_count' => fn(Builder $q) => $q->where('status', 'A')->whereBetween('date', [$dateFrom, $dateTo]),
                    ])
                    ->orderBy('full_name');
            })
            // 6. Definisi Kolom
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nama Siswa')
                    ->searchable(),
                
                TextColumn::make('H_count')
                    ->label('Hadir (H)')
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
                    ->state(fn (Student $record) => $record->H_count)
                    ->color('success')
                    ->weight('bold'),

                TextColumn::make('total_ketidakhadiran')
                    ->label('Total Absen')
                    ->alignCenter()
                    ->state(fn (Student $record) => $record->S_count + $record->I_count + $record->A_count)
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->paginated(false); // Matikan paginasi agar semua siswa di kelas tampil
            // TODO: Tambahkan Aksi Export di sini nanti
    }
}
