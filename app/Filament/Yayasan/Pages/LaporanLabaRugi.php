<?php

namespace App\Filament\Yayasan\Pages;

use App\Exports\LaporanLabaRugiExport;
use App\Filament\Traits\HasModuleAccess;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\School;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Facades\Filament;
use Filament\Schemas\Schema; // Ganti namespace
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;


class LaporanLabaRugi extends Page implements HasForms
{
    use InteractsWithForms, HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    // --- REFACTOR 1: Ganti Istilah ---
    protected static ?string $navigationLabel = 'Laporan Penghasilan Komprehensif'; 
    protected string $view = 'filament.yayasan.pages.laporan-laba-rugi';
    protected static string | UnitEnum | null $navigationGroup  = 'Laporan';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'laporan/penghasilan-komprehensif';
    protected ?string $heading = 'Laporan Penghasilan Komprehensif'; 
    // ---------------------------------

    public ?int $selectedSchool = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    // --- PROPERTI UNTUK KOLOM "TANPA PEMBATASAN" ---
    public $hasilPendapatanTidakTerikat = [];
    public $totalPendapatanTidakTerikat = 0;
    public $hasilBeban = [];
    public $totalBeban = 0;
    public $totalPelepasanDana_Kredit = 0; // (Jurnal #2 Sisi Kredit)
    public $totalSurplusDefisit_TidakTerikat = 0;

    // --- PROPERTI UNTUK KOLOM "DENGAN PEMBATASAN" ---
    public $hasilPendapatanTerikat = [];
    public $totalPendapatanTerikat = 0;
    public $totalPelepasanDana_Debit = 0; // (Jurnal #2 Sisi Debit)
    public $totalSurplusDefisit_Terikat = 0;
    
    // --- PROPERTI UNTUK KOLOM "TOTAL" ---
    public $totalPendapatan = 0;
    public $totalBebanGabungan = 0; // Beban (hanya 1 kolom)
    public $totalPerubahanAsetNeto = 0; // Total Surplus/Defisit

    public function mount(): void
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->endOfMonth()->toDateString();
        $this->applyFilters();
    }
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export ke Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->exportExcel()), 

            Action::make('export_pdf')
                ->label('Export ke PDF')
                ->icon('heroicon-o-document-text') // Icon yang berbeda
                ->color('danger')
                ->action(fn () => $this->exportPdf()), 
        ];
    }

    public function filterForm(Schema $form): Schema
    {
        // Form ini sudah benar
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
    
    public function applyFilters(): void
    {
        $schoolId = $this->selectedSchool ?? (auth()->user()->hasRole('Admin Yayasan') ? null : auth()->user()->school_id);

        // Fungsi Closure untuk filter Jurnal (sudah diperbaiki)
        $journalFilter = function (Builder $query) use ($schoolId) {
            $query->join('journals', 'journal_entries.journal_id', '=', 'journals.id')
                  ->whereBetween('journals.date', [$this->startDate, $this->endDate]);
            
            if ($schoolId) {
                // Jika filter sekolah DIPILIH
                $query->where('journals.school_id', $schoolId);
            } elseif (auth()->user()->school_id) {
                // Jika user ADALAH Admin Sekolah
                $query->where('journals.school_id', auth()->user()->school_id);
            }
            // Jika Admin Yayasan + "Semua Sekolah", tidak ada filter school_id
        };

        // ===================================================
        // QUERY KOLOM "TANPA PEMBATASAN" (OPERASIONAL)
        // ===================================================

        // 1. Pendapatan TANPA Pembatasan (SPP, PPDB, dll)
        // Asumsi: Akun Pendapatan yang punya 'system_code'
        $this->hasilPendapatanTidakTerikat = Account::query()
            ->where('foundation_id', Filament::getTenant()->id)
            ->where('type', 'Pendapatan')
            ->whereNotNull('system_code') // <-- ASUMSI KITA
            ->withSum([
                'journalEntries as total' => fn (Builder $query) => $query
                    ->tap($journalFilter) // Terapkan filter
                    ->where('journal_entries.type', 'kredit')
            ], 'amount')
            ->get();
        $this->totalPendapatanTidakTerikat = $this->hasilPendapatanTidakTerikat->sum('total');

        // 2. Beban (Semua Beban dianggap "Tanpa Pembatasan")
        $this->hasilBeban = Account::query()
            ->where('foundation_id', Filament::getTenant()->id)
            ->where('type', 'Beban') 
            ->withSum([
                'journalEntries as total' => fn (Builder $query) => $query
                    ->tap($journalFilter) // Terapkan filter
                    ->where('journal_entries.type', 'debit')
            ], 'amount')
            ->get();
        $this->totalBeban = $this->hasilBeban->sum('total');

        // ===================================================
        // QUERY KOLOM "DENGAN PEMBATASAN" (DANA BOS, DLL)
        // ===================================================

        // 3. Pendapatan DENGAN Pembatasan (Jurnal #0 Dana BOS)
        // Asumsi: Akun Pendapatan yang DIBUAT MANUAL (system_code = NULL)
        $this->hasilPendapatanTerikat = Account::query()
            ->where('foundation_id', Filament::getTenant()->id)
            ->where('type', 'Pendapatan')
            ->whereNull('system_code') // <-- ASUMSI KITA
            ->withSum([
                'journalEntries as total' => fn (Builder $query) => $query
                    ->tap($journalFilter) // Terapkan filter
                    ->where('journal_entries.type', 'kredit')
            ], 'amount')
            ->get();
        $this->totalPendapatanTerikat = $this->hasilPendapatanTerikat->sum('total');

        // ===================================================
        // QUERY JURNAL #2 (PELEPASAN DANA)
        // ===================================================
        
        // 4. Sisi KREDIT (Masuk ke "Tanpa Pembatasan" sebagai +)
        $akunAsetNetoTidakTerikat = Account::where('foundation_id', Filament::getTenant()->id)
                                          ->where('system_code', 'aset_neto_tidak_terikat')
                                          ->first();
        if ($akunAsetNetoTidakTerikat) {
            $this->totalPelepasanDana_Kredit = JournalEntry::query()
                ->where('account_id', $akunAsetNetoTidakTerikat->id)
                ->where('type', 'kredit')
                ->whereHas('journal', fn(Builder $q) => $q
                    ->whereBetween('date', [$this->startDate, $this->endDate])
                    // Kita harus tap filter $journalFilter di relasi
                    ->where(function (Builder $query) use ($schoolId) {
                        if ($schoolId) {
                            $query->where('journals.school_id', $schoolId);
                        } elseif (auth()->user()->school_id) {
                            $query->where('journals.school_id', auth()->user()->school_id);
                        }
                    })
                )->sum('amount');
        }

        // 5. Sisi DEBIT (Keluar dari "Dengan Pembatasan" sebagai -)
        // Asumsi: Semua akun Aset Neto yang BUKAN 'aset_neto_tidak_terikat'
        $this->totalPelepasanDana_Debit = JournalEntry::query()
            ->whereHas('account', fn(Builder $q) => $q
                ->where('type', 'Aset Neto')
                ->where('system_code', '!=', 'aset_neto_tidak_terikat')
            )
            ->where('type', 'debit') // Sisi Debit-nya
            ->whereHas('journal', fn(Builder $q) => $q
                ->whereBetween('date', [$this->startDate, $this->endDate])
                // Kita harus tap filter $journalFilter di relasi
                ->where(function (Builder $query) use ($schoolId) {
                    if ($schoolId) {
                        $query->where('journals.school_id', $schoolId);
                    } elseif (auth()->user()->school_id) {
                        $query->where('journals.school_id', auth()->user()->school_id);
                    }
                })
            )->sum('amount');


        // ===================================================
        // KALKULASI TOTAL
        // ===================================================
        $this->totalSurplusDefisit_TidakTerikat = 
            $this->totalPendapatanTidakTerikat + 
            $this->totalPelepasanDana_Kredit - // (+) Dana masuk dari pelepasan
            $this->totalBeban;

        $this->totalSurplusDefisit_Terikat = 
            $this->totalPendapatanTerikat - 
            $this->totalPelepasanDana_Debit; // (-) Dana keluar untuk dilepaskan

        // Total Keseluruhan
        $this->totalPerubahanAsetNeto = 
            $this->totalSurplusDefisit_TidakTerikat + 
            $this->totalSurplusDefisit_Terikat;
            
        // Total untuk kolom "TOTAL"
        $this->totalPendapatan = $this->totalPendapatanTidakTerikat + $this->totalPendapatanTerikat;
        $this->totalBebanGabungan = $this->totalBeban; // Beban hanya di satu kolom
    }

    public function exportExcel()
    {
        // Pastikan $this->applyFilters() sudah dipanggil
        $this->applyFilters(); 
        
        $data = [
            'hasilPendapatanTidakTerikat' => $this->hasilPendapatanTidakTerikat,
            'totalPendapatanTidakTerikat' => $this->totalPendapatanTidakTerikat,
            'hasilBeban' => $this->hasilBeban,
            'totalBeban' => $this->totalBeban,
            'totalPelepasanDana_Kredit' => $this->totalPelepasanDana_Kredit,
            'totalSurplusDefisit_TidakTerikat' => $this->totalSurplusDefisit_TidakTerikat,
            'hasilPendapatanTerikat' => $this->hasilPendapatanTerikat,
            'totalPendapatanTerikat' => $this->totalPendapatanTerikat,
            'totalPelepasanDana_Debit' => $this->totalPelepasanDana_Debit,
            'totalSurplusDefisit_Terikat' => $this->totalSurplusDefisit_Terikat,
            'totalPendapatan' => $this->totalPendapatan,
            'totalBebanGabungan' => $this->totalBeban, // <-- Koreksi Bug
            'totalPerubahanAsetNeto' => $this->totalPerubahanAsetNeto,
            
            // PASTIKAN DATA FILTER INI ADA
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'schoolName' => $this->selectedSchool ? School::find($this->selectedSchool)->name : 'Semua Sekolah (Yayasan)',
        ];

        $namaFile = 'Laporan_Laba_Rugi_' . Carbon::parse($this->startDate)->format('d-m-Y') . '_sd_' . Carbon::parse($this->endDate)->format('d-m-Y') . '.xlsx';

        return Excel::download(new LaporanLabaRugiExport($data), $namaFile);
    }
    public function exportPdf(): StreamedResponse
    {
        // 1. Pastikan data ter-filter (SAMA DENGAN EXCEL)
        $this->applyFilters(); 
        
        // 2. Kumpulkan semua data (SAMA DENGAN EXCEL)
        $data = [
            'hasilPendapatanTidakTerikat' => $this->hasilPendapatanTidakTerikat,
            'totalPendapatanTidakTerikat' => $this->totalPendapatanTidakTerikat,
            'hasilBeban' => $this->hasilBeban,
            'totalBeban' => $this->totalBeban,
            'totalPelepasanDana_Kredit' => $this->totalPelepasanDana_Kredit,
            'totalSurplusDefisit_TidakTerikat' => $this->totalSurplusDefisit_TidakTerikat,
            'hasilPendapatanTerikat' => $this->hasilPendapatanTerikat,
            'totalPendapatanTerikat' => $this->totalPendapatanTerikat,
            'totalPelepasanDana_Debit' => $this->totalPelepasanDana_Debit,
            'totalSurplusDefisit_Terikat' => $this->totalSurplusDefisit_Terikat,
            'totalPendapatan' => $this->totalPendapatan,
            'totalBebanGabungan' => $this->totalBeban,
            'totalPerubahanAsetNeto' => $this->totalPerubahanAsetNeto,
            
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'schoolName' => $this->selectedSchool ? School::find($this->selectedSchool)->name : 'Semua Sekolah (Yayasan)',
        ];

        // 3. Buat nama file PDF
        $namaFile = 'Laporan_Penghasilan_Komprehensif_' . Carbon::parse($this->startDate)->format('d-m-Y') . '_sd_' . Carbon::parse($this->endDate)->format('d-m-Y') . '.pdf';

        // 4. Load view dan data menggunakan DOMPDF
        // PENTING: Asumsinya adalah LaporanLabaRugiExport menggunakan view
        // di 'exports.laporan-laba-rugi'. Sesuaikan jika namanya beda.
        $pdf = Pdf::loadView('exports.laporan-laba-rugi', $data)
                   ->setPaper('a4', 'portrait'); // Atur ukuran kertas

        // 5. Kembalikan sebagai streamed response (download)
        return response()->streamDownload(
            fn() => print($pdf->output()),
            $namaFile
        );
    }
}