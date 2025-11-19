<?php

namespace App\Filament\Exports;

use App\Models\JournalEntry;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Filament\Facades\Filament;

class JournalExporter extends Exporter
{
    protected static ?string $model = JournalEntry::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('journal.date')
                ->label('Tanggal'),
            ExportColumn::make('journal.description')
                ->label('Deskripsi Jurnal'),
            ExportColumn::make('account.name')
                ->label('Akun'),
            ExportColumn::make('debit_amount')
                ->label('Debit'),
            ExportColumn::make('credit_amount')
                ->label('Kredit'),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        // EXPLICIT loading dengan kolom yang dibutuhkan
        return $query->with([
            'journal:id,date,description,foundation_id,school_id',
            'account:id,name'
        ])
        ->whereHas('journal', function ($q) {
            $q->where('foundation_id', Filament::getTenant()->id);
        });
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor buku besar berhasil. ' . Number::format($export->successful_rows) . ' ' . str('baris')->plural($export->successful_rows) . ' diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}