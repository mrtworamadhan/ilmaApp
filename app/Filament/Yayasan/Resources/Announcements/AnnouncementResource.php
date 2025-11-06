<?php

namespace App\Filament\Yayasan\Resources\Announcements;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Yayasan\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Yayasan\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Yayasan\Resources\Announcements\Schemas\AnnouncementForm;
use App\Filament\Yayasan\Resources\Announcements\Tables\AnnouncementsTable;
use App\Models\Announcement;
use App\Models\School;
use Filament\Facades\Filament;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AnnouncementResource extends Resource
{
    use HasModuleAccess;
    protected static string $requiredModule = 'announcement';
    protected static ?string $model = Announcement::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;
    protected static string | UnitEnum | null $navigationGroup  = 'Pengaturan';
    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $userSchoolId = auth()->user()->school_id;

        if ($userSchoolId) {
            $query->where(function ($q) use ($userSchoolId) {
                $q->where('school_id', $userSchoolId)
                  ->orWhereNull('school_id');
            });
        }
        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
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
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
