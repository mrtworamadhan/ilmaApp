<?php

namespace App\Filament\Yayasan\Pages;

use App\Filament\Exports\JournalEntryExporter;
use App\Filament\Exports\JournalExporter;
use App\Filament\Traits\HasModuleAccess;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
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
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class LaporanBukuBesar extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasModuleAccess;

    protected static string $requiredModule = 'finance';
    
    public static function canAccess(): bool // <-- BENAR (Ini untuk Page)
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Laporan Buku Besar';
    protected string $view = 'filament.yayasan.pages.laporan-buku-besar';
    protected static string | UnitEnum | null $navigationGroup  = 'Laporan';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'laporan/buku-besar';

    // Properti ini AKAN DIGUNAKAN oleh 'table()'
    public ?int $selectedAccount = null;
    public ?int $selectedSchool = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    
    // Properti ini HANYA untuk menampung state form
    public ?array $data = []; 

    public function mount(): void
    {
        // Set tanggal default (bulan ini)
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        
        // Cek jika user level sekolah
        if (auth()->user()->school_id !== null) { // <-- Koreksi logika cek
            $this->selectedSchool = auth()->user()->school_id;
        }

        // Isi data form filter
        $this->filterForm->fill([
            'selectedAccount' => $this->selectedAccount,
            'selectedSchool' => $this->selectedSchool,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]);

        // Inisialisasi $data dengan state awal
        $this->data = $this->filterForm->getState();
    }

    public function filterForm(Schema $form): Schema
    {
        return $form
            ->statePath('data') // State form akan disimpan di properti $data
            ->schema([
                Select::make('selectedAccount')
                    ->label('Pilih Akun (Opsional)') // <-- Label diubah
                    ->options(
                        Account::where('foundation_id', Filament::getTenant()->id)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    // ->required() // <-- 1. HAPUS REQUIRED
                    // ->live(),   // <-- 2. HAPUS LIVE (agar tidak error validasi)
                    ->placeholder('Tampilkan Semua Akun (Jurnal Umum)'), // Tambahkan placeholder

                Select::make('selectedSchool')
                    ->label('Filter per Sekolah (Opsional)')
                    ->options(
                        School::where('foundation_id', Filament::getTenant()->id)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->hidden(fn () => auth()->user()->school_id !== null),
                    // ->live(),   // <-- 2. HAPUS LIVE

                DatePicker::make('startDate')
                    ->label('Tanggal Mulai')
                    ->default(now()->startOfMonth())
                    ->required(), // <-- Biarkan required, tapi error hanya saat submit
                    // ->live(),   // <-- 2. HAPUS LIVE

                DatePicker::make('endDate')
                    ->label('Tanggal Selesai')
                    ->default(now()->endOfMonth())
                    ->required()
                    // ->live(),   // <-- 2. HAPUS LIVE
            ]);
    }

    // 3. Method ini dipanggil oleh tombol "Terapkan Filter"
    public function applyFilters(): void
    {
        // Ambil data terbaru dari form
        $data = $this->filterForm->getState();
        
        // Validasi akan dijalankan di sini. Jika lolos:
        $this->selectedAccount = $data['selectedAccount'];
        $this->selectedSchool = $data['selectedSchool'];
        $this->startDate = $data['startDate'];
        $this->endDate = $data['endDate'];

        // PENTING: Panggil re-render tabel secara manual
        $this->table->query(fn () => $this->table($this->table)->getQuery());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = JournalEntry::query()
                    ->with([
                        'journal:id,date,description,foundation_id,school_id', 
                        'account:id,name'
                    ])
                    ->whereHas('journal', function ($q) {
                        $q->where('foundation_id', Filament::getTenant()->id);
                    });
                
                if ($this->selectedAccount) {
                    $query->where('journal_entries.account_id', $this->selectedAccount);
                }

                if ($this->startDate) {
                    $query->whereHas('journal', function ($q) {
                        $q->whereDate('date', '>=', $this->startDate);
                    });
                }
                
                if ($this->endDate) {
                    $query->whereHas('journal', function ($q) {
                        $q->whereDate('date', '<=', $this->endDate);
                    });
                }

                $userSchoolId = auth()->user()->school_id;
                if ($userSchoolId) {
                    $query->whereHas('journal', function ($q) use ($userSchoolId) {
                        $q->where('school_id', $userSchoolId);
                    });
                } elseif ($this->selectedSchool) {
                    $query->whereHas('journal', function ($q) {
                        $q->where('school_id', $this->selectedSchool);
                    });
                }

                return $query->orderBy(
                    Journal::query()->select('date')
                        ->whereColumn('id', 'journal_entries.journal_id')
                        ->limit(1),
                    'asc'
                );
            })
            ->columns([
                TextColumn::make('journal.date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('account.name')
                    ->label('Akun')
                    ->sortable()
                    ->hidden(fn () => $this->selectedAccount !== null), 
                
                TextColumn::make('journal.description')
                    ->label('Keterangan')
                    ->wrap(),
                
                TextColumn::make('debit_amount')
                    ->label('Debit')
                    ->money('IDR')
                    ->alignEnd(),
                
                TextColumn::make('credit_amount')
                    ->label('Kredit')
                    ->money('IDR')
                    ->alignEnd(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export Excel')
                    ->exporter(JournalEntryExporter::class)
                    ->fileName(fn (): string => "laporan_buku_besar_{$this->startDate}_sd_{$this->endDate}")
                    ->options(fn () => [
                        'selectedAccount' => $this->selectedAccount,
                    ])
            ]);
           
         
    }
    /**
     * Menambahkan tombol Aksi di Header Halaman (di atas form filter)
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->action('exportPdf'), // Panggil method exportPdf
        ];
    }

    /**
     * Logika untuk Export PDF
     */
    public function exportPdf(): StreamedResponse
    {
        $query = JournalEntry::query()
            ->with([
                'journal:id,date,description,foundation_id,school_id', 
                'account:id,name'
            ])
            ->whereHas('journal', function ($q) {
                $q->where('foundation_id', Filament::getTenant()->id);
            });
        
        if ($this->selectedAccount) {
            $query->where('journal_entries.account_id', $this->selectedAccount);
        }

        if ($this->startDate) {
            $query->whereHas('journal', function ($q) {
                $q->whereDate('date', '>=', $this->startDate);
            });
        }
        
        if ($this->endDate) {
            $query->whereHas('journal', function ($q) {
                $q->whereDate('date', '<=', $this->endDate);
            });
        }

        $userSchoolId = auth()->user()->school_id;
        if ($userSchoolId) {
            $query->whereHas('journal', function ($q) use ($userSchoolId) {
                $q->where('school_id', $userSchoolId);
            });
        } elseif ($this->selectedSchool) {
            $query->whereHas('journal', function ($q) {
                $q->where('school_id', $this->selectedSchool);
            });
        }

        // Ambil datanya
        $entries = $query->orderBy(
            Journal::query()->select('date')
                ->whereColumn('id', 'journal_entries.journal_id')
                ->limit(1),
            'asc'
        )->get();

        // 2. Siapkan data tambahan untuk judul
        $accountName = $this->selectedAccount
            ? Account::find($this->selectedAccount)?->name
            : 'Semua Akun (Jurnal Umum)';
        
        $schoolName = 'Semua Sekolah (Level Yayasan)';
        if ($this->selectedSchool) {
            $schoolName = School::find($this->selectedSchool)?->name;
        } elseif (auth()->user()->school_id) {
            $schoolName = School::find(auth()->user()->school_id)?->name;
        }

        $fileName = "laporan_buku_besar_{$this->startDate}_sd_{$this->endDate}.pdf";

        // 3. Kumpulkan semua data untuk dikirim ke view
        $data = [
            'entries' => $entries,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'accountName' => $accountName,
            'schoolName' => $schoolName,
            'selectedAccount' => $this->selectedAccount, // Untuk logic hide kolom
        ];

        // 4. Load view dan data menggunakan DOMPDF
        $pdf = Pdf::loadView('exports.laporan-buku-besar', $data)
                   ->setPaper('a4', 'portrait'); // Atur ukuran kertas

        // 5. Kembalikan sebagai streamed response (download)
        return response()->streamDownload(
            fn() => print($pdf->output()),
            $fileName
        );
    }
}