<?php

namespace App\Filament\Yayasan\Resources\StudentAttendances\Tables;

use App\Models\School;
use App\Models\SchoolClass;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentAttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('student.full_name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('schoolClass.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'H',
                        'warning' => 'S',
                        'info' => 'I',
                        'danger' => 'A',
                    ]),
                TextColumn::make('notes')
                    ->label('Keterangan')
                    ->limit(30),
                TextColumn::make('reporter.name')
                    ->label('Diinput Oleh')
                    ->searchable()
                    ->sortable(),
            ])
           ->filters([
                // Filter Range Tanggal
                Filter::make('date')
                    ->form([
                        DatePicker::make('date_from')->label('Dari Tanggal'),
                        DatePicker::make('date_to')->label('Sampai Tanggal')->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn (Builder $query, $date) => $query->whereDate('date', '>=', $date))
                            ->when($data['date_to'], fn (Builder $query, $date) => $query->whereDate('date', '<=', $date));
                    }),
                
                SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => School::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id'))
                    ->visible(fn () => auth()->user()->school_id === null), // Hanya Admin Yayasan
                
                SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->options(function () {
                        $userSchoolId = auth()->user()->school_id;
                        if ($userSchoolId) {
                            return SchoolClass::where('school_id', $userSchoolId)->pluck('name', 'id');
                        }
                        // Admin Yayasan bisa filter semua kelas
                        return SchoolClass::where('foundation_id', Filament::getTenant()->id)->pluck('name', 'id');
                    })
                    ->searchable(),
                
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'H' => 'Hadir',
                        'S' => 'Sakit',
                        'I' => 'Izin',
                        'A' => 'Alpa',
                    ]),
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
