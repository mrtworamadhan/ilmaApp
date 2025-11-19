<?php

namespace App\Filament\Yayasan\Resources\Users;

use App\Filament\Yayasan\Resources\Users\Pages\CreateUser;
use App\Filament\Yayasan\Resources\Users\Pages\EditUser;
use App\Filament\Yayasan\Resources\Users\Pages\ListUsers;
use App\Filament\Yayasan\Resources\Users\Schemas\UserForm;
use App\Filament\Yayasan\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use UnitEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string | UnitEnum | null $navigationGroup  = 'Pengaturan';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Manajemen User';
    protected static ?string $slug = 'users';
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['Admin Yayasan', 'Admin Sekolah']);
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
                    ->where('foundation_id', Filament::getTenant()->id);

        $userSchoolId = auth()->user()->school_id;
        
        if ($userSchoolId) {
            $query->where('school_id', $userSchoolId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
    public static function getUpdateNotificationTitle(): ?string
    {
        return 'Password updated successfully!';
    }
}
