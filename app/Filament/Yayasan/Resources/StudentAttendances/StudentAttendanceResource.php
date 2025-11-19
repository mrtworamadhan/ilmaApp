<?php

namespace App\Filament\Yayasan\Resources\StudentAttendances;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\StudentAttendances\Pages\CreateStudentAttendance;
use App\Filament\Yayasan\Resources\StudentAttendances\Pages\EditStudentAttendance;
use App\Filament\Yayasan\Resources\StudentAttendances\Pages\ListStudentAttendances;
use App\Filament\Yayasan\Resources\StudentAttendances\Schemas\StudentAttendanceForm;
use App\Filament\Yayasan\Resources\StudentAttendances\Tables\StudentAttendancesTable;
use App\Models\StudentAttendance;
use App\Models\School;
use App\Models\SchoolClass;
use Filament\Facades\Filament;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StudentAttendanceResource extends Resource
{
    use HasModuleAccess; // 2. Gunakan Trait
    protected static string $requiredModule = 'attendance';
    protected static ?string $model = StudentAttendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;
    
    protected static string|UnitEnum|null $navigationGroup = 'Kesiswaan';

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $label = 'Log Absensi';
    protected static ?string $pluralLabel = 'Log Absensi';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule([
            'Admin Yayasan', 
            'Admin Sekolah',
            'Staf Kesiswaan',
            'Wali Kelas',
        ]);
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery(); // Scope Yayasan
        
        $userSchoolId = auth()->user()->school_id;

        // Admin Sekolah/Staf Kesiswaan/Wali Kelas hanya lihat data sekolahnya
        if ($userSchoolId) {
            $query->where('school_id', $userSchoolId);
        }
        
        // Admin Yayasan bisa lihat semua

        return $query;
    }
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return StudentAttendanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentAttendancesTable::configure($table);
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
            'index' => ListStudentAttendances::route('/'),
            // 'create' => CreateStudentAttendance::route('/create'),
            'edit' => EditStudentAttendance::route('/{record}/edit'),
        ];
    }
}
