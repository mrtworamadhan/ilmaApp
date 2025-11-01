<?php

namespace App\Filament\Yayasan\Resources\Expenses;

use App\Filament\Yayasan\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Yayasan\Resources\Expenses\Pages\EditExpense;
use App\Filament\Yayasan\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Yayasan\Resources\Expenses\Schemas\ExpenseForm;
use App\Filament\Yayasan\Resources\Expenses\Tables\ExpensesTable;
use App\Models\Expense;
use BackedEnum;
use UnitEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Catat Pengeluaran';
    protected static ?string $slug = 'pengeluaran';
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Keuangan';
    protected static ?int $navigationSort = 3;
    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['Admin Yayasan', 'Admin Sekolah']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
                    ->where('foundation_id', Filament::getTenant()->id);

        $userSchoolId = auth()->user()->school_id;
        
        if ($userSchoolId) {
            $query->where(function ($q) use ($userSchoolId) {
                $q->where('school_id', $userSchoolId)
                  ->orWhereNull('school_id'); 
            });
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return ExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpensesTable::configure($table);
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
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }
}
