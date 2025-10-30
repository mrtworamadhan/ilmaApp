<?php

namespace App\Filament\Yayasan\Resources\Journals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JournalsTable
{
    public static function configure(Table $table): Table
    {
        $isYayasanUser = auth()->user()->school_id === null;

        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tgl Transaksi')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->wrap(), // Agar teks panjang bisa turun
                
                TextColumn::make('school.name')
                    ->label('Unit Sekolah')
                    ->badge()
                    ->hidden(!$isYayasanUser), // Sembunyikan jika Admin Sekolah
                
                TextColumn::make('creator.name') // Siapa yg input
                    ->label('Diinput Oleh')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Filter Sekolah')
                    ->relationship('school', 'name')
                    ->hidden(!$isYayasanUser), 
            ])
            ->recordActions([
                EditAction::make()->label(''),
                DeleteAction::make()->label(''),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
