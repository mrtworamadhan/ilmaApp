
    <div>
        @if ($registrationSuccess)
            <div class="p-4 text-center text-lg text-green-800 bg-green-100 rounded-lg shadow-md" role="alert">
                <h3 class="font-bold text-xl mb-2">✅ Pendaftaran Berhasil!</h3>
                <p>Data Anda telah berhasil terkirim.</p>
                <p class="mt-2">Silakan tunggu informasi selanjutnya dari pihak sekolah yang akan menghubungi Anda.</p>
                <p class="mt-4 text-sm">Terima kasih.</p>
            </div>
        @else
            <form wire:submit="create" class="bg-white p-6 rounded-lg shadow-md">
                {{ $this->form }}
            </form>
        @endif
        
        <x-filament-actions::modals /> 
    </div>
