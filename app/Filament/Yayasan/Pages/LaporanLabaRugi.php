<?php

namespace App\Filament\Yayasan\Pages;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\School;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms; // <-- 1. Implement HasForms
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB; // <-- 2. Import DB
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

class LaporanLabaRugi extends Page implements HasForms
{
    // 3. Gunakan Trait
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Laporan Laba Rugi';
    protected string $view = 'filament.yayasan.pages.laporan-laba-rugi';
    protected static string | UnitEnum | null $navigationGroup  = 'Laporan';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'laporan/laba-rugi';

    // --- 4. Properti untuk menampung filter & hasil ---
    public ?int $selectedSchool = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    // Properti untuk menyimpan hasil query
    public $hasilPendapatan = [];
    public $totalPendapatan = 0;
    public $hasilBeban = [];
    public $totalBeban = 0;
    public $labaRugi = 0;
    public static function canAccess(): bool
    {
        // Ini sudah benar
        return auth()->user()->hasRole(['Admin Yayasan', 'Admin Sekolah']);
    }

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        
        $userSchoolId = auth()->user()->school_id;
        if ($userSchoolId) {
            $this->selectedSchool = $userSchoolId;
        }

        $this->filterForm->fill([
            'selectedSchool' => $this->selectedSchool,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);

        // Hitung otomatis saat halaman dibuka
        $this->applyFilters();
    }

    // --- 5. Definisi Form Filter ---
    public function filterForm(Schema $form): Schema
    {
        return $form
            ->components([
                Select::make('selectedSchool')
                    ->label('Filter per Sekolah')
                    ->options(
                        School::where('foundation_id', Filament::getTenant()->id)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    // Sembunyikan jika user level sekolah
                    ->hidden(fn () => auth()->user()->school_id !== null)
                    ->placeholder('Semua Sekolah (Gabungan)'), // <-- Opsi gabungan

                DatePicker::make('startDate')
                    ->label('Tanggal Mulai')
                    ->default(now()->startOfMonth())
                    ->required(),

                DatePicker::make('endDate')
                    ->label('Tanggal Selesai')
                    ->default(now()->endOfMonth())
                    ->required(),
            ]);
    }

    // --- 6. Fungsi untuk menghitung ---
    public function applyFilters(): void
    {
        $data = $this->filterForm->getState();
        $this->startDate = $data['startDate'];
        $this->endDate = $data['endDate'];

        // Tentukan filter sekolah
        $userSchoolId = auth()->user()->school_id;
        $schoolId = $userSchoolId ?? $data['selectedSchool']; // Ambil dari user dulu, baru filter

        // --- QUERY PENDAPATAN ---
        $this->hasilPendapatan = Account::query()
            ->where('foundation_id', Filament::getTenant()->id)
            ->where('type', 'pendapatan') //
            // Hitung total 'amount' dari journal_entries
            ->withSum([
                'journalEntries as total' => fn (Builder $query) => $query
                    ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                    ->whereBetween('journals.date', [$this->startDate, $this->endDate])
                    ->when($schoolId, fn ($q) => $q->where('journals.school_id', $schoolId))
                    // Pendapatan dihitung dari KREDIT
                    ->where('journal_entries.type', 'kredit')
            ], 'amount')
            ->get();
        
        $this->totalPendapatan = $this->hasilPendapatan->sum('total');
        

        // --- QUERY BEBAN ---
        $this->hasilBeban = Account::query()
            ->where('foundation_id', Filament::getTenant()->id)
            ->where('type', 'beban') //
            ->withSum([
                'journalEntries as total' => fn (Builder $query) => $query
                    ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                    ->whereBetween('journals.date', [$this->startDate, $this->endDate])
                    ->when($schoolId, fn ($q) => $q->where('journals.school_id', $schoolId))
                    // Beban dihitung dari DEBIT
                    ->where('journal_entries.type', 'debit')
            ], 'amount')
            ->get();
        
        $this->totalBeban = $this->hasilBeban->sum('total');

        // --- HITUNG LABA RUGI ---
        $this->labaRugi = $this->totalPendapatan - $this->totalBeban;
    }
}