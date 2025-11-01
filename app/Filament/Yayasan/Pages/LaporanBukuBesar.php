<?php

namespace App\Filament\Yayasan\Pages;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\School;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms; // <-- 1. Implement HasForms
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable; // <-- 2. Implement HasTable
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use BackedEnum;
use UnitEnum;

// 3. Implement HasForms dan HasTable
class LaporanBukuBesar extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Laporan Buku Besar';
    protected string $view = 'filament.yayasan.pages.laporan-buku-besar';
    protected static string | UnitEnum | null $navigationGroup  = 'Laporan';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'laporan/buku-besar';

    public ?int $selectedAccount = null;
    public ?int $selectedSchool = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['Admin Yayasan', 'Admin Sekolah']);
    }

    public function mount(): void
    {
        // Set tanggal default (bulan ini)
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        
        // Cek jika user level sekolah
        if (!auth()->user()->school_id === null) {
            $this->selectedSchool = auth()->user()->school_id;
        }

        // Isi data form filter
        $this->filterForm->fill([
            'selectedAccount' => $this->selectedAccount,
            'selectedSchool' => $this->selectedSchool,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);
    }

    // --- 6. Definisi Form Filter ---
    public function filterForm(Schema $form): Schema
    {
        return $form
            ->statePath('data') // <-- Nama properti bebas
            ->components([
                Select::make('selectedAccount')
                    ->label('Pilih Akun')
                    ->options(
                        Account::where('foundation_id', Filament::getTenant()->id)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->required()
                    ->live(), // <-- Update 'live'

                Select::make('selectedSchool')
                    ->label('Filter per Sekolah (Opsional)')
                    ->options(
                        School::where('foundation_id', Filament::getTenant()->id)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    // Sembunyikan jika user level sekolah
                    ->hidden(fn () => auth()->user()->school_id !== null)
                    ->live(),

                DatePicker::make('startDate')
                    ->label('Tanggal Mulai')
                    ->default(now()->startOfMonth())
                    ->required()
                    ->live(),

                DatePicker::make('endDate')
                    ->label('Tanggal Selesai')
                    ->default(now()->endOfMonth())
                    ->required()
                    ->live(),
            ]);
    }

    // --- 7. Tombol 'Apply' Filter ---
    // (Method ini dipanggil oleh form di view)
    public function applyFilters(): void
    {
        $data = $this->filterForm->getState();
        $this->selectedAccount = $data['selectedAccount'];
        $this->selectedSchool = $data['selectedSchool'];
        $this->startDate = $data['startDate'];
        $this->endDate = $data['endDate'];
    }

    // --- 8. Definisi Tabel Hasil ---
    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                // Query dasar adalah JournalEntry
                $query = JournalEntry::query()
                    ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                    ->where('journals.foundation_id', Filament::getTenant()->id)
                    // Pilih kolom yg dibutuhkan
                    ->selectRaw("
                        journal_entries.id as id,
                        journals.date, 
                        journals.description, 
                        (CASE WHEN journal_entries.type = 'debit' THEN journal_entries.amount ELSE 0 END) as debit,
                        (CASE WHEN journal_entries.type = 'kredit' THEN journal_entries.amount ELSE 0 END) as kredit
                    ");

                // --- Terapkan Filter ---
                if ($this->selectedAccount) {
                    $query->where('journal_entries.account_id', $this->selectedAccount);
                }

                if ($this->startDate) {
                    $query->whereDate('journals.date', '>=', $this->startDate);
                }
                
                if ($this->endDate) {
                    $query->whereDate('journals.date', '<=', $this->endDate);
                }

                // Filter sekolah
                $userSchoolId = auth()->user()->school_id;
                if ($userSchoolId) {
                    // Admin Sekolah hanya lihat jurnal sekolahnya
                    $query->where('journals.school_id', $userSchoolId);
                } elseif ($this->selectedSchool) {
                    // Admin Yayasan memfilter
                    $query->where('journals.school_id', $this->selectedSchool);
                }

                return $query->orderBy('journals.date', 'asc');
            })
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->wrap(),
                
                TextColumn::make('debit')
                    ->label('Debit')
                    ->money('IDR')
                    ->alignEnd(),
                
                TextColumn::make('kredit')
                    ->label('Kredit')
                    ->money('IDR')
                    ->alignEnd(),
            ])
            ->paginated(false); // Tampilkan semua hasil
    }
}