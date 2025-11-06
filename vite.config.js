import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', // <-- Hanya ini
                'resources/js/app.js',  // <-- Dan ini
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});