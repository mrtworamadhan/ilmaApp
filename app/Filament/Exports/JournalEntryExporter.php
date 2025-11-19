<?php

namespace App\Filament\Exports;

use App\Models\JournalEntry; // <-- Pastikan model-nya benar
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;

class JournalEntryExporter extends Exporter
{
    protected static ?string $model = JournalEntry::class;
    // public static function getOptionsFormComponents(): array
    // {
    //     return [
    //         DatePicker::make('startDate')
    //             ->label('Tanggal Mulai')
    //             ->default(now()->startOfMonth())
    //             ->required(),
    //         DatePicker::make('endDate')
    //             ->label('Tanggal Selesai')
    //             ->default(now()->endOfMonth())
    //             ->required(),
    //     ];
    // }
    public static function getColumns(): array
    {
        $selectedAccount = null;

        $columns = [];

        // Kolom Tanggal
        $columns[] = ExportColumn::make('journal.date')
            ->label('Tanggal')
            ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('d-m-Y'));

        // Kolom Akun (Hanya tampil jika 'Semua Akun' dipilih)
        if (!$selectedAccount) {
            $columns[] = ExportColumn::make('account.name')
                ->label('Akun');
        }

        // Kolom Keterangan, Debit, Kredit
        $columns[] = ExportColumn::make('journal.description')
            ->label('Keterangan');
        $columns[] = ExportColumn::make('debit_amount')
            ->label('Debit')
            ->formatStateUsing(fn ($state) => $state ?? 0); // Format angka jika perlu
        $columns[] = ExportColumn::make('credit_amount')
            ->label('Kredit')
            ->formatStateUsing(fn ($state) => $state ?? 0); // Format angka jika perlu

        return $columns;
    }

    /**
     * Method ini meneruskan properti publik dari Page ke Exporter,
     * sehingga kita bisa tahu $selectedAccount di getColumns().
     */
    public function getOptions(): array
    {
        $options = [];
        // Cek jika kita ada di dalam Livewire component (Page)
        if (property_exists($this, 'livewire')) {
            $options['selectedAccount'] = $this->livewire->selectedAccount;
        }
        return $options;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Laporan buku besar (' . $export->successful_rows . ' baris) telah selesai diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . $failedRowsCount . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}