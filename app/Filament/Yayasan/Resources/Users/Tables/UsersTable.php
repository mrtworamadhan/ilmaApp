<?php

namespace App\Filament\Yayasan\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $isYayasanUser = auth()->user()->school_id === null;
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama User')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('email')
                    ->label('Email Login')
                    ->searchable(),
                
                TextColumn::make('role')
                    ->label('Role')
                    ->badge(),
                
                // Tampilkan kolom Sekolah HANYA jika Admin Yayasan
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->badge()
                    ->hidden(!$isYayasanUser),
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
