<?php

namespace App\Filament\Yayasan\Resources\Teachers;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Yayasan\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Yayasan\Resources\Teachers\Pages\ListTeachers;
use App\Filament\Yayasan\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Yayasan\Resources\Teachers\Tables\TeachersTable;
use App\Models\Teacher;
use BackedEnum;
use UnitEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;
    protected static ?string $navigationLabel = 'Daftar Guru';
    protected static ?string $slug = 'guru';
    protected static string | UnitEnum | null $navigationGroup  = 'Data Master';
    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';
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
        return TeacherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeachersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PayrollsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeachers::route('/'),
            'create' => CreateTeacher::route('/create'),
            'edit' => EditTeacher::route('/{record}/edit'),
        ];
    }
}
