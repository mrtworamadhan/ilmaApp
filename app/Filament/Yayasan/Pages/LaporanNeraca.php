<?php

namespace App\Filament\Yayasan\Pages;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\School;
use App\Models\Bill;
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
    protected static ?int $navigationSort = 5;
    protected static ?string $slug = 'laporan/neraca';

    public ?int $selectedSchool = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public $hasilAktiva = [];
    public $totalAktiva = 0;
    public $hasilKewajiban = [];
    public $totalKewajiban = 0;
    public $hasilEkuitas = [];
    public $totalEkuitas = 0;
    public $labaRugiPeriodeIni = 0;
    
    public $piutangTunggakan = 0;
    public $totalAktivaTermasukPiutang = 0;
    public $labaDitangguhkan = 0;
    public $totalEkuitasTermasukLabaDitangguhkan = 0;
    public static function canAccess(): bool
    {
        // Ini sudah benar
        return auth()->user()->hasRole(['Admin Yayasan', 'Admin Sekolah']);
    }

    public function mount(): void
    {
        $this->startDate = now()->startOfYear()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        
        $userSchoolId = auth()->user()->school_id;
        $isYayasanUser = auth()->user()->school_id === null;
        
        $this->selectedSchool = $isYayasanUser ? null : $userSchoolId;

        $this->filterForm->fill([
            'selectedSchool' => $this->selectedSchool,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
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
        $isYayasanUser = auth()->user()->school_id === null;
        
        $schoolId = $isYayasanUser ? ($data['selectedSchool'] ?? null) : $userSchoolId;

        // --- FUNGSI HELPER UNTUK QUERY SALDO ---
        $getAccountBalance = function ($accountType, $endDate, $schoolId) use ($isYayasanUser) {
            $query = Account::query()
                ->where('foundation_id', Filament::getTenant()->id)
                ->where('type', $accountType)
                ->select('accounts.id', 'accounts.name')
                ->addSelect(['balance' => JournalEntry::select(DB::raw("SUM(CASE WHEN journal_entries.type = 'debit' THEN amount ELSE -amount END)"))
                    ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                    ->whereColumn('journal_entries.account_id', 'accounts.id')
                    ->where('journals.date', '<=', $endDate)
                    ->when($schoolId || !$isYayasanUser, function ($q) use ($schoolId) {
                        return $q->where('journals.school_id', $schoolId);
                    })
                ])
                ->withCasts(['balance' => 'float']);
            return $query->get();
        };

        $getAccountBalanceKredit = function ($accountType, $endDate, $schoolId) use ($isYayasanUser) {
             $query = Account::query()
                ->where('foundation_id', Filament::getTenant()->id)
                ->where('type', $accountType)
                ->select('accounts.id', 'accounts.name')
                ->addSelect(['balance' => JournalEntry::select(DB::raw("SUM(CASE WHEN journal_entries.type = 'kredit' THEN amount ELSE -amount END)"))
                    ->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                    ->whereColumn('journal_entries.account_id', 'accounts.id')
                    ->where('journals.date', '<=', $endDate)
                    ->when($schoolId || !$isYayasanUser, function ($q) use ($schoolId) {
                        return $q->where('journals.school_id', $schoolId);
                    })
                ])
                ->withCasts(['balance' => 'float']);
            return $query->get();
        };

        // --- 1. HITUNG AKTIVA ---
        $this->hasilAktiva = $getAccountBalance('aktiva', $this->endDate, $schoolId);
        $this->totalAktiva = $this->hasilAktiva->sum('balance');

        // --- 2. HITUNG PIUTANG DARI TUNGGAKAN ---
        $this->piutangTunggakan = Bill::query()
            ->where('foundation_id', Filament::getTenant()->id)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->when($schoolId || !$isYayasanUser, function ($q) use ($schoolId) {
                return $q->where('school_id', $schoolId);
            })
            ->sum('amount');

        // --- 3. TOTAL AKTIVA + PIUTANG TUNGGAKAN ---
        $this->totalAktivaTermasukPiutang = $this->totalAktiva + $this->piutangTunggakan;

        // --- 4. HITUNG KEWAJIBAN ---
        $this->hasilKewajiban = $getAccountBalanceKredit('kewajiban', $this->endDate, $schoolId);
        $this->totalKewajiban = $this->hasilKewajiban->sum('balance');

        // --- 5. HITUNG EKUITAS ---
        $this->hasilEkuitas = $getAccountBalanceKredit('ekuitas', $this->endDate, $schoolId);
        $this->totalEkuitas = $this->hasilEkuitas->sum('balance');

        // --- 6. HITUNG LABA DITANGGUHKAN (PENYEIMBANG PIUTANG) ---
        $this->labaDitangguhkan = $this->piutangTunggakan;

        // --- 7. HITUNG LABA RUGI PERIODE BERJALAN ---
        $totalPendapatan = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'pendapatan')
            ->where('journals.foundation_id', Filament::getTenant()->id)
            ->whereBetween('journals.date', [$this->startDate, $this->endDate])
            ->when($schoolId || !$isYayasanUser, function ($q) use ($schoolId) {
                return $q->where('journals.school_id', $schoolId);
            })
            ->where('journal_entries.type', 'kredit')
            ->sum('journal_entries.amount');

        $totalBeban = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'beban')
            ->where('journals.foundation_id', Filament::getTenant()->id)
            ->whereBetween('journals.date', [$this->startDate, $this->endDate])
            ->when($schoolId || !$isYayasanUser, function ($q) use ($schoolId) {
                return $q->where('journals.school_id', $schoolId);
            })
            ->where('journal_entries.type', 'debit')
            ->sum('journal_entries.amount');
            
        $this->labaRugiPeriodeIni = $totalPendapatan - $totalBeban;

        // --- 8. TOTAL EKUITAS TERMASUK LABA DITANGGUHKAN ---
        $this->totalEkuitasTermasukLabaDitangguhkan = $this->totalEkuitas + $this->labaRugiPeriodeIni + $this->labaDitangguhkan;
    }
}