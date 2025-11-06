<?php

namespace App\Filament\Yayasan\Resources\TeacherAttendances;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\TeacherAttendances\Pages\CreateTeacherAttendance;
use App\Filament\Yayasan\Resources\TeacherAttendances\Pages\EditTeacherAttendance;
use App\Filament\Yayasan\Resources\TeacherAttendances\Pages\ListTeacherAttendances;
use App\Filament\Yayasan\Resources\TeacherAttendances\Schemas\TeacherAttendanceForm;
use App\Filament\Yayasan\Resources\TeacherAttendances\Tables\TeacherAttendancesTable;
use App\Models\TeacherAttendance;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TeacherAttendanceResource extends Resource
{
    use HasModuleAccess; // 4. Gunakan Trait
    protected static string $requiredModule = 'attendance';
    protected static ?string $model = TeacherAttendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;
    protected static string|UnitEnum|null $navigationGroup = 'Kepegawaian';

    protected static ?string $label = 'Log Absensi Guru';
    protected static ?string $pluralLabel = 'Log Absensi Guru';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';
    public static function canViewAny(): bool
    {
        // TODO: Tambahkan role 'Staf Kepegawaian' jika sudah dibuat
        return static::canAccessWithRolesAndModule([
            'Admin Yayasan', 
            'Admin Sekolah',
            'Staf Kesiswaan',
        ]);
    }
    public static function canCreate(): bool
    {
        return false;
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery(); // Scope Yayasan
        
        $userSchoolId = auth()->user()->school_id;

        // Admin Sekolah/Staf hanya lihat data sekolahnya
        if ($userSchoolId) {
            $query->where('school_id', $userSchoolId);
        }
        
        // Admin Yayasan bisa lihat semua

        return $query;
    }
    public static function form(Schema $schema): Schema
    {
        return TeacherAttendanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeacherAttendancesTable::configure($table);
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
            'index' => ListTeacherAttendances::route('/'),
            'create' => CreateTeacherAttendance::route('/create'),
            'edit' => EditTeacherAttendance::route('/{record}/edit'),
        ];
    }
}
