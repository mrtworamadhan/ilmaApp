<?php

namespace App\Filament\Yayasan\Pages;

use App\Filament\Traits\HasModuleAccess;
use App\Models\Bill;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use Filament\Tables\Summarizers\Sum;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

class LaporanTunggakan extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canViewAny(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string | UnitEnum | null $navigationGroup  = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Tunggakan';
    protected string $view = 'filament.yayasan.pages.laporan-tunggakan';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'laporan/tunggakan';

    // Hapus property individual, gunakan array filters saja
    public array $filters = [];

    public function mount(): void
    {
        $userSchoolId = auth()->user()->school_id;
        $isYayasanUser = auth()->user()->school_id === null;
        
        $this->filters = [
            'selectedSchool' => $isYayasanUser ? null : $userSchoolId,
            'selectedClass' => null,
            'selectedStudent' => null,
        ];
    }

    public function filterForm(Schema $form): Schema
    {
        $isYayasanUser = auth()->user()->school_id === null;
        $userSchoolId = auth()->user()->school_id;

        return $form
            ->statePath('filters')
            ->schema([
                Select::make('selectedSchool')
                    ->label('Filter Sekolah')
                    ->options(
                        School::where('foundation_id', Filament::getTenant()->id)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->live()
                    ->hidden(!$isYayasanUser)
                    ->placeholder('Semua Sekolah')
                    ->afterStateUpdated(function () {
                        // Reset class dan student ketika school berubah
                        $this->filters['selectedClass'] = null;
                        $this->filters['selectedStudent'] = null;
                    }),

                Select::make('selectedClass')
                    ->label('Filter Kelas')
                    ->options(function (callable $get) {
                        $schoolId = $this->getSelectedSchool();
                        if (!$schoolId) return [];
                        return SchoolClass::where('school_id', $schoolId)->pluck('name', 'id');
                    })
                    ->searchable()
                    ->live()
                    ->placeholder('Semua Kelas')
                    ->afterStateUpdated(function () {
                        // Reset student ketika class berubah
                        $this->filters['selectedStudent'] = null;
                    }),

                Select::make('selectedStudent')
                    ->label('Filter Siswa')
                    ->options(function (callable $get) {
                        $schoolId = $this->getSelectedSchool();
                        $classId = $this->filters['selectedClass'] ?? null;
                        
                        $query = Student::query()->where('foundation_id', Filament::getTenant()->id);
                        if ($schoolId) $query->where('school_id', $schoolId);
                        if ($classId) $query->where('class_id', $classId);
                        
                        return $query->pluck('full_name', 'id');
                    })
                    ->searchable()
                    ->live()
                    ->placeholder('Semua Siswa'),
            ]);
    }

    // Helper method untuk mendapatkan school ID yang dipilih
    protected function getSelectedSchool(): ?int
    {
        $isYayasanUser = auth()->user()->school_id === null;
        $userSchoolId = auth()->user()->school_id;
        
        return $isYayasanUser 
            ? ($this->filters['selectedSchool'] ?? null)
            : $userSchoolId;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                // Query dasar adalah Tagihan (Bill)
                $query = Bill::query()
                    ->where('foundation_id', Filament::getTenant()->id)
                    ->whereIn('status', ['unpaid', 'overdue']);

                // --- Terapkan Filter Keamanan ---
                $userSchoolId = auth()->user()->school_id;
                if ($userSchoolId) {
                    $query->where('school_id', $userSchoolId);
                }

                // --- Terapkan Filter Form ---
                $selectedSchool = $this->getSelectedSchool();
                $selectedClass = $this->filters['selectedClass'] ?? null;
                $selectedStudent = $this->filters['selectedStudent'] ?? null;
                
                $query->when($selectedSchool, function ($q) use ($selectedSchool) {
                    return $q->where('school_id', $selectedSchool);
                });
                
                $query->when($selectedClass, function ($q) use ($selectedClass) {
                    return $q->whereHas('student', fn($sq) => $sq->where('class_id', $selectedClass));
                });
                
                $query->when($selectedStudent, function ($q) use ($selectedStudent) {
                    return $q->where('student_id', $selectedStudent);
                });

                return $query->orderBy('due_date', 'asc');
            })
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Nama Siswa')
                    ->searchable(),
                
                TextColumn::make('student.schoolClass.name')
                    ->label('Kelas')
                    ->badge(),
                
                TextColumn::make('feeCategory.name')
                    ->label('Tagihan Untuk')
                    ->badge(),
                
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->label('Total Tunggakan')->money('IDR')),

                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
                
                BadgeColumn::make('status')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    }),
            ]);
    }

    // Method untuk reset filters
    public function resetFilters(): void
    {
        $userSchoolId = auth()->user()->school_id;
        $isYayasanUser = auth()->user()->school_id === null;
        
        $this->filters = [
            'selectedSchool' => $isYayasanUser ? null : $userSchoolId,
            'selectedClass' => null,
            'selectedStudent' => null,
        ];
    }
}