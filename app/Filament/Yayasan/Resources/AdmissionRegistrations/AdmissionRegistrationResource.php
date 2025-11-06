<?php

namespace App\Filament\Yayasan\Resources\AdmissionRegistrations;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\AdmissionRegistrations\Pages\CreateAdmissionRegistration;
use App\Filament\Yayasan\Resources\AdmissionRegistrations\Pages\EditAdmissionRegistration;
use App\Filament\Yayasan\Resources\AdmissionRegistrations\Pages\ListAdmissionRegistrations;
use App\Filament\Yayasan\Resources\AdmissionRegistrations\Schemas\AdmissionRegistrationForm;
use App\Filament\Yayasan\Resources\AdmissionRegistrations\Tables\AdmissionRegistrationsTable;
use App\Models\AdmissionRegistration;
use App\Models\Student;
use App\Models\School;
use Filament\Facades\Filament;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdmissionRegistrationResource extends Resource
{
    use HasModuleAccess; // 2. Gunakan Trait
    protected static string $requiredModule = 'ppdb';
    protected static ?string $model = AdmissionRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string | UnitEnum | null $navigationGroup  = 'PPDB';
    protected static ?string $label = 'Pendaftar PPDB';
    protected static ?string $pluralLabel = 'Pendaftar PPDB';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';
    public static function canViewAny(): bool
    {
        return static::canAccessWithRolesAndModule([
            'Admin Yayasan', 
            'Admin Sekolah', 
            'Staf Keuangan / TU', 
            'Staf Kesiswaan'     
        ]);
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $userSchoolId = auth()->user()->school_id;

        // Admin Sekolah hanya lihat pendaftar sekolahnya
        if ($userSchoolId) {
            $query->where('school_id', $userSchoolId);
        }
        // Admin Yayasan lihat semua
        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return AdmissionRegistrationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdmissionRegistrationsTable::configure($table);
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
            'index' => ListAdmissionRegistrations::route('/'),
            'create' => CreateAdmissionRegistration::route('/create'),
            'edit' => EditAdmissionRegistration::route('/{record}/edit'),
        ];
    }
}
