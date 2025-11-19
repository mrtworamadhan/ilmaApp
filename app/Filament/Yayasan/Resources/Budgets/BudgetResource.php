<?php

namespace App\Filament\Yayasan\Resources\Budgets;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\Budgets\Pages\CreateBudget;
use App\Filament\Yayasan\Resources\Budgets\Pages\EditBudget;
use App\Filament\Yayasan\Resources\Budgets\Pages\ListBudgets;
use App\Filament\Yayasan\Resources\Budgets\Schemas\BudgetForm;
use App\Filament\Yayasan\Resources\Budgets\Tables\BudgetsTable;
use App\Models\Budget;
use BackedEnum;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BudgetResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah', 'Kepala Bagian']);
    }

    protected static ?string $model = Budget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static string| UnitEnum |null $navigationGroup = 'Anggaran';
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'E-Budgeting';

    protected static ?string $recordTitleAttribute = 'name';
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $foundationId = Filament::getTenant()->id;

        // 1. Ambil query dasar (selalu di-scope ke Yayasan/Tenant)
        $query = parent::getEloquentQuery()
                    ->where('foundation_id', $foundationId);

        // 2. CEK LEVEL 1 (Paling Spesifik): KEPALA BAGIAN
        // (Logic ini SUDAH BENAR karena 'budgets' punya 'department_id')
        if ($user->hasRole('Kepala Bagian') && $user->department_id) {
            
            return $query->where('department_id', $user->department_id);
        }

        // 3. CEK LEVEL 2: ADMIN SEKOLAH
        // (Ini hanya berjalan jika user BUKAN Kepala Bagian)
        if ($user->hasRole(['Admin Sekolah']) && $user->school_id) {
            
            // V-- INI DIA PERBAIKANNYA (LOGIC "LOMPAT" TABEL) --V

            // Tampilkan Anggaran...
            return $query->whereHas('department', function (Builder $q) use ($user) {
                // ...yang departemen-nya punya school_id yang sama dengan user
                $q->where('school_id', $user->school_id);
            });

            // ^-- BATAS AKHIR PERBAIKAN --^
        }
        
        // 4. LEVEL 3: ADMIN YAYASAN
        // (Jika bukan keduanya, kembalikan semua data di yayasannya)
        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return BudgetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgets::route('/'),
            'create' => CreateBudget::route('/create'),
            'edit' => EditBudget::route('/{record}/edit'),
        ];
    }
}
