<?php

namespace App\Filament\Yayasan\Resources\Students\RelationManagers;

use App\Filament\Yayasan\Resources\Students\StudentResource;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;

class OptionalFeesRelationManager extends RelationManager
{
    protected static string $relationship = 'optionalFees';

    protected static ?string $relatedResource = StudentResource::class;

    protected static ?string $title = 'Biaya Opsional (Ekskul, Katering, dll)';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nama Biaya Opsional'),
                TextColumn::make('amount')->money('IDR'),
                TextColumn::make('billing_cycle')->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Ini adalah tombol "Toggle ON"
                AttachAction::make()
                    ->label('Tambahkan Biaya Opsional (Toggle ON)')
                    ->preloadRecordSelect()
                    // Filter biaya opsional agar sesuai
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        // 1. Ambil ID sekolah siswa ini
                        $studentSchoolId = $this->getOwnerRecord()->school_id;
                        
                        // 2. Tampilkan HANYA aturan biaya yang:
                        return $query
                            // - Milik sekolah siswa
                            ->where('school_id', $studentSchoolId)
                            // - Dan BUKAN 'one_time' (karena ini langganan)
                            ->where('billing_cycle', '!=', 'one_time');
                    }),
            ])
            ->actions([
                // Ini adalah tombol "Toggle OFF"
                DetachAction::make()->label('Hapus (Toggle OFF)'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
