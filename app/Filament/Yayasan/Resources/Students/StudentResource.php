<?php

namespace App\Filament\Yayasan\Resources\Students;

use App\Filament\Yayasan\Resources\Students\Pages\CreateStudent;
use App\Filament\Yayasan\Resources\Students\Pages\EditStudent;
use App\Filament\Yayasan\Resources\Students\Pages\ListStudents;
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
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Siswa';
    protected static ?int $navigationSort = 2;
    
    public static function getEloquentQuery(): Builder
    {
        // 1. Ambil query dasar (sudah di-scope ke Tenant/Yayasan)
        $query = parent::getEloquentQuery()
                    ->where('foundation_id', Filament::getTenant()->id);

        // 2. Cek apakah user ini level Sekolah?
        $userSchoolId = auth()->user()->school_id;
        
        if ($userSchoolId) {
            // 3. Jika ya, paksa query HANYA tampilkan siswa
            // dari sekolah milik user tsb.
            $query->where('school_id', $userSchoolId);
        }

        // 4. Jika tidak (level Yayasan), kembalikan query langkah 1
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
