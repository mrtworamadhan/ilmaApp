<?php

namespace App\Filament\Yayasan\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        $isYayasanUser = auth()->user()->school_id === null;
        return $table
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                
                // Tampilkan kolom Sekolah HANYA jika Admin Yayasan
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->badge()
                    ->hidden(!$isYayasanUser), // Sembunyikan jika Admin Sekolah
                
                TextColumn::make('schoolClass.name')
                    ->label('Kelas')
                    ->badge(),
                    
                TextColumn::make('va_number')
                    ->label('Virtual Account (VA)')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'warning',
                        'active' => 'success',
                        'inactive' => 'gray',
                        'graduated' => 'primary',
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
