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
    public static function canViewAny(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    protected static ?string $model = Budget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static string| UnitEnum |null $navigationGroup = 'Anggaran';
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'E-Budgeting';

    protected static ?string $recordTitleAttribute = 'name';
    // public static function getEloquentQuery(): Builder
    // {
    //     // 1. Ambil query dasar (sudah di-scope ke Tenant/Yayasan)
    //     $query = parent::getEloquentQuery()
    //                 ->where('foundation_id', Filament::getTenant()->id);

    //     // 2. Cek apakah user ini level Sekolah?
    //     $userSchoolId = auth()->user()->school_id;
        
    //     if ($userSchoolId) {
    //         // 3. Jika ya, paksa query HANYA tampilkan siswa
    //         // dari sekolah milik user tsb.
    //         $query->where('school_id', $userSchoolId);
    //     }

    //     // 4. Jika tidak (level Yayasan), kembalikan query langkah 1
    //     return $query;
    // }

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
