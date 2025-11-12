<div 
    class="flex flex-col items-center justify-between min-h-screen p-8 transition-all duration-500"
    :class="{
        'bg-gray-100 dark:bg-gray-900': $wire.status === '',
        'bg-green-500 dark:bg-green-700': $wire.status === 'success',
        'bg-red-500 dark:bg-red-700': $wire.status === 'error',
    }"
    x-data="{ 
        currentTime: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    }"
    x-init="
        setInterval(() => {
            currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
        }, 1000);
        
        $refs.rfidInput.focus();
        $watch('$wire.status', (value, oldValue) => {
            if (value !== '') {
                setTimeout(() => {
                    $wire.resetKiosk();
                    $refs.rfidInput.focus();
                }, 3000);
            }
        });
    "
    @blur="$refs.rfidInput.focus()"
>
    {{-- 1. BAGIAN ATAS: JAM & NAMA SEKOLAH --}}
    <div class="w-full text-center">
        <h1 
            class="text-xl font-bold tracking-tighter"
            :class="@js($status) === '' ? 'text-gray-800 dark:text-gray-100' : 'text-white'"
            x-text="currentTime"
        >
        </h1>
        <p 
            class="text-3xl font-light"
            :class="@js($status) === '' ? 'text-gray-600 dark:text-gray-300' : 'text-white/80'"
        >
            {{ $school->name }} - Absensi Siswa
        </p>
    </div>

    {{-- 2. BAGIAN TENGAH: FEEDBACK (FOTO & PESAN) --}}
    <div class="flex flex-col items-center justify-center text-center">
        
        {{-- Tampilan Default (Sebelum Scan) --}}
        <div 
            wire:loading.remove 
            wire:target="updatedRfid" 
            x-show="!$wire.foundStudent && $wire.status === ''"
            class="w-full items-center justify-center"
        >
            <x-heroicon-o-qr-code class="w-48 h-48 text-gray-400 dark:text-gray-600 animate-pulse"/>            
            <p class="text-2xl font-medium text-gray-500 dark:text-gray-400 mt-4">
                Silakan Scan Kartu RFID Anda
            </p>
        </div>

        {{-- Tampilan Loading (Saat Scan) --}}
        <div wire:loading wire:target="updatedRfid" class="flex flex-col items-center justify-center">
            <svg class="animate-spin h-32 w-32" :class="@js($status) === '' ? 'text-blue-600' : 'text-white'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-2xl font-medium mt-4" :class="@js($status) === '' ? 'text-blue-600' : 'text-white'">
                Membaca Kartu...
            </p>
        </div>

        <div 
            wire:loading.remove 
            wire:target="updatedRfid" 
            x-show="$wire.foundStudent || $wire.status !== ''"
            class="text-white w-full"
        >
            @if ($foundStudent)
                <img 
                    src="{{ $foundStudent->photo_path ? Storage::url($foundStudent->photo_path) : url('/images/default-user.png') }}" 
                    alt="{{ $foundStudent->nickname }}"
                    class="w-64 h-64 rounded-full border-8 border-white/50 shadow-2xl mb-6 mx-auto"
                >
                <h2 class="text-6xl font-bold">{{ $foundStudent->nickname }}</h2>
            @endif
            
            <p class="text-4xl font-medium mt-4">{{ $message }}</p>
        </div>
    </div>

    {{-- 3. BAGIAN BAWAH (INPUT RFID RAHASIA) --}}
    <div>
        <input 
            type="text" 
            wire:model.live.debounce.100ms="rfid"
            x-ref="rfidInput" {{-- Referensi untuk auto-fokus --}}
            class="opacity-25 w-full h-0 p-0 m-0 border-0"
        >
    </div>
</div>