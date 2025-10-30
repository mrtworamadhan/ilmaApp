<?php

namespace App\Filament\Yayasan\Resources\Schools\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SchoolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Sekolah')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('level')
                    ->label('Jenjang')
                     ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tk' => 'TK / PAUD',
                        'sd' => 'SD / MI',
                        'smp' => 'SMP / MTS',
                        'sma' => 'SMA / MA',
                        'pondok' => 'Pondok Pesantren',
                        default => ucfirst($state),
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'tk' => 'success',
                        'sd' => 'primary',
                        'smp' => 'warning',
                        'sma' => 'danger',
                        'pondok' => 'info',
                    }),
                
                TextColumn::make('headmaster')
                    ->label('Kepala Sekolah'),
                
                TextColumn::make('phone')
                    ->label('Telepon'),
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
