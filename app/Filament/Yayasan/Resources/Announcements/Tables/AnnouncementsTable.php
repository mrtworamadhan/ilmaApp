<?php

namespace App\Filament\Yayasan\Resources\Announcements\Tables;

use App\Models\School;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                
                TextColumn::make('school.name')
                    ->label('Target Sekolah')
                    ->placeholder('Semua Sekolah')
                    ->visible(fn () => auth()->user()->school_id === null), // Hanya Admin Yayasan
                
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                    }),
                
                TextColumn::make('published_at')
                    ->label('Waktu Publish')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => School::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id'))
                    ->visible(fn () => auth()->user()->school_id === null),
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
