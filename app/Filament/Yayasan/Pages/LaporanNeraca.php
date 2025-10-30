<?php

namespace App\Filament\Yayasan\Pages;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\School;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

class LaporanNeraca extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Laporan Neraca';
    protected string $view = 'filament.yayasan.pages.laporan-neraca';
    protected static string | UnitEnum | null $navigationGroup  = 'Laporan';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'laporan/neraca';

    // --- Properti untuk filter & hasil ---
    public ?int $selectedSchool = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    // Properti untuk hasil query
    public $hasilAktiva = [];
    public $totalAktiva = 0;
    public $hasilKewajiban = [];
    public $totalKewajiban = 0;
    public $hasilEkuitas = [];
    public $totalEkuitas = 0; // Modal Awal Saja
    public $labaRugiPeriodeIni = 0; // Laba/Rugi dari P&L

    public function mount(): void
    {
        // Neraca biasanya "per tanggal" akhir, dan L/R adalah "selama periode"
        $this->startDate = now()->startOfYear()->format('Y-m-d'); // L/R dihitung dari awal tahun
        $this->endDate = now()->format('Y-m-d'); // Neraca dihitung per hari ini
        
        $userSchoolId = auth()->user()->school_id;
        if ($userSchoolId) {
            $this->selectedSchool = $userSchoolId;
        }

        $this->filterForm->fill([
            'selectedSchool' => $this->selectedSchool,
            'startDate' => $this->startDate, // Filter L/R
            'endDate' => $this->endDate,       // Filter Neraca
        ]);

        $this->applyFilters();
    }

    // --- Definisi Form Filter ---
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
                    ->hidden(fn () => auth()->user()->school_id !== null)
                    ->placeholder('Semua Sekolah (Gabungan)'),

                DatePicker::make('startDate')
                    ->label('Laba/Rugi Mulai Tgl')
                    ->default(now()->startOfYear())
                    ->required(),

                DatePicker::make('endDate')
                    ->label('Posisi Neraca per Tgl')
                    ->default(now())
                    ->required(),
            ]);
    }

    // --- Fungsi untuk menghitung ---
    public function applyFilters(): void
    {
        $data = $this->filterForm->getState();
        $this->startDate = $data['startDate'];
        $this->endDate = $data['endDate'];

        $userSchoolId = auth()->user()->school_id;
        $schoolId = $userSchoolId ?? $data['selectedSchool'];

        // --- FUNGSI HELPER UNTUK QUERY SALDO ---
        $getAccountBalance = function ($accountType, $endDate, $schoolId) {
            $query = Account::query()
                ->where('foundation_id', Filament::getTenant()->id)
                ->where('type', $accountType)
                ->select('accounts.id', 'accounts.name') // Pilih kolom yg kita mau
                // Gunakan Subquery untuk menghitung saldo
                ->addSelect(['balance' => JournalEntry::select(DB::raw("SUM(CASE WHEN journal_entries.type = 'debit' THEN amount ELSE -amount END)"))
                    ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                    ->whereColumn('journal_entries.account_id', 'accounts.id') // Link subquery
                    ->where('journals.date', '<=', $endDate)
                    ->when($schoolId, fn ($q) => $q->where('journals.school_id', $schoolId))
                ])
                ->withCasts(['balance' => 'float']); // Ubah hasil jadi angka
            return $query->get();
        };

        // Saldo normal Akun Kewajiban & Ekuitas adalah Kredit
        $getAccountBalanceKredit = function ($accountType, $endDate, $schoolId) {
             $query = Account::query()
                ->where('foundation_id', Filament::getTenant()->id)
                ->where('type', $accountType)
                ->select('accounts.id', 'accounts.name')
                // Gunakan Subquery untuk menghitung saldo
                ->addSelect(['balance' => JournalEntry::select(DB::raw("SUM(CASE WHEN journal_entries.type = 'kredit' THEN amount ELSE -amount END)"))
                    ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                    ->whereColumn('journal_entries.account_id', 'accounts.id') // Link subquery
                    ->where('journals.date', '<=', $endDate)
                    ->when($schoolId, fn ($q) => $q->where('journals.school_id', $schoolId))
                ])
                ->withCasts(['balance' => 'float']);
            return $query->get();
        };

        // --- 1. HITUNG AKTIVA ---
        $this->hasilAktiva = $getAccountBalance('aktiva', $this->endDate, $schoolId);
        $this->totalAktiva = $this->hasilAktiva->sum('balance');

        // --- 2. HITUNG KEWAJIBAN ---
        $this->hasilKewajiban = $getAccountBalanceKredit('kewajiban', $this->endDate, $schoolId);
        $this->totalKewajiban = $this->hasilKewajiban->sum('balance');

        // --- 3. HITUNG EKUITAS (Modal Awal Saja) ---
        $this->hasilEkuitas = $getAccountBalanceKredit('ekuitas', $this->endDate, $schoolId);
        $this->totalEkuitas = $this->hasilEkuitas->sum('balance');

        // --- 4. HITUNG LABA RUGI PERIODE BERJALAN ---
        // (Perhitungan ini terpisah, hanya untuk periode yg dipilih)
        $totalPendapatan = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'pendapatan')
            ->where('journals.foundation_id', Filament::getTenant()->id)
            ->whereBetween('journals.date', [$this->startDate, $this->endDate])
            ->when($schoolId, fn ($q) => $q->where('journals.school_id', $schoolId))
            ->where('journal_entries.type', 'kredit') // Pendapatan = Kredit
            ->sum('journal_entries.amount');

        $totalBeban = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'beban')
            ->where('journals.foundation_id', Filament::getTenant()->id)
            ->whereBetween('journals.date', [$this->startDate, $this->endDate])
            ->when($schoolId, fn ($q) => $q->where('journals.school_id', $schoolId))
            ->where('journal_entries.type', 'debit') // Beban = Debit
            ->sum('journal_entries.amount');
            
        $this->labaRugiPeriodeIni = $totalPendapatan - $totalBeban;
    }
}