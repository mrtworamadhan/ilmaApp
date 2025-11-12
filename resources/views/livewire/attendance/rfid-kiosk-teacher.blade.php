<div 
    class="flex flex-col items-center justify-between min-h-screen p-8 transition-all duration-500"
    {{-- Kita tambahkan 'info' (biru) untuk status pulang --}}
    :class="{
        'bg-gray-100 dark:bg-gray-900': $wire.status === '',
        'bg-green-500 dark:bg-green-700': $wire.status === 'success',
        'bg-red-500 dark:bg-red-700': $wire.status === 'error',
        'bg-blue-500 dark:bg-blue-700': $wire.status === 'info',
    }"
    x-data="{ 
        currentTime: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    }"
    x-init="
        setInterval(() => {
            currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
        }, 1000);
        
        $refs.rfidInput.focus();

        {{-- Listener $watch (untuk auto-reset) --}}
        $watch('$wire.status', (value, oldValue) => {
            if (value !== '') {
                setTimeout(() => {
                    $wire.resetKiosk();
                    $refs.rfidInput.focus();
                }, 3000); // Reset setelah 3 detik
            }
        });
    "
    @blur="$refs.rfidInput.focus()"
>
    {{-- 1. BAGIAN ATAS: JAM & NAMA SEKOLAH --}}
    <div class="w-full text-center">
        <h1 
            class="text-8xl font-bold tracking-tighter"
            :class="$wire.status === '' ? 'text-gray-800 dark:text-gray-100' : 'text-white'"
            x-text="currentTime"
        >
        </h1>
        <p 
            class="text-3xl font-light"
            :class="$wire.status === '' ? 'text-gray-600 dark:text-gray-300' : 'text-white/80'"
        >
            {{ $school->name }} - Absensi Guru
        </p>
    </div>

    {{-- 2. BAGIAN TENGAH: FEEDBACK (FOTO & PESAN) --}}
    <div class="flex flex-col items-center justify-center text-center">
        
        {{-- Tampilan Default (Sebelum Scan) --}}
        <div 
            wire:loading.remove 
            wire:target="updatedRfid" 
            x-show="!$wire.foundTeacher && $wire.status === ''"
        >
            <x-heroicon-o-qr-code class="w-48 h-48 text-gray-400 dark:text-gray-600 animate-pulse"/>            

            <p class="text-2xl font-medium text-gray-500 dark:text-gray-400 mt-4">
                Silakan Scan Kartu RFID Anda
            </p>
        </div>

        {{-- Tampilan Loading (Saat Scan) --}}
        <div wire:loading wire:target="updatedRfid" class="flex flex-col items-center justify-center">
            <svg class="animate-spin h-32 w-32" :class="$wire.status === '' ? 'text-blue-600' : 'text-white'" ...>
                {{-- (kode svg spinner) --}}
            </svg>
            <p class="text-2xl font-medium mt-4" :class="$wire.status === '' ? 'text-blue-600' : 'text-white'">
                Membaca Kartu...
            </p>
        </div>

        {{-- Tampilan Hasil (Sukses/Error) --}}
        <div 
            wire:loading.remove 
            wire:target="updatedRfid" 
            x-show="$wire.foundTeacher || $wire.status !== ''"
            class="text-white w-full"
        >
            {{-- GANTI $foundStudent ke $foundTeacher --}}
            @if ($foundTeacher) 
                <img 
                    src="{{ $foundTeacher->photo_path ? Storage::url($foundTeacher->photo_path) : url('/images/default-user.png') }}" 
                    alt="{{ $foundTeacher->name }}"
                    class="w-64 h-64 rounded-full border-8 border-white/50 shadow-2xl mb-6 mx-auto"
                >
                <h2 class="text-6xl font-bold">{{ $foundTeacher->name }}</h2>
            @endif
            
            <p class="text-4xl font-medium mt-4">{{ $message }}</p>
        </div>
    </div>

    {{-- 3. BAGIAN BAWAH (INPUT RFID RAHASIA) --}}
    <div>
        <input 
            type="text" 
            wire:model.live.debounce.100ms="rfid"
            x-ref="rfidInput"
            class="opacity-0 w-0 h-0 p-0 m-0 border-0"
        >
    </div>
</div>