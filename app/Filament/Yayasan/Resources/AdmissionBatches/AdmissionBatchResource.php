<?php

namespace App\Filament\Yayasan\Resources\AdmissionBatches;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\AdmissionBatches\Pages\CreateAdmissionBatch;
use App\Filament\Yayasan\Resources\AdmissionBatches\Pages\EditAdmissionBatch;
use App\Filament\Yayasan\Resources\AdmissionBatches\Pages\ListAdmissionBatches;
use App\Filament\Yayasan\Resources\AdmissionBatches\Schemas\AdmissionBatchForm;
use App\Filament\Yayasan\Resources\AdmissionBatches\Tables\AdmissionBatchesTable;
use App\Models\AdmissionBatch;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdmissionBatchResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'ppdb';
    protected static ?string $model = AdmissionBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static string | UnitEnum | null $navigationGroup  = 'PPDB';

    protected static ?string $label = 'Gelombang PPDB';
    protected static ?string $pluralLabel = 'Gelombang PPDB';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';
    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule([
            'Admin Yayasan', 
            'Admin Sekolah',
        ]);
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $userSchoolId = auth()->user()->school_id;

        // Admin Sekolah hanya lihat gelombang sekolahnya
        if ($userSchoolId) {
            $query->where('school_id', $userSchoolId);
        }
        // Admin Yayasan lihat semua
        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return AdmissionBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdmissionBatchesTable::configure($table);
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
            'index' => ListAdmissionBatches::route('/'),
            'create' => CreateAdmissionBatch::route('/create'),
            'edit' => EditAdmissionBatch::route('/{record}/edit'),
        ];
    }
}
