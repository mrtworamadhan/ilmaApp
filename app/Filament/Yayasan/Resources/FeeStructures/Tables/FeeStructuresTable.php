<?php

namespace App\Filament\Yayasan\Resources\FeeStructures\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeeStructuresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Aturan')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->badge(),
                
                TextColumn::make('feeCategory.name')
                    ->label('Kategori')
                    ->badge(),
                
                TextColumn::make('grade_level')
                    ->label('Tingkat')
                    ->badge()
                    ->default('Semua Tingkat'),
                    
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                
                TextColumn::make('billing_cycle')
                    ->label('Siklus')
                    ->badge(),
                
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
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
