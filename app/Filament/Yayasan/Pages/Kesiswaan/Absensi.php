<?php

namespace App\Filament\Yayasan\Pages\Kesiswaan;

use App\Filament\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use BackedEnum;
use UnitEnum;

class Absensi extends Page implements HasForms
{
    use HasModuleAccess, InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Kesiswaan';
    protected static ?string $title = 'Input Absen';
    protected static ?int $navigationSort = 1;
    protected static string $requiredModule = 'attendance';

    protected string $view = 'filament.yayasan.pages.kesiswaan.absensi';

    public ?array $data = []; 
    public Collection $students;
    public array $attendanceStatus = [];
    public array $attendanceNotes = [];

    public function __construct()
    {
        $this->students = collect();
    }

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
        $this->form->fill();
        $this->students = collect();
    }

    /**
     * 🔹 Form utama (Filter + Daftar Siswa)
     */
    public function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->components([
                // 🔸 Bagian Filter dan tombol Load
                Section::make('Filter Pengambilan Absensi')
                    ->columns(3)
                    ->schema([
                        Select::make('class_id')
                            ->label('Pilih Kelas')
                            ->options(fn() => SchoolClass::where('school_id', auth()->user()->school_id)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive(),

                        DatePicker::make('date')
                            ->label('Tanggal Absensi')
                            ->required()
                            ->default(now())
                            ->reactive(),

                        // Tombol tampilkan siswa di bagian kanan
                        Actions::make([
                            \Filament\Actions\Action::make('load')
                                ->label('Tampilkan Siswa')
                                ->icon('heroicon-o-magnifying-glass')
                                ->color('primary')
                                ->action('loadStudents')
                        ])
                        ->columnSpan(1)
                        ->alignEnd()
                    ])
                    ->statePath('data'),

                // 🔸 Daftar siswa muncul setelah klik tombol
                Section::make('Daftar Siswa')
                    ->visible(fn() => $this->students->isNotEmpty())
                    ->schema(
                        $this->students->map(function ($student) {
                            return Section::make($student->full_name)
                                ->columns(2)
                                ->schema([
                                    Radio::make("attendanceStatus.{$student->id}")
                                        ->label('Status')
                                        ->options([
                                            'H' => 'Hadir',
                                            'S' => 'Sakit',
                                            'I' => 'Izin',
                                            'A' => 'Alpa',
                                        ])
                                        ->default('H')
                                        ->inline()
                                        ->reactive(),

                                    TextInput::make("attendanceNotes.{$student->id}")
                                        ->label('Keterangan (wajib jika Izin)')
                                        ->visible(fn(callable $get) => $get("attendanceStatus.{$student->id}") === 'I')
                                        ->required(fn(callable $get) => $get("attendanceStatus.{$student->id}") === 'I'),
                                    
                                ]);
                        })->toArray()
                    ),
                Actions::make([
                                        \Filament\Actions\Action::make('save')
                                            ->label('Simpan Absensi')
                                            ->icon('heroicon-o-check-circle')
                                            ->color('success')
                                            ->action('saveAttendance')
                                    ])
                                    ->alignCenter()
                // 🔸 Tombol Simpan di section terpisah di bawah
                // Section::make('Simpan Data Absensi')
                //     ->visible(fn() => $this->students->isNotEmpty())
                //     ->schema([
                //         Actions::make([
                //             \Filament\Actions\Action::make('save')
                //                 ->label('Simpan Absensi')
                //                 ->icon('heroicon-o-check-circle')
                //                 ->color('success')
                //                 ->action('saveAttendance')
                //         ])
                //         ->alignCenter()
                //     ]),
            ]);
    }

    public function loadStudents(): void
    {
        $filterData = $this->data;

        if (empty($filterData['class_id']) || empty($filterData['date'])) {
            Notification::make()
                ->title('Filter tidak lengkap')
                ->body('Silakan pilih kelas dan tanggal terlebih dahulu.')
                ->warning()
                ->send();
            return;
        }

        $this->students = Student::where('class_id', $filterData['class_id'])
            ->where('status', 'active')
            ->orderBy('full_name', 'asc')
            ->get();

        if ($this->students->isEmpty()) {
            Notification::make()
                ->title('Tidak Ada Siswa')
                ->body('Tidak ditemukan siswa aktif di kelas yang dipilih.')
                ->warning()
                ->send();
            return;
        }

        $existingAttendances = StudentAttendance::where('class_id', $filterData['class_id'])
            ->where('date', $filterData['date'])
            ->get()
            ->keyBy('student_id');

        foreach ($this->students as $student) {
            $existing = $existingAttendances->get($student->id);
            $this->attendanceStatus[$student->id] = $existing ? $existing->status : 'H';
            $this->attendanceNotes[$student->id] = $existing ? $existing->notes : '';
        }

        Notification::make()
            ->title('Data Siswa Dimuat')
            ->body("Berhasil memuat {$this->students->count()} siswa.")
            ->success()
            ->send();
    }

    public function saveAttendance(): void
    {
        $formData = $this->data;

        if (empty($formData['class_id']) || empty($formData['date']) || $this->students->isEmpty()) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Data siswa belum dimuat. Klik "Tampilkan Siswa" terlebih dahulu.')
                ->danger()
                ->send();
            return;
        }

        try {
            $records = [];

            foreach ($this->students as $student) {
                $records[] = [
                    'foundation_id' => Filament::getTenant()->id,
                    'school_id' => auth()->user()->school_id,
                    'class_id' => $formData['class_id'],
                    'student_id' => $student->id,
                    'reported_by_user_id' => auth()->id(),
                    'date' => $formData['date'],
                    'status' => $this->attendanceStatus[$student->id] ?? 'H',
                    'notes' => $this->attendanceNotes[$student->id] ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            StudentAttendance::upsert(
                $records,
                ['student_id', 'date'],
                ['status', 'notes', 'reported_by_user_id', 'updated_at']
            );

            Notification::make()
                ->title('Absensi Berhasil Disimpan')
                ->success()
                ->send();

            $this->students = collect();
            $this->attendanceStatus = [];
            $this->attendanceNotes = [];
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Menyimpan Absensi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
