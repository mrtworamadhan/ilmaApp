<?php

namespace App\Filament\Yayasan\Pages;

use App\Filament\Traits\HasModuleAccess;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\School;
use App\Models\Bill;
use Filament\Actions\Action;
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
use Maatwebsite\Excel\Facades\Excel; 
use App\Exports\LaporanNeracaExport; // <-- Tambahkan ini
use Barryvdh\DomPDF\Facade\Pdf; // <-- Tambahkan ini
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanNeraca extends Page implements HasForms
{
    use InteractsWithForms, HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canAccess(): bool // <-- BENAR (Ini untuk Page)
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Laporan Neraca';
    protected string $view = 'filament.yayasan.pages.laporan-neraca'; // <-- Pastikan view-nya benar
    protected static string | UnitEnum | null $navigationGroup  = 'Laporan';
    protected static ?int $navigationSort = 5;
    protected static ?string $slug = 'laporan/neraca';

    public ?int $selectedSchool = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    // Properti untuk menyimpan hasil
    public $hasilAktiva = [];
    public $totalAktiva = 0;
    public $hasilKewajiban = [];
    public $totalKewajiban = 0;
    public $hasilEkuitas = [];
    public $totalEkuitas = 0;
    public $labaDitangguhkan = 0;
    public $labaRugiPeriodeIni = 0;
    public $totalKewajibanDanEkuitas = 0;
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action('exportExcel'), // Panggil method exportExcel

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->action('exportPdf'), // Panggil method exportPdf
        ];
    }
    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfYear()->toDateString();
        $this->endDate = Carbon::now()->endOfDay()->toDateString();
        $this->applyFilters();
    }

    public function filterForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('selectedSchool')
                    ->label('Pilih Sekolah')
                    ->options(fn () => School::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id'))
                    ->placeholder('Semua Sekolah (Level Yayasan)')
                    ->searchable()
                    ->hidden(fn () => !auth()->user()->hasRole('Admin Yayasan')),
                DatePicker::make('startDate')
                    ->label('Tanggal Mulai')
                    ->default($this->startDate)
                    ->required(),
                DatePicker::make('endDate')
                    ->label('Tanggal Selesai')
                    ->default($this->endDate)
                    ->required(),
            ])
            ->columns(3);
    }

    // ========================================================
    // KODE YANG HILANG (DITAMBAHKAN KEMBALI)
    // ========================================================
    protected function rules(): array
    {
        return [
            'selectedSchool' => 'nullable|integer',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ];
    }
    // ========================================================

    public function applyFilters(): void
    {
        $this->validate(); // <-- Panggilan ini sekarang valid

        $isYayasanUser = $this->selectedSchool === null && auth()->user()->hasRole('Admin Yayasan');
        $schoolId = $isYayasanUser ? null : ($this->selectedSchool ?? auth()->user()->school_id);

        $baseQuery = fn ($type) => JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.foundation_id', Filament::getTenant()->id)
            ->where('journals.date', '<=', $this->endDate) // Neraca dihitung s/d tanggal akhir
            ->when($schoolId, function ($q) use ($schoolId) {
                return $q->where('journals.school_id', $schoolId);
            })
            // REFACTOR Tipe Akun ISAK 35
            ->where('accounts.type', $type); 

        // --- 1. AMBIL ASET ---
        $this->hasilAktiva = $baseQuery('Aset') // <-- REFACTOR
            ->select('accounts.name', 'accounts.type',
                DB::raw('SUM(CASE WHEN journal_entries.type = "debit" THEN journal_entries.amount ELSE 0 END) as total_debit'),
                DB::raw('SUM(CASE WHEN journal_entries.type = "kredit" THEN journal_entries.amount ELSE 0 END) as total_kredit')
            )
            ->groupBy('accounts.name', 'accounts.type')
            ->get();
        
        $this->totalAktiva = 0;
        foreach ($this->hasilAktiva as $akun) {
            $akun->balance = $akun->total_debit - $akun->total_kredit;
            $this->totalAktiva += $akun->balance;
        }

        // --- 2. AMBIL LIABILITAS ---
        $this->hasilKewajiban = $baseQuery('Liabilitas') // <-- REFACTOR
            ->select('accounts.name', 'accounts.type',
                DB::raw('SUM(CASE WHEN journal_entries.type = "debit" THEN journal_entries.amount ELSE 0 END) as total_debit'),
                DB::raw('SUM(CASE WHEN journal_entries.type = "kredit" THEN journal_entries.amount ELSE 0 END) as total_kredit')
            )
            ->groupBy('accounts.name', 'accounts.type')
            ->get();
        
        $this->totalKewajiban = 0;
        foreach ($this->hasilKewajiban as $akun) {
            $akun->balance = $akun->total_kredit - $akun->total_debit;
            $this->totalKewajiban += $akun->balance;
        }

        // --- 3. AMBIL ASET NETO ---
        $this->hasilEkuitas = $baseQuery('Aset Neto') // <-- REFACTOR
            ->select('accounts.name', 'accounts.type',
                DB::raw('SUM(CASE WHEN journal_entries.type = "debit" THEN journal_entries.amount ELSE 0 END) as total_debit'),
                DB::raw('SUM(CASE WHEN journal_entries.type = "kredit" THEN journal_entries.amount ELSE 0 END) as total_kredit')
            )
            ->groupBy('accounts.name', 'accounts.type')
            ->get();
        
        $this->totalEkuitas = 0;
        foreach ($this->hasilEkuitas as $akun) {
            $akun->balance = $akun->total_kredit - $akun->total_debit;
            $this->totalEkuitas += $akun->balance;
        }

        // --- 4. HITUNG LABA DITANGGUHKAN (Laba s/d PERIODE SEBELUMNYA) ---
        $totalPendapatanLalu = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'Pendapatan') // <-- REFACTOR
            ->where('journals.foundation_id', Filament::getTenant()->id)
            ->where('journals.date', '<', $this->startDate) // SEBELUM Tanggal Mulai
            ->when($schoolId, fn ($q) => $q->where('journals.school_id', $schoolId))
            ->where('journal_entries.type', 'kredit')
            ->sum('journal_entries.amount');

        $totalBebanLalu = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'Beban') // <-- REFACTOR
            ->where('journals.foundation_id', Filament::getTenant()->id)
            ->where('journals.date', '<', $this->startDate) // SEBELUM Tanggal Mulai
            ->when($schoolId, fn ($q) => $q->where('journals.school_id', $schoolId))
            ->where('journal_entries.type', 'debit')
            ->sum('journal_entries.amount');
            
        $this->labaDitangguhkan = $totalPendapatanLalu - $totalBebanLalu;

        // --- 5. HITUNG LABA RUGI PERIODE INI (Laba SELAMA PERIODE BERJALAN) ---
        $totalPendapatan = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'Pendapatan') // <-- REFACTOR
            ->where('journals.foundation_id', Filament::getTenant()->id)
            ->whereBetween('journals.date', [$this->startDate, $this->endDate])
            ->when($schoolId, fn ($q) => $q->where('journals.school_id', $schoolId))
            ->where('journal_entries.type', 'kredit')
            ->sum('journal_entries.amount');

        $totalBeban = JournalEntry::join('journals', 'journal_entries.journal_id', '=', 'journals.id')
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->where('accounts.type', 'Beban') // <-- REFACTOR
            ->where('journals.foundation_id', Filament::getTenant()->id)
            ->whereBetween('journals.date', [$this->startDate, $this->endDate])
            ->when($schoolId, fn ($q) => $q->where('journals.school_id', $schoolId))
            ->where('journal_entries.type', 'debit')
            ->sum('journal_entries.amount');
            
        $this->labaRugiPeriodeIni = $totalPendapatan - $totalBeban;

        // --- 6. TOTAL EKUITAS TERMASUK LABA DITANGGUHKAN ---
        $this->totalEkuitas += ($this->labaDitangguhkan + $this->labaRugiPeriodeIni);

        // --- 7. TOTAL KEWAJIBAN + EKUITAS ---
        $this->totalKewajibanDanEkuitas = $this->totalKewajiban + $this->totalEkuitas;
    }
    public function exportExcel()
    {
        // 1. Jalankan filter terlebih dahulu untuk memastikan data terbaru
        $this->applyFilters();

        // 2. Siapkan data tambahan
        $namaSekolah = $this->selectedSchool
            ? School::find($this->selectedSchool)?->name
            : 'Semua Sekolah (Level Yayasan)';
            
        $fileName = "laporan_neraca_{$this->startDate}_sd_{$this->endDate}.xlsx";

        // 3. Panggil Maatwebsite Excel untuk download
        return Excel::download(new LaporanNeracaExport(
            $this->hasilAktiva,
            $this->totalAktiva,
            $this->hasilKewajiban,
            $this->totalKewajiban,
            $this->hasilEkuitas,
            $this->totalEkuitas,
            $this->labaDitangguhkan,
            $this->labaRugiPeriodeIni,
            $this->totalKewajibanDanEkuitas,
            $this->startDate,
            $this->endDate,
            $namaSekolah
        ), $fileName);
    }

    /**
     * Logika untuk Export PDF
     */
    public function exportPdf(): StreamedResponse
    {
        // 1. Jalankan filter terlebih dahulu untuk memastikan data terbaru
        $this->applyFilters();

        // 2. Siapkan data tambahan
        $namaSekolah = $this->selectedSchool
            ? School::find($this->selectedSchool)?->name
            : 'Semua Sekolah (Level Yayasan)';

        $fileName = "laporan_neraca_{$this->startDate}_sd_{$this->endDate}.pdf";

        // 3. Kumpulkan semua data untuk dikirim ke view
        $data = [
            'hasilAktiva' => $this->hasilAktiva,
            'totalAktiva' => $this->totalAktiva,
            'hasilKewajiban' => $this->hasilKewajiban,
            'totalKewajiban' => $this->totalKewajiban,
            'hasilEkuitas' => $this->hasilEkuitas,
            'totalEkuitas' => $this->totalEkuitas,
            'labaDitangguhkan' => $this->labaDitangguhkan,
            'labaRugiPeriodeIni' => $this->labaRugiPeriodeIni,
            'totalKewajibanDanEkuitas' => $this->totalKewajibanDanEkuitas,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'namaSekolah' => $namaSekolah,
        ];

        // 4. Load view dan data menggunakan DOMPDF
        $pdf = Pdf::loadView('exports.laporan-neraca', $data)
                   ->setPaper('a4', 'portrait'); // Atur ukuran kertas

        // 5. Kembalikan sebagai streamed response (download)
        return response()->streamDownload(
            fn() => print($pdf->output()),
            $fileName
        );
    }
}