<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'POS Kasir' }}</title> 

    <style>[x-cloak] { display: none !important; }</style>
    @filamentStyles
    @vite('resources/css/app.css')
</head>

<body 
    class="font-sans antialiased bg-gray-100 dark:bg-gray-900" {{-- <-- Sesuaikan BG untuk POS --}}
    
    x-data="{
        // --- Logic untuk Mesin Suara (TTS) ---
        synth: null, utterance: null, voices: [],
        initTTS() {
            if ('speechSynthesis' in window) {
                this.synth = window.speechSynthesis;
                this.utterance = new SpeechSynthesisUtterance();
                this.utterance.lang = 'id-ID';
                this.utterance.rate = 0.9;
                this.utterance.pitch = 1.1;
                this.synth.onvoiceschanged = () => this.loadVoices();
                this.loadVoices();
            } else { console.error('TTS tidak didukung.'); }
        },
        loadVoices() {
            this.voices = this.synth.getVoices().filter(v => v.lang === 'id-ID');
        },
        speak(text) {
            if (!this.synth) return;
            let selectedVoice = this.voices.find(v => v.lang === 'id-ID' && (v.name.toLowerCase().includes('female') || v.name.toLowerCase().includes('google'))) || this.voices[0];
            if (selectedVoice) { this.utterance.voice = selectedVoice; }
            this.utterance.text = text;
            this.synth.cancel();
            this.synth.speak(this.utterance);
        },

        // --- Logic untuk Notifikasi (Hutang POS) ---
        toasts: [],
        removeToast(id) {
            let toast = this.toasts.find(t => t.id === id);
            if (toast) {
                toast.visible = false;
                setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 300); 
            }
        }
    }"

    x-init="initTTS()"

    @speak-text.window="speak($event.detail.text)"
    @show-toast.window="
        let id = Date.now();
        toasts.push({ 
            id: id, 
            visible: true, 
            title: $event.detail.title, 
            body: $event.detail.body || '', 
            status: $event.detail.status || 'info' 
        });
        setTimeout(() => removeToast(id), 3000);
    "
>
    <div class="p-4 sm:p-6 lg:p-8">
        {{ $slot }}
    </div>


    <div class="fixed top-5 right-5 z-50 w-full max-w-xs space-y-3">
        <template x-for="toast in toasts" :key="toast.id">
            <div 
                x-show="toast.visible" 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-x-full"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 transform translate-x-0"
                x-transition:leave-end="opacity-0 transform translate-x-full"
                class="relative w-full rounded-lg shadow-lg"
                :class="{
                    'bg-green-500 text-white': toast.status === 'success',
                    'bg-red-500 text-white': toast.status === 'danger',
                    'bg-blue-500 text-white': toast.status === 'info',
                }"
            >
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-semibold" x-text="toast.title"></p>
                            <p class="mt-1 text-sm" x-text="toast.body"></p>
                        </div>
                        <div class="ml-4 flex flex-shrink-0">
                            <button @click="removeToast(toast.id)" class="inline-flex text-white/70 hover:text-white">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
    
    @livewire('notifications') 
    @filamentScripts
    @vite('resources/js/app.js')
</body>
</html>