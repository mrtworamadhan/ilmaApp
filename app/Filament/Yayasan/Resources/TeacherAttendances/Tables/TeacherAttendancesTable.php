<?php

namespace App\Filament\Yayasan\Resources\TeacherAttendances\Tables;

use App\Models\School;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherAttendancesTable
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
                TextColumn::make('teacher.full_name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => auth()->user()->school_id === null), // Hanya Admin Yayasan
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'H',
                        'primary' => 'DL',
                        'warning' => 'S',
                        'info' => 'I',
                        'danger' => 'A',
                    ]),
                TextColumn::make('timestamp_in')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('timestamp_out')
                    ->label('Jam Pulang')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Keterangan')
                    ->limit(30),
                TextColumn::make('reporter.name')
                    ->label('Diinput Oleh')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
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
                    ->options(fn () => School::where('foundation_id', auth()->user()->foundation_id)->pluck('name', 'id'))
                    ->visible(fn () => auth()->user()->school_id === null), // Hanya Admin Yayasan
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'H' => 'Hadir',
                        'S' => 'Sakit',
                        'I' => 'Izin',
                        'A' => 'Alpa',
                        'DL' => 'Dinas Luar',
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
