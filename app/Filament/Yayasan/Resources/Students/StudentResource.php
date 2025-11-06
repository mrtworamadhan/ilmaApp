<?php

namespace App\Filament\Yayasan\Resources\Students;

use App\Filament\Yayasan\Resources\Students\Pages\CreateStudent;
use App\Filament\Yayasan\Resources\Students\Pages\EditStudent;
use App\Filament\Yayasan\Resources\Students\Pages\ListStudents;
use App\Filament\Yayasan\Resources\Students\RelationManagers\StudentRecordsRelationManager;
use App\Filament\Yayasan\Resources\Students\Schemas\StudentForm;
use App\Filament\Yayasan\Resources\Students\Tables\StudentsTable;
use App\Models\Student;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Yayasan\Resources\Students\RelationManagers\OptionalFeesRelationManager;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Daftar Siswa';
    protected static ?string $slug = 'siswa';
    protected static string | UnitEnum | null $navigationGroup  = 'Data Master';
    protected static ?int $navigationSort = 2;

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
            $query->where('school_id', $userSchoolId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OptionalFeesRelationManager::class,
            StudentRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
