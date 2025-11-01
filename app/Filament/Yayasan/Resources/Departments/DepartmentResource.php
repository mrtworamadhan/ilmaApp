<?php

namespace App\Filament\Yayasan\Resources\Departments;

use App\Filament\Yayasan\Resources\Departments\Pages\ManageDepartments;
use App\Models\Department;
use App\Models\School;
use BackedEnum;
use Filament\Forms\Components\Select;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
    protected static string | UnitEnum | null $navigationGroup  = 'Pengaturan';
    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['Admin Yayasan', 'Admin Sekolah']);
    }
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
        return $schema
            ->components([
                Select::make('school_id')
                    ->label('Sekolah')
                    ->options(School::pluck('name', 'id')) // Ambil dari model School
                    ->searchable()
                    ->required(),
                TextInput::make('name')
                    ->label('Nama Departemen / Bagian')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('school.name') // <-- Tampilkan nama sekolah
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDepartments::route('/'),
        ];
    }
}
