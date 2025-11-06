<?php

namespace App\Filament\Yayasan\Resources\Teachers\Tables;

use App\Models\School;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->circular(),
                TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => auth()->user()->hasRole(['Admin Yayasan'])), // Hanya Admin Yayasan yg lihat
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('No. Handphone')
                    ->searchable(),
                TextColumn::make('employment_status')
                    ->label('Status')
                    ->searchable(),
            ])
            ->filters([
                // Filter berdasarkan sekolah (jika Admin Yayasan)
                SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => School::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id'))
                    ->visible(fn () => auth()->user()->hasRole(['Admin Yayasan'])),
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
}
