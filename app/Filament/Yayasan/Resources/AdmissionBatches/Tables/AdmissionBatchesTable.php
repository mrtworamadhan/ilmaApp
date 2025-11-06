<?php

namespace App\Filament\Yayasan\Resources\AdmissionBatches\Tables;

use App\Models\AdmissionBatch;
use App\Models\School;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AdmissionBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Gelombang')
                    ->searchable()
                    ->sortable()
                    ->description(fn (AdmissionBatch $record): string => $record->description ?? ''),
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->sortable()
                    ->visible(fn () => auth()->user()->school_id === null), // Hanya Admin Yayasan
                TextColumn::make('start_date')
                    ->label('Tgl. Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Tgl. Selesai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('fee_amount')
                    ->label('Biaya Formulir')
                    ->money('IDR')
                    ->sortable(),
                ToggleColumn::make('is_active') // <-- Kolom toggle cepat
                    ->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => School::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id'))
                    ->visible(fn () => auth()->user()->school_id === null),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
