<div class="filament-tables-summary bg-gray-50 dark:bg-gray-900/50 rounded-b-xl border-t border-gray-200 dark:border-gray-800">
    <div class="flex justify-between items-center px-4 py-3 text-sm font-medium">
        <div class="text-gray-600 dark:text-gray-300">
            TOTAL KESELURUHAN
        </div>
        <div class="flex space-x-8 text-right">
            <div>
                <div class="text-gray-500 text-xs">Dianggarkan</div>
                <div>{{ \Illuminate\Support\Number::currency($totalDianggarkan, 'IDR', 'id') }}</div>
            </div>
            <div>
                <div class="text-gray-500 text-xs">Realisasi</div>
                <div>{{ \Illuminate\Support\Number::currency($totalRealisasi, 'IDR', 'id') }}</div>
            </div>
            <div>
                <div class="text-gray-500 text-xs">Sisa</div>
                <div @class([
                    'text-red-600' => $totalSisa < 0,
                    'text-green-600' => $totalSisa >= 0,
                ])>
                    {{ \Illuminate\Support\Number::currency($totalSisa, 'IDR', 'id') }}
                </div>
            </div>
        </div>
    </div>
</div>
