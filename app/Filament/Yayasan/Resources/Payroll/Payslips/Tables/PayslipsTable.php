<?php

namespace App\Filament\Yayasan\Resources\Payroll\Payslips\Tables;

use App\Models\Payroll\Payslip;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayslipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacher.full_name')
                    ->label('Nama Guru')
                    ->searchable(),
                // Kolom virtual "Periode"
                TextColumn::make('period')
                    ->label('Periode')
                    ->getStateUsing(function ($record) {
                        return date('F', mktime(0, 0, 0, $record->month, 1)) . ' ' . $record->year;
                    })
                    ->searchable(['month', 'year']),
                TextColumn::make('total_allowance')
                    ->label('Total Pendapatan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total_deduction')
                    ->label('Total Potongan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('net_pay')
                    ->label('Gaji Bersih (THP)')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'generated' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('month')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ]),
                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(array_combine(range(now()->year, now()->year - 5), range(now()->year, now()->year - 5)))
                    ->default(now()->year),
            ])
            ->recordActions([
                ViewAction::make()->label(''),
                Action::make('downloadPdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info') // Ganti warna agar beda dari 'View'
                    ->url(fn (Payslip $record): string => route('payslip.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
