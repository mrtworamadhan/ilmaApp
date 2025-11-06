<?php

namespace App\Filament\Yayasan\Resources\Students\RelationManagers;

use App\Filament\Yayasan\Resources\Students\StudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'studentRecords';

    protected static ?string $relatedResource = StudentResource::class;
    protected static ?string $title = 'Catatan Siswa';

    public function table(Table $table): Table
    {
        return $table
        ->recordTitleAttribute('name')
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
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
