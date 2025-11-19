<?php

namespace App\Filament\Yayasan\Resources\Journals;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\Journals\Pages\CreateJournal;
use App\Filament\Yayasan\Resources\Journals\Pages\EditJournal;
use App\Filament\Yayasan\Resources\Journals\Pages\ListJournals;
use App\Filament\Yayasan\Resources\Journals\Schemas\JournalForm;
use App\Filament\Yayasan\Resources\Journals\Tables\JournalsTable;
use App\Models\Journal;
use BackedEnum;
use UnitEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'finance';
    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }

    protected static ?string $model = Journal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Jurnal Umum (Manual)';
    protected static ?string $slug = 'jurnal-umum';
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Keuangan';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
                    ->where('foundation_id', Filament::getTenant()->id);

        $userSchoolId = auth()->user()->school_id;
        
        if ($userSchoolId) {
            // Tampilkan jurnal milik sekolah ATAU jurnal level yayasan
            $query->where(function ($q) use ($userSchoolId) {
                $q->where('school_id', $userSchoolId)
                  ->orWhereNull('school_id'); // Bendahara sekolah boleh lihat jurnal yayasan
            });
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return JournalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalsTable::configure($table);
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
            'index' => ListJournals::route('/'),
            'create' => CreateJournal::route('/create'),
            'edit' => EditJournal::route('/{record}/edit'),
        ];
    }
}
