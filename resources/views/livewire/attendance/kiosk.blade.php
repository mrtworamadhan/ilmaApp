{{--
File: resources/views/livewire/attendance/kiosk.blade.php
Kita tidak perlu <x-pos-layout> di sini karena 'otak' (Kiosk.php)
    sudah memanggilnya di method render().
    --}}
    <div>
        <div class="min-h-screen w-full transition-all duration-300" {{-- Kita ganti warna background berdasarkan
            $uiState untuk efek visual --}} :class="{
        'bg-gray-100 dark:bg-gray-900': @js($uiState) === null,
        'bg-blue-50 dark:bg-blue-900': @js($uiState) === 'konfirmasi',
        'bg-green-50 dark:bg-green-900': @js($uiState) === 'berhasil',
        'bg-yellow-50 dark:bg-yellow-900': @js($uiState) === 'sudah_absen',}" x-data="{
            magicSound: new Audio('/sounds/magic-click.mp3')
        }">
            {{--
            ==================================================================
            LAYAR 1: PEMILIHAN FOTO SISWA (uiState == null)
            ==================================================================
            --}}
            @if ($uiState === null)
                <div class="p-4 sm:p-8">
                    {{-- Header --}}
                    <div class="text-center mb-8">
                        <h1 class="text-4xl sm:text-5xl font-bold text-gray-800 dark:text-gray-100">
                            Selamat Datang Siswa Siswi {{ $school->name }}
                        </h1>
                        <p class="text-xl sm:text-2xl text-gray-600 dark:text-gray-300 mt-2">
                            Sentuh Foto Kamu Untuk Absen
                        </p>
                    </div>

                    {{-- Grid Foto Siswa --}}
                    <div
                        class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-5 gap-4 sm:gap-6">
                        @forelse ($students as $student)
                            <div
                                wire:key="student-{{ $student->id }}"
                                
                                {{-- FIX: Tambahkan 'relative' untuk positioning badge --}}
                                class="relative aspect-square flex flex-col items-center justify-center 
                                    rounded-2xl bg-white dark:bg-gray-800 shadow-lg 
                                    border-4 border-transparent 
                                    cursor-pointer transition-all duration-200 
                                    hover:scale-105 hover:shadow-2xl hover:border-blue-500
                                    {{-- FIX: Redupkan & matikan cursor jika sudah absen --}}
                                    {{ $student->todaysAttendance ? 'opacity-50 cursor-not-allowed' : '' }}" 
                                
                                @click="
                                    {{-- FIX: Hanya jalankan jika BELUM absen --}}
                                    if (@js($student->todaysAttendance === null)) {
                                        $wire.selectStudent({{ $student->id }}); 
                                        magicSound.currentTime = 0;
                                        magicSound.play();
                                        $el.classList.add('effect-magic');
                                        setTimeout(() => $el.classList.remove('effect-magic'), 1000);
                                    }
                                "
                            >
                                @if ($student->todaysAttendance)
                                    <div class="absolute top-2 right-2 z-10 w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center bg-white rounded-full shadow-md">
                                        @if ($student->todaysAttendance->status === 'H')
                                            <x-heroicon-s-check-circle class="w-full h-full text-green-500" />
                                        @elseif ($student->todaysAttendance->status === 'S')
                                            <x-heroicon-s-face-frown class="w-9 h-9 sm:w-10 sm:h-10 text-yellow-500" />
                                        @elseif ($student->todaysAttendance->status === 'I')
                                            <x-heroicon-s-envelope class="w-8 h-8 sm:w-9 sm:h-9 text-blue-500" />
                                        @endif
                                    </div>
                                @endif
                                <div
                                    class="w-3/4 h-3/4 rounded-full overflow-hidden border-4 border-gray-200 dark:border-gray-700">
                                    <img src="{{ Storage::url($student->photo_path) }}" alt="{{ $student->nickname }}"
                                        class="w-full h-full object-cover">
                                </div>

                                {{-- Nama Panggilan --}}
                                <h2 class="mt-3 text-xl font-bold text-gray-900 dark:text-gray-100 truncate px-2">
                                    {{ $student->nickname }}
                                </h2>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-20 text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-users class="w-16 h-16 mx-auto mb-4" />
                                <h3 class="text-2xl font-semibold">Belum ada data siswa</h3>
                                <p class="text-lg">Silakan Admin meng-upload foto siswa terlebih dahulu.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            {{--
            ==================================================================
            LAYAR 2: KONFIRMASI (uiState == 'konfirmasi')
            ==================================================================
            --}}
            @if ($uiState === 'konfirmasi' && $selectedStudent)
                <div class="min-h-screen flex flex-col items-center justify-center p-4 text-center">

                    {{-- Foto Siswa yg Dipilih --}}
                    <img src="{{ Storage::url($selectedStudent->photo_path) }}" alt="{{ $selectedStudent->nickname }}"
                        class="w-64 h-64 rounded-full border-8 border-white dark:border-gray-700 shadow-2xl mb-6 object-cover">

                    {{-- Sapaan --}}
                    <h1 class="text-6xl font-bold text-blue-800 dark:text-blue-300 mb-8">
                        Halo, {{ $selectedStudent->nickname }}!
                    </h1>
                    <p class="text-3xl text-gray-700 dark:text-gray-200 mb-12">
                        Kamu hari ini...
                    </p>

                    {{-- Tombol Pilihan --}}
                    <div class="flex flex-col sm:flex-row gap-6">
                        {{-- Tombol Hadir --}}
                        <button wire:click="submitAttendance('hadir')" wire:loading.attr="disabled" class="flex flex-col items-center justify-center w-48 h-48 sm:w-64 sm:h-64 
                                   bg-green-500 text-white rounded-3xl shadow-xl 
                                   hover:bg-green-600 transition-all duration-200 transform hover:scale-105">
                            <x-heroicon-o-check-circle class="w-24 h-24" />
                            <span class="text-4xl font-bold mt-2">HADIR</span>
                        </button>

                        {{-- Tombol Izin --}}
                        <button wire:click="submitAttendance('izin')" wire:loading.attr="disabled" class="flex flex-col items-center justify-center w-48 h-48 sm:w-64 sm:h-64 
                                   bg-blue-500 text-white rounded-3xl shadow-xl 
                                   hover:bg-blue-600 transition-all duration-200 transform hover:scale-105">
                            <x-heroicon-o-envelope class="w-24 h-24" />
                            <span class="text-4xl font-bold mt-2">IZIN</span>
                        </button>

                        {{-- Tombol Sakit --}}
                        <button wire:click="submitAttendance('sakit')" wire:loading.attr="disabled" class="flex flex-col items-center justify-center w-48 h-48 sm:w-64 sm:h-64 
                                   bg-yellow-500 text-white rounded-3xl shadow-xl 
                                   hover:bg-yellow-600 transition-all duration-200 transform hover:scale-105">
                            <x-heroicon-o-face-frown class="w-24 h-24" />
                            <span class="text-4xl font-bold mt-2">SAKIT</span>
                        </button>
                    </div>

                    {{-- Tombol Batal --}}
                    <button wire:click="resetKiosk()"
                        class="mt-16 text-xl text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-semibold">
                        Bukan aku? (Kembali)
                    </button>
                </div>
            @endif

            {{--
            ==================================================================
            LAYAR 3: BERHASIL (uiState == 'berhasil')
            ==================================================================
            --}}
            @if ($uiState === 'berhasil' && $selectedStudent)
                <div class="min-h-screen flex flex-col items-center justify-center p-4 text-center" x-data
                    x-init="setTimeout(() => $wire.resetKiosk(), 3000)">

                    <x-heroicon-s-check-badge class="w-64 h-64 text-green-500 mb-8" />

                    <h1 class="text-7xl font-bold text-gray-800 dark:text-gray-100 mb-4">
                        Hore!
                    </h1>
                    <p class="text-4xl text-gray-700 dark:text-gray-200">
                        Absen <span
                            class="font-bold text-green-600 dark:text-green-400">{{ $selectedStudent->nickname }}</span>
                        berhasil!
                    </p>
                </div>
            @endif

            {{--
            ==================================================================
            LAYAR 4: SUDAH ABSEN (uiState == 'sudah_absen')
            ==================================================================
            --}}
            @if ($uiState === 'sudah_absen' && $selectedStudent)
                <div class="min-h-screen flex flex-col items-center justify-center p-4 text-center" x-data
                    x-init="setTimeout(() => $wire.resetKiosk(), 3000)">

                    <x-heroicon-s-exclamation-circle class="w-64 h-64 text-yellow-500 mb-8" />

                    <h1 class="text-7xl font-bold text-gray-800 dark:text-gray-100 mb-4">
                        Oops!
                    </h1>
                    <p class="text-4xl text-gray-700 dark:text-gray-200">
                        Kamu sudah absen hari ini, <span
                            class="font-bold text-yellow-600 dark:text-yellow-400">{{ $selectedStudent->nickname }}</span>.
                    </p>
                </div>
            @endif

            {{-- Global Loading Spinner (saat berpindah layar) --}}


        </div>
        <div wire:loading wire:target="selectStudent, submitAttendance"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
            <svg class="animate-spin h-24 w-24 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        </div>
    </div>