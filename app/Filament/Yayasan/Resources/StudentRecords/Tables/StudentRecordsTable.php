<?php

namespace App\Filament\Yayasan\Resources\StudentRecords\Tables;

use App\Models\School;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('student.full_name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.schoolClass.name') // Tampilkan kelas
                    ->label('Kelas')
                    ->badge(),
                BadgeColumn::make('type') // Gunakan BadgeColumn
                    ->label('Tipe')
                    ->colors([
                        'danger' => 'pelanggaran',
                        'success' => 'prestasi',
                        'warning' => 'perizinan',
                        'info' => 'catatan_bk',
                    ]),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->html(),
                TextColumn::make('points')
                    ->label('Poin')
                    ->sortable(),
                TextColumn::make('reporter.name') // Tampilkan siapa yg lapor
                    ->label('Dilaporkan Oleh')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => School::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id'))
                    ->visible(fn () => auth()->user()->school_id === null), // Hanya Admin Yayasan
                
                SelectFilter::make('student_id')
                    ->label('Siswa')
                    ->relationship('student', 'full_name', fn (Builder $query) => 
                        // Filter siswa berdasarkan sekolah user
                        $query->where('school_id', auth()->user()->school_id)
                    )
                    ->searchable()
                    ->preload()
                    ->visible(fn () => auth()->user()->school_id !== null), // Hanya Admin Sekolah/Staf
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
