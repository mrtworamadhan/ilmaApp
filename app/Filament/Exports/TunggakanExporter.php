<?php

namespace App\Filament\Exports;

use App\Models\Bill;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TunggakanExporter extends Exporter
{
    protected static ?string $model = Bill::class;

    // Ini adalah kolom-kolom yang akan muncul di file Excel
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('student.full_name')
                ->label('Nama Siswa'),
            ExportColumn::make('student.schoolClass.name')
                ->label('Kelas'),
            ExportColumn::make('description')
                ->label('Keterangan Tagihan'),
            ExportColumn::make('total_amount')
                ->label('Total Tagihan'),
            ExportColumn::make('status')
                ->label('Status'),
            ExportColumn::make('due_date')
                ->label('Jatuh Tempo'),
        ];
    }

    // Ini adalah filter query (hanya ekspor tunggakan)
    public function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Bill::query()
            ->with(['student.schoolClass']) // Eager load relasi
            ->whereIn('status', ['unpaid', 'overdue']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor laporan tunggakan Anda berhasil disiapkan. ' . number_format($export->successful_rows) . ' ' . str('baris')->plural($export->successful_rows) . ' telah diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}