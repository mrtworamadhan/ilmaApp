<?php

namespace App\Filament\Yayasan\Resources\StudentRecords;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\StudentRecords\Pages\CreateStudentRecord;
use App\Filament\Yayasan\Resources\StudentRecords\Pages\EditStudentRecord;
use App\Filament\Yayasan\Resources\StudentRecords\Pages\ListStudentRecords;
use App\Filament\Yayasan\Resources\StudentRecords\Schemas\StudentRecordForm;
use App\Filament\Yayasan\Resources\StudentRecords\Tables\StudentRecordsTable;
use App\Models\StudentRecord;
use App\Models\School; // Untuk filter
use App\Models\Student;
use BackedEnum;
use UnitEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StudentRecordResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'student_records';

    protected static ?string $model = StudentRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;
    protected static string | UnitEnum | null $navigationGroup  = 'Kesiswaan';
    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $label = 'Catatan Siswa';
    protected static ?string $pluralLabel = 'Catatan Siswa';
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
        $query = parent::getEloquentQuery(); 
        $userSchoolId = auth()->user()->school_id;

        if ($userSchoolId) {
            $query->where('school_id', $userSchoolId);
        }
        
        return $query;
    }
    public static function canCreate(): bool
    {
        if (!static::checkModuleAccess()) {
            return false;
        }
        
        return auth()->user()->hasAnyRole(['Admin Yayasan', 'Staf Kesiswaan', 'Wali Kelas']); 
    }
    public static function form(Schema $schema): Schema
    {
        return StudentRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentRecordsTable::configure($table);
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
            'index' => ListStudentRecords::route('/'),
            'create' => CreateStudentRecord::route('/create'),
            'edit' => EditStudentRecord::route('/{record}/edit'),
        ];
    }
}
