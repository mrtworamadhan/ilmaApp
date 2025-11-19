<?php

namespace App\Filament\Yayasan\Pages;

use App\Filament\Exports\TunggakanExporter;
use App\Filament\Traits\HasModuleAccess;
use App\Models\Bill;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
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
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

class LaporanTunggakan extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canAccess(): bool // <-- BENAR (Ini untuk Page)
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string | UnitEnum | null $navigationGroup  = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Tunggakan';
    protected string $view = 'filament.yayasan.pages.laporan-tunggakan';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'laporan/tunggakan';

    public ?array $data = []; 

    public function mount(): void
    {
        $this->resetFilters(); // Panggil resetFilters untuk inisialisasi
        $this->form->fill($this->data); // Isi form dengan data
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data') // Gunakan statePath 'data'
            ->components([
                Select::make('selectedSchool')
                    ->label('Filter Sekolah')
                    ->options(fn () => School::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id'))
                    ->placeholder('Semua Sekolah')
                    ->searchable()
                    ->hidden(fn () => auth()->user()->school_id !== null) // Sembunyikan jika Admin Sekolah
                    ->live(), // Buat reaktif
                
                Select::make('selectedClass')
                    ->label('Filter Kelas')
                    ->options(function (callable $get) {
                        $schoolId = $get('selectedSchool') ?? auth()->user()->school_id;
                        if (!$schoolId) return [];
                        return SchoolClass::where('school_id', $schoolId)->pluck('name', 'id');
                    })
                    ->placeholder('Semua Kelas')
                    ->searchable()
                    ->live(), // Buat reaktif
                
                Select::make('selectedStudent')
                    ->label('Filter Siswa')
                    ->options(function (callable $get) {
                        $classId = $get('selectedClass');
                        if (!$classId) return [];
                        return Student::where('class_id', $classId)->pluck('name', 'id');
                    })
                    ->placeholder('Semua Siswa')
                    ->searchable()
                    ->live(), // Buat reaktif
            ]);
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = Bill::query()
                    ->with(['student.schoolClass']) // Eager load
                    ->where('foundation_id', Filament::getTenant()->id)
                    ->whereIn('status', ['unpaid', 'overdue']); // Hanya ambil yg belum lunas

                // Ambil filter dari state 'data'
                $filters = $this->data;
                $userSchoolId = auth()->user()->school_id;

                if ($userSchoolId) {
                    // Admin Sekolah hanya lihat sekolahnya
                    $query->where('school_id', $userSchoolId);
                } elseif (!empty($filters['selectedSchool'])) {
                    // Admin Yayasan memfilter
                    $query->where('school_id', $filters['selectedSchool']);
                }

                if (!empty($filters['selectedClass'])) {
                    $query->whereHas('student', fn (Builder $q) => $q->where('class_id', $filters['selectedClass']));
                }

                if (!empty($filters['selectedStudent'])) {
                    $query->where('student_id', $filters['selectedStudent']);
                }

                return $query->orderBy('due_date', 'asc')->orderBy('id', 'asc');
            })
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('student.schoolClass.name')
                    ->label('Kelas')
                    ->badge(),
                
                // ===================================================
                // PERBAIKAN 1: Ganti 'feeCategory.name'
                // ===================================================
                TextColumn::make('description')
                    ->label('Keterangan Tagihan')
                    ->badge(),
                
                // ===================================================
                // PERBAIKAN 2 & 3: Ganti 'amount' ke 'total_amount'
                // ===================================================
                TextColumn::make('total_amount') // <-- Perbaikan 2
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(Sum::make('total_amount')->label('Total Tunggakan')->money('IDR')), // <-- Perbaikan 3

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
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export ke Excel')
                    ->exporter(TunggakanExporter::class)
                    ->formats([
                            ExportFormat::Xlsx,
                        ])
                    
            ]);
    }

    // Method untuk reset filters
    public function resetFilters(): void
    {
        $userSchoolId = auth()->user()->school_id;
        $isYayasanUser = auth()->user()->school_id === null;
        
        $this->data = [
            'selectedSchool' => $isYayasanUser ? null : $userSchoolId,
            'selectedClass' => null,
            'selectedStudent' => null,
        ];
        
        // Reset form juga
        $this->form->fill($this->data);
    }
}