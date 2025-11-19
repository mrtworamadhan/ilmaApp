<?php

namespace App\Filament\Yayasan\Pages;

use App\Models\FeeStructure;
use Filament\Actions\BulkAction;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use App\Models\School;
use App\Models\Student;
use App\Models\FeeCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use BackedEnum;
use UnitEnum;


// Implement HasForms dan HasTable
class AssignOptionalFees extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Biaya';
    protected static ?string $navigationLabel = 'Assign Biaya Opsional';
    protected static ?string $title = 'Assign Biaya Opsional (Massal)';
    protected string $view = 'filament.yayasan.pages.assign-optional-fees';

    // Properti untuk menyimpan data filter
    public ?array $data = [];

    public function mount(): void
    {
        // Isi form dengan data kosong (atau default)
        $this->form->fill();
        $this->data = $this->form->getState(); // Inisialisasi $data
    }

    // --- FORM FILTER DI ATAS ---
    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data') // Simpan data filter ke properti $data
            ->components([
                Select::make('school_id')
                    ->label('Pilih Sekolah')
                    ->options(
                        School::where('foundation_id', Filament::getTenant()->id)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->live() // <-- Buat reaktif
                    ->required(),
                
                Select::make('fee_structure_id') // <-- Ganti nama field
                    ->label('Pilih Aturan Biaya Opsional')
                    ->options(function (Get $get) {
                        $schoolId = $get('school_id');
                        if (blank($schoolId)) {
                            return []; // Kosongkan jika sekolah belum dipilih
                        }
                        
                        // Ambil FeeStructure yang sekolahnya cocok
                        // DAN yang Kategori-nya 'is_optional' = true
                        return FeeStructure::query()
                            ->where('school_id', $schoolId)
                            ->whereHas('feeCategory', fn (Builder $q) => $q->where('is_optional', true))
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->live() 
                    ->required(),
            ])->columns(2);
    }

    protected function getStudentQuery(): Builder
    {
        // Ambil filter data dari state
        $schoolId = $this->data['school_id'] ?? null;
        $feeStructureId = $this->data['fee_structure_id'] ?? null;

        // Query dasar: Ambil siswa
        $query = Student::query()
            ->with('schoolClass')
            ->where('school_id', $schoolId);

        if ($feeStructureId) {
            $subQuery = DB::table('student_optional_fees')
                ->selectRaw('1')
                ->whereColumn('student_optional_fees.student_id', 'students.id')
                ->where('student_optional_fees.fee_structure_id', $feeStructureId)
                ->limit(1);

            // Gunakan selectSub alih-alih DB::raw
            $query->addSelect(['students.*'])
                  ->selectSub($subQuery, 'is_assigned');
        } else {
            $query->addSelect(['students.*', DB::raw("0 as is_assigned")]);
        }
        
        return $query;
    }

    // --- TABEL SISWA DI BAWAH ---
    public function table(Table $table): Table
    {

        return $table
            ->query(fn () => $this->getStudentQuery())

            ->columns([
                TextColumn::make('nis')->label('NIS')->searchable(),
                TextColumn::make('full_name')->label('Nama Siswa')->searchable(),
                TextColumn::make('schoolClass.name')->label('Kelas'),
                
                // Kolom Status (mengecek kolom virtual 'is_assigned')
                IconColumn::make('is_assigned')
                    ->label('Status Terdaftar')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->bulkActions([
                BulkAction::make('assign')
                    ->label('Assign Biaya ke Siswa Terpilih')
                    ->icon('heroicon-o-check')
                    ->action(function (Collection $records) { // 1. HAPUS 'array $data'
                        
                        // 2. Ambil ID dari '$this->data' (state halaman)
                        $feeStructureId = $this->data['fee_structure_id'] ?? null; 
                        
                        if (!$feeStructureId) {
                            Notification::make()->title('Gagal!')->body('Pilih aturan biaya opsional terlebih dahulu.')->danger()->send();
                            return;
                        }
                        foreach ($records as $student) {
                            $student->optionalFees()->syncWithoutDetaching([$feeStructureId]); 
                        }
                        Notification::make()->title('Siswa berhasil di-assign!')->success()->send();
                    }),
                
                BulkAction::make('unassign')
                    ->label('Un-assign Biaya dari Siswa Terpilih')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) { // 1. HAPUS 'array $data'
                        
                        // 2. Ambil ID dari '$this->data' (state halaman)
                        $feeStructureId = $this->data['fee_structure_id'] ?? null; 
                        
                        if (!$feeStructureId) {
                            Notification::make()->title('Gagal!')->body('Pilih aturan biaya opsional terlebih dahulu.')->danger()->send();
                            return;
                        }
                        foreach ($records as $student) {
                            $student->optionalFees()->detach($feeStructureId); 
                        }
                        Notification::make()->title('Biaya berhasil di-unassign!')->warning()->send();
                    }),
            ]);
    }
}