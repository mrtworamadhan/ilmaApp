<x-filament-panels::page>

    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    Generate Gaji Bulan Ini
                </span>
                <span wire:loading>
                    Memproses...
                </span>
            </x-filament::button>
        </div>
    </form>

</x-filament-panels::page>