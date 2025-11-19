<?php

namespace App\Filament\Yayasan\Resources\Vendors;

use App\Filament\Traits\HasModuleAccess;
use App\Filament\Yayasan\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Yayasan\Resources\Vendors\Pages\EditVendor;
use App\Filament\Yayasan\Resources\Vendors\Pages\ListVendors;
use App\Filament\Yayasan\Resources\Vendors\Schemas\VendorForm;
use App\Filament\Yayasan\Resources\Vendors\Tables\VendorsTable;
use App\Models\Pos\Vendor;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VendorResource extends Resource
{
    use HasModuleAccess; // 4. Gunakan Trait
    protected static string $requiredModule = 'cashless';
    public static function canAccess(): bool
    {
        return static::canAccessWithRolesAndModule(['Admin Yayasan', 'Admin Sekolah']);
    }
    protected static ?string $model = Vendor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;
        
    protected static string | UnitEnum | null $navigationGroup  = 'Vendor Kantin';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Vendor (Kantin)';

    protected static ?string $pluralModelLabel = 'Data Vendor (Kantin)';
    protected static ?string $slug = 'vendor-kantin';

    public static function getEloquentQuery(): Builder
    {
        // Admin Yayasan hanya bisa lihat vendor milik yayasannya
        return parent::getEloquentQuery()
            ->where('foundation_id', Auth::user()->foundation_id);
    }

    public static function boot(): void
    {
        parent::boot();

        // Otomatis suntik foundation_id saat BUAT vendor
        static::creating(function ($vendor) {
            if (Auth::check()) {
                $vendor->foundation_id = Auth::user()->foundation_id;
            }
        });
    }

    public static function form(Schema $schema): Schema
    {
        return VendorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorsTable::configure($table);
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
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
}
