import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [

    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './vendor/filament/**/*.blade.php',

    './resources/views/livewire/**/*.blade.php',       // <-- Untuk kiosk.blade.php
    './resources/views/components/layouts/*.blade.php', // <-- Untuk app.blade.php
    './app/Livewire/Attendance/Kiosk.php',
    './app/Livewire/Attendance/RfidKioskStudent.php',
    './app/Livewire/Attendance/RfidKioskTeacher.php',
    './app/Livewire/Kantin/PosUi.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Figtree', ...defaultTheme.fontFamily.sans],
      },
    },
  },
  plugins: [],
};
