<?php

namespace App\Filament\Yayasan\Resources\SavingTransactions;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\SavingTransactions\Pages\CreateSavingTransaction;
use App\Filament\Yayasan\Resources\SavingTransactions\Pages\EditSavingTransaction;
use App\Filament\Yayasan\Resources\SavingTransactions\Pages\ListSavingTransactions;
use App\Filament\Yayasan\Resources\SavingTransactions\Schemas\SavingTransactionForm;
use App\Filament\Yayasan\Resources\SavingTransactions\Tables\SavingTransactionsTable;
use App\Models\SavingTransaction;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SavingTransactionResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'savings';
    public static function canViewAny(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }
    protected static ?string $model = SavingTransaction::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;
    protected static string| UnitEnum |null $navigationGroup = 'Tabungan Siswa';
    protected static ?string $navigationLabel = 'Riwayat Transaksi';
    protected static ?string $slug = 'tabungan/riwayat-transaksi';
    protected static ?int $navigationSort = 2;
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $userSchoolId = auth()->user()->school_id;

        if ($userSchoolId) {
            $query->whereHas('savingAccount', function ($q) use ($userSchoolId) {
                $q->where('school_id', $userSchoolId);
            });
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return SavingTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SavingTransactionsTable::configure($table);
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
            'index' => ListSavingTransactions::route('/'),
        ];
    }
}
