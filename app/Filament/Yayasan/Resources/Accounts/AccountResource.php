<?php

namespace App\Filament\Yayasan\Resources\Accounts;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\Accounts\Pages\CreateAccount;
use App\Filament\Yayasan\Resources\Accounts\Pages\EditAccount;
use App\Filament\Yayasan\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Yayasan\Resources\Accounts\Schemas\AccountForm;
use App\Filament\Yayasan\Resources\Accounts\Tables\AccountsTable;
use App\Models\Account;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class AccountResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canViewAny(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }
    
    
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AtSymbol;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Daftar Akun (COA)';
    protected static ?string $slug = 'akun-keuangan';
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Keuangan';
    protected static ?int $navigationSort = 1; // Urutan pertama di grup

    public static function getEloquentQuery(): Builder
    {
        // Otomatis filter data berdasarkan Yayasan yang login
        return parent::getEloquentQuery()
            ->where('foundation_id', Filament::getTenant()->id);
    }

    public static function form(Schema $schema): Schema
    {
        return AccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountsTable::configure($table);
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
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }
}
