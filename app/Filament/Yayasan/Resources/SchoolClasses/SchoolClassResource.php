<?php

namespace App\Filament\Yayasan\Resources\SchoolClasses;

use App\Filament\Yayasan\Resources\SchoolClasses\Pages\ManageSchoolClasses;
use App\Filament\Yayasan\Resources\ClassResource\Pages;
use App\Models\Class;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use App\Models\SchoolClass;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use IlluminateAgnostic\Arr;

class SchoolClassResource extends Resource
{
    protected static ?string $model = SchoolClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Daftar Kelas';
    protected static ?string $slug = 'kelas';
    protected static string | UnitEnum | null $navigationGroup  = 'Manajemen Siswa';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        $isYayasanUser = auth()->user()->school_id === null;
        return $schema
            ->components([
                Select::make('school_id')
                    ->label('Sekolah')
                    ->relationship(
                        name: 'school',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => 
                            $query->where('foundation_id', Filament::getTenant()->id)
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive() // <-- Buat field ini reaktif
                    ->hidden(!$isYayasanUser), // Sembunyikan jika bukan Admin Yayasan

                // Jika user = Admin Sekolah, isi otomatis & sembunyikan
                Hidden::make('school_id') // <-- Field tersembunyi
                    ->default(auth()->user()->school_id) // <-- Isi otomatis
                    ->hidden($isYayasanUser), // Sembunyikan jika user = Admin Yayasan

                // --- Sisa Form ---
                TextInput::make('name')
                    ->label('Nama Kelas')
                    ->required()
                    ->helperText('Contoh: Kelas 1A, Kelas 7B, Asrama Putra A'),
                
                TextInput::make('grade_level')
                    ->label('Tingkat')
                    ->required()
                    ->helperText('Contoh: 1, 2, 7, 10, TK A, TK B'),
                
                Select::make('homeroom_teacher_id')
                    ->label('Wali Kelas')
                    ->options(function (\Filament\Schemas\Components\Utilities\Get $get) use ($isYayasanUser) {
                        // Ambil school_id, baik dari dropdown (Admin Yayasan)
                        // atau dari data default (Admin Sekolah)
                        $schoolId = $isYayasanUser ? $get('school_id') : auth()->user()->school_id;

                        if (!$schoolId) {
                            return []; // Jika sekolah belum dipilih, kosongkan
                        }

                        // Ambil user yg rolenya 'guru' (nanti kita ganti 'role'nya)
                        // dan hanya dari sekolah yg dipilih
                        return User::where('foundation_id', Filament::getTenant()->id)
                                   ->where('school_id', $schoolId)
                                //    ->where('role', 'guru') // <-- Nanti kita aktifkan
                                   ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kelas')
                    ->searchable(),
                
                TextColumn::make('grade_level')
                    ->label('Tingkat')
                    ->badge(),
                    
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->badge()
                    ->searchable(),
                
                TextColumn::make('homeroomTeacher.name')
                    ->label('Wali Kelas')
                    ->badge()
                    ->default('Belum diatur'),
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
    public static function getEloquentQuery(): Builder
    {
        // 1. Ambil query dasar (sudah di-scope ke Tenant/Yayasan)
        $query = parent::getEloquentQuery()
                    ->where('foundation_id', Filament::getTenant()->id);

        // 2. Cek apakah user ini level Sekolah?
        $userSchoolId = auth()->user()->school_id;
        
        if ($userSchoolId) {
            // 3. Jika ya, paksa query HANYA tampilkan kelas
            // dari sekolah milik user tsb.
            $query->where('school_id', $userSchoolId);
        }

        // 4. Jika tidak (level Yayasan), kembalikan query langkah 1
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSchoolClasses::route('/'),
        ];
    }
}
