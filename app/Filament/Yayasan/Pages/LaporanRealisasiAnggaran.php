<?php

namespace App\Filament\Yayasan\Pages;

use App\Exports\LaporanRealisasiExport;
use App\Filament\Traits\HasModuleAccess;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Department;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use Maatwebsite\Excel\Facades\Excel; 
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class LaporanRealisasiAnggaran extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canAccess(): bool // <-- BENAR (Ini untuk Page)
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    protected string $view = 'filament.yayasan.pages.laporan-realisasi-anggaran';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Realisasi Anggaran';
    protected static ?string $navigationLabel = 'Laporan Realisasi';
    protected static ?int $navigationSort = 1;
    public ?array $data = [];

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
        $this->form->fill([
            'academic_year' => Budget::where('status', 'APPROVED')->latest('academic_year')->value('academic_year'),
        ]);
    }
    
    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Select::make('academic_year')
                    ->label('Tahun Ajaran')
                    ->options(
                        Budget::where('status', 'APPROVED')
                            ->distinct()
                            ->pluck('academic_year', 'academic_year')
                    )
                    ->live()
                    ->required(),

                Select::make('department_id')
                    ->label('Departemen / Bagian')
                    ->options($this->getDepartmentOptions())
                    ->live()
                    ->nullable(),
            ])
            ->columns(2)
            ->statePath('data');
    }
    protected function getDepartmentOptions(): array
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        $query = Department::where('foundation_id', $tenant->id);

        if ($user->school_id !== null) {
            $query->where('school_id', $user->school_id);
        }

        return $query->pluck('name', 'id')->toArray();
    }
    public function getReportData()
    {
        $academicYear = $this->data['academic_year'] ?? null;
        $departmentId = $this->data['department_id'] ?? null;

        if (!$academicYear) {
            return collect();
        }

        return BudgetItem::query()
            ->whereHas('budget', function (Builder $query) use ($academicYear, $departmentId) {

                $query->where('status', 'APPROVED');

                $query->where('academic_year', $academicYear);

                if ($departmentId) {
                    $query->where('department_id', $departmentId);
                }
            })
            ->with('account')


            ->withSum('expenses', 'amount')

            ->get();
    }

    public function table(Table $table): Table
    {
        return $table
            // 4. Query data (logika dari getReportData() pindah ke sini)
            ->query(function (): Builder {
                $data = $this->form->getState(); // Ambil data dari form filter
                $academicYear = $data['academic_year'] ?? null;
                $departmentId = $data['department_id'] ?? null;

                $query = BudgetItem::query()
                    ->whereHas('budget', function (Builder $q) use ($academicYear, $departmentId) {
                        $q->where('status', 'APPROVED');
                        if ($academicYear) {
                            $q->where('academic_year', $academicYear);
                        }
                        if ($departmentId) {
                            $q->where('department_id', $departmentId);
                        }
                    })
                    ->with('account')
                    ->withSum('expenses', 'amount'); // <-- Ini akan menghasilkan 'expenses_sum_amount'
    
                // Jika tahun ajaran belum dipilih, jangan tampilkan apa-apa
                if (!$academicYear) {
                    return $query->whereRaw('1 = 0'); // Trik agar query kosong
                }

                return $query;
            })
            // 5. Definisikan Kolom
            ->columns([
                TextColumn::make('account.name')
                    ->label('Pos Anggaran (COA)')
                    ->description(fn(BudgetItem $record) => $record->account->code)
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->wrap(),

                TextColumn::make('planned_amount')
                    ->label('Dianggarkan')
                    ->numeric(locale: 'id')
                    ->money('IDR')
                    ->alignment('right'),

                TextColumn::make('expenses_sum_amount')
                    ->label('Realisasi')
                    ->numeric(locale: 'id')
                    ->money('IDR')
                    ->alignment('right')
                    ->default(0),
                
                TextColumn::make('persentase')
                    ->label('Realisasi (%)')
                    ->alignment('right')
                    ->state(function (BudgetItem $record): string {
                        $dianggarkan = $record->planned_amount;
                        $realisasi = $record->expenses_sum_amount ?? 0;

                        if ($dianggarkan == 0 || $dianggarkan == null) {
                            return 'N/A'; 
                        }

                        $persentase = ($realisasi / $dianggarkan) * 100;
                        
                        return number_format($persentase, 2) . ' %';
                    })
                    ->color(function (BudgetItem $record): string {
                        $dianggarkan = $record->planned_amount;
                        $realisasi = $record->expenses_sum_amount ?? 0;

                        if ($dianggarkan == 0 || $dianggarkan == null) {
                            return 'gray';
                        }
                        
                        $persentase = ($realisasi / $dianggarkan) * 100;

                        if ($persentase > 100) {
                            return 'danger'; 
                        } elseif ($persentase > 85) {
                            return 'warning';
                        } else {
                            return 'success';
                        }
                    }),

                TextColumn::make('sisa')
                    ->label('Sisa (Rp)')
                    ->numeric(locale: 'id')
                    ->money('IDR')
                    ->alignment('right')
                    ->state(function (BudgetItem $record): float {
                        return $record->planned_amount - ($record->expenses_sum_amount ?? 0);
                    })
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success'),
                ]);
            // ->content(function (Table $table) {
            //         $query = $table->getQuery();

            //         $records = $query->get();

            //         if ($records->isEmpty()) {
            //             return null;
            //         }

            //         $totalDianggarkan = $records->sum('planned_amount');
            //         $totalRealisasi = $records->sum('expenses_sum_amount');
            //         $totalSisa = $records->sum(fn ($item) => $item->planned_amount - ($item->expenses_sum_amount ?? 0));

            //         return view('filament.yayasan.pages.partials.laporan-realisasi-summary', [
            //             'totalDianggarkan' => $totalDianggarkan,
            //             'totalRealisasi' => $totalRealisasi,
            //             'totalSisa' => $totalSisa,
            //         ]);
            // });

    }
    private function getExportData(): array
    {
        $data = $this->form->getState(); // Ambil data dari form filter
        $academicYear = $data['academic_year'] ?? null;
        $departmentId = $data['department_id'] ?? null;

        // Jika tahun ajaran belum dipilih, jangan tampilkan apa-apa
        if (!$academicYear) {
            return [
                'items' => collect(),
                'academicYear' => $academicYear,
                'departmentName' => 'N/A',
            ];
        }
        
        // --- Ini adalah query yang sama persis dari method table() ---
        $query = BudgetItem::query()
            ->whereHas('budget', function (Builder $q) use ($academicYear, $departmentId) {
                $q->where('status', 'APPROVED');
                if ($academicYear) {
                    $q->where('academic_year', $academicYear);
                }
                if ($departmentId) {
                    $q->where('department_id', $departmentId);
                }
            })
            ->with('account')
            ->withSum('expenses', 'amount'); // <-- Ini akan menghasilkan 'expenses_sum_amount'
        
        $items = $query->get();
        // --- Selesai duplikat query ---

        // Ambil data tambahan untuk judul
        $departmentName = 'Semua Departemen';
        if ($departmentId) {
            $departmentName = Department::find($departmentId)?->name ?? 'Semua Departemen';
        }

        return [
            'items' => $items,
            'academicYear' => $academicYear,
            'departmentName' => $departmentName,
        ];
    }

    /**
     * Logika untuk Export Excel (Maatwebsite)
     */
    public function exportExcel()
    {
        // 1. Ambil data pakai helper
        $data = $this->getExportData();
        
        // 2. Siapkan nama file
        $fileName = "laporan_realisasi_anggaran.xlsx";

        // 3. Panggil Maatwebsite Excel untuk download
        return Excel::download(new LaporanRealisasiExport(
            $data['items'],
            $data['academicYear'],
            $data['departmentName']
        ), $fileName);
    }

    /**
     * Logika untuk Export PDF
     */
    public function exportPdf(): StreamedResponse
    {
        // 1. Ambil data pakai helper
        $data = $this->getExportData();
        
        // 2. Siapkan nama file
        $fileName = "laporan_realisasi_anggaran.pdf";

        // 3. Load view dan data menggunakan DOMPDF
        // Laporan ini lebar, jadi kita pakai 'landscape'
        $pdf = Pdf::loadView('exports.laporan-realisasi-anggaran', $data)
                   ->setPaper('a4', 'landscape'); 

        // 4. Kembalikan sebagai streamed response (download)
        return response()->streamDownload(
            fn() => print($pdf->output()),
            $fileName
        );
    }
}
