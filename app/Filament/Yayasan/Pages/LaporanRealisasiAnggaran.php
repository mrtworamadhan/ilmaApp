<?php

namespace App\Filament\Yayasan\Pages;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Department;
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
use UnitEnum;

class LaporanRealisasiAnggaran extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.yayasan.pages.laporan-realisasi-anggaran';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|UnitEnum|null $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Realisasi Anggaran';
    protected static ?string $navigationLabel = 'Laporan Realisasi';
    protected static ?int $navigationSort = 1;
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'academic_year' => Budget::where('status', 'APPROVED')->latest('academic_year')->value('academic_year'),
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['Admin Yayasan', 'Admin Sekolah']);
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
}
