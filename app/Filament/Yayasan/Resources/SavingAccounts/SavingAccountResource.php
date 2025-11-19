<?php

namespace App\Filament\Yayasan\Resources\SavingAccounts;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\SavingAccounts\Pages\CreateSavingAccount;
use App\Filament\Yayasan\Resources\SavingAccounts\Pages\EditSavingAccount;
use App\Filament\Yayasan\Resources\SavingAccounts\Pages\ListSavingAccounts;
use App\Filament\Yayasan\Resources\SavingAccounts\Schemas\SavingAccountForm;
use App\Filament\Yayasan\Resources\SavingAccounts\Tables\SavingAccountsTable;
use App\Models\SavingAccount;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SavingAccountResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'savings';
    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }
    protected static ?string $model = SavingAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string| UnitEnum |null $navigationGroup = 'Tabungan Siswa';
    protected static ?string $navigationLabel = 'Buku Tabungan';
    protected static ?string $slug = 'tabungan/buku-tabungan';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery(); 
        $userSchoolId = auth()->user()->school_id;

        if ($userSchoolId) {
            $query->where('school_id', $userSchoolId);
        }
        
        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return SavingAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SavingAccountsTable::configure($table);
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
            'index' => ListSavingAccounts::route('/'),
            'create' => CreateSavingAccount::route('/create'),
            'edit' => EditSavingAccount::route('/{record}/edit'),
        ];
    }
}
