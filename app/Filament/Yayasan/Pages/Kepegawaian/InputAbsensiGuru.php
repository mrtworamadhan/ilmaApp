<?php

namespace App\Filament\Yayasan\Pages\Kepegawaian;

use App\Filament\Traits\HasModuleAccess;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use App\Models\School;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use BackedEnum;
use UnitEnum;

class InputAbsensiGuru extends Page implements HasForms
{
    use HasModuleAccess;
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Kepegawaian';
    protected static ?string $title = 'Input Absensi Guru';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.yayasan.pages.kepegawaian.input-absensi-guru';
    protected static string $requiredModule = 'attendance';

    public ?array $data = [];
    public Collection $teachers;
    public array $attendanceStatus = [];
    public array $attendanceNotes = [];
    public array $timestampIn = [];
    public array $timestampOut = [];

    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule([
            'Admin Yayasan',
            'Admin Sekolah',
            'Staf Kesiswaan',
        ]);
    }

    public function mount(): void
    {
        $this->data = [
            'date' => now()->format('Y-m-d'),
            'school_id' => auth()->user()->school_id ?? null,
        ];

        $this->teachers = collect();
    }

    public function form(Schema $form): Schema
    {
        $schema = [
            Section::make('Filter Absensi Guru')
                ->columns(3)
                ->components([
                    Select::make('school_id')
                        ->label('Pilih Sekolah')
                        ->options(School::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->visible(fn () => auth()->user()->school_id === null),

                    DatePicker::make('date')
                        ->label('Tanggal Absensi')
                        ->required(),
                    Grid::make(1)
                        ->components([
                            Actions::make([
                                Action::make('loadTeachers')
                                    ->label('Muat Guru')
                                    ->icon('heroicon-o-magnifying-glass')
                                    ->color('primary')
                                    ->button()
                                    ->action(fn () => $this->loadTeachers()),
                            ]),
                        ])
                        ->columnSpanFull(),
                ]),
        ];

        // Jika sudah ada data guru yang dimuat, tampilkan form absensinya
        if ($this->teachers->isNotEmpty()) {
            $schema[] = Section::make('Daftar Absensi Guru')
                ->description('Isi status kehadiran setiap guru.')
                ->components([
                    ...$this->buildTeacherCards(),
                     Grid::make(1)->components([
                        Actions::make([
                            Action::make('saveAttendance')
                                ->label('Simpan Absensi')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->button()
                                ->action(fn () => $this->saveAttendance()),
                        ]),
                    ])
                ]);
        }

        return $form->components($schema)->statePath('data');
    }

    /**
     * Bangun struktur schema card guru (radio, jam, catatan)
     */
    protected function buildTeacherCards(): array
    {
        $components = [];

        foreach ($this->teachers as $teacher) {
            $components[] = Section::make($teacher->full_name)
                ->icon(Heroicon::OutlinedUser)
                ->description($teacher->nip ?? '-')
                ->columns(3)
                ->components([
                    Radio::make("attendanceStatus.{$teacher->id}")
                        ->label('Status Kehadiran')
                        ->options([
                            'H' => 'Hadir',
                            'I' => 'Izin',
                            'DL' => 'Dinas Luar',
                            'S' => 'Sakit',
                            'A' => 'Alfa',
                        ])
                        ->inline()
                        ->default('H')
                        ->reactive(),

                    TimePicker::make("timestampIn.{$teacher->id}")
                        ->label('Jam Masuk')
                        ->visible(fn () => in_array($this->attendanceStatus[$teacher->id] ?? 'H', ['H', 'DL'])),

                    TimePicker::make("timestampOut.{$teacher->id}")
                        ->label('Jam Pulang')
                        ->visible(fn () => in_array($this->attendanceStatus[$teacher->id] ?? 'H', ['H', 'DL'])),

                    TextInput::make("attendanceNotes.{$teacher->id}")
                        ->label('Keterangan')
                        ->placeholder('Tuliskan alasan izin/sakit...')
                        ->visible(fn () => in_array($this->attendanceStatus[$teacher->id] ?? '', ['I', 'S'])),
                    
                ])
                ->collapsed(false);
        }

        return $components;
    }

    public function loadTeachers(): void
    {
        $formData = $this->data;

        if (empty($formData['school_id']) || empty($formData['date'])) {
            Notification::make()
                ->title('Filter tidak lengkap')
                ->body('Silakan pilih sekolah dan tanggal terlebih dahulu.')
                ->warning()
                ->send();
            $this->teachers = collect();
            return;
        }

        $this->teachers = Teacher::where('school_id', $formData['school_id'])
            ->orderBy('full_name')
            ->get();

        if ($this->teachers->isEmpty()) {
            Notification::make()
                ->title('Tidak ada data guru di sekolah ini.')
                ->warning()
                ->send();
            return;
        }

        $existing = TeacherAttendance::where('school_id', $formData['school_id'])
            ->where('date', $formData['date'])
            ->get()
            ->keyBy('teacher_id');

        foreach ($this->teachers as $t) {
            $record = $existing->get($t->id);
            $this->attendanceStatus[$t->id] = $record->status ?? 'H';
            $this->attendanceNotes[$t->id] = $record->notes ?? '';
            $this->timestampIn[$t->id] = $record->timestamp_in ?? '07:00';
            $this->timestampOut[$t->id] = $record->timestamp_out ?? '15:00';
        }
    }

    public function saveAttendance(): void
    {
        $formData = $this->data;

        if (empty($formData['school_id']) || empty($formData['date']) || $this->teachers->isEmpty()) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Data filter atau daftar guru belum dimuat.')
                ->danger()
                ->send();
            return;
        }

        try {
            $records = [];
            foreach ($this->teachers as $t) {
                $status = $this->attendanceStatus[$t->id] ?? 'H';
                $isHadir = in_array($status, ['H', 'DL']);

                $records[] = [
                    'foundation_id' => Filament::getTenant()->id,
                    'school_id' => $formData['school_id'],
                    'teacher_id' => $t->id,
                    'reported_by_user_id' => auth()->id(),
                    'date' => $formData['date'],
                    'status' => $status,
                    'notes' => $this->attendanceNotes[$t->id] ?? '',
                    'timestamp_in' => $isHadir ? ($this->timestampIn[$t->id] ?? null) : null,
                    'timestamp_out' => $isHadir ? ($this->timestampOut[$t->id] ?? null) : null,
                    'method' => 'manual',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            TeacherAttendance::upsert(
                $records,
                ['teacher_id', 'date'],
                ['status', 'notes', 'timestamp_in', 'timestamp_out', 'method', 'reported_by_user_id', 'updated_at']
            );

            Notification::make()
                ->title('Absensi Guru Berhasil Disimpan')
                ->success()
                ->send();

            $this->teachers = collect();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
