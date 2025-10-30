<?php

namespace App\Filament\Yayasan\Resources\Bills\Tables;

use App\Models\SchoolClass;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BillsTable
{
    public static function configure(Table $table): Table
    {
        $isYayasanUser = auth()->user()->school_id === null;
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable(),
                
                // Tampilkan kolom Sekolah HANYA jika Admin Yayasan
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->badge()
                    ->hidden(!$isYayasanUser), // Sembunyikan jika Admin Sekolah
                
                TextColumn::make('feeCategory.name')
                    ->label('Kategori Tagihan')
                    ->badge(),
                
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                    }),
            ])
            ->filters([
                // Filter berdasarkan Sekolah (Hanya untuk Admin Yayasan)
                SelectFilter::make('school_id')
                    ->label('Filter Sekolah')
                    ->relationship('school', 'name')
                    ->hidden(!$isYayasanUser), 
                
                // Filter berdasarkan Kelas
                SelectFilter::make('student.class_id')
                    ->label('Filter Kelas')
                    ->options(function () use ($isYayasanUser) {
                        $query = SchoolClass::query()
                            ->where('foundation_id', auth()->user()->foundation_id);
                        
                        if (!$isYayasanUser) {
                            $query->where('school_id', auth()->user()->school_id);
                        }
                        
                        return $query->pluck('name', 'id');
                    }),
                
                SelectFilter::make('status')
                    ->label('Status Tagihan')
                    ->options([
                        'unpaid' => 'Belum Lunas',
                        'paid' => 'Lunas',
                        'overdue' => 'Jatuh Tempo',
                        'cancelled' => 'Dibatalkan',
                    ]),
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
}
