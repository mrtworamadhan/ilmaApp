<?php

namespace App\Livewire\Attendance;

use App\Models\School;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class RfidKioskTeacher extends Component
{
    // --- PROPERTI STATE ---
    public School $school;
    public $rfid = '';
    public ?Teacher $foundTeacher = null;
    public $message = '';
    public $status = ''; // 'success', 'error', 'info' (untuk pulang)

    /**
     * Dijalankan saat komponen di-load
     */
    public function mount(School $school)
    {
        $this->school = $school;
    }

    /**
     * Livewire Hook: Dijalankan OTOMATIS setiap kali $rfid berubah
     */
    public function updatedRfid($value)
    {
        $this->rfid = trim($value);
        if (empty($this->rfid)) {
            return;
        }

        // 1. Cari Guru
        $teacher = Teacher::where('rfid_tag_id', $this->rfid)
                        ->where('school_id', $this->school->id)
                        ->first();

        // 2. JIKA GURU TIDAK DITEMUKAN
        if (!$teacher) {
            $this->status = 'error';
            $this->message = 'Kartu RFID Tidak Dikenali.';
            $this->dispatch('speak-text', text: 'Kartu tidak dikenal.');
            $this->dispatchReset();
            return;
        }

        // 3. JIKA GURU DITEMUKAN
        $this->foundTeacher = $teacher;
        $today = Carbon::today();
        
        // Cek absensi hari ini (menggunakan relasi 'pintar' yg baru kita buat)
        $existing = $teacher->todaysAttendance;

        try {
            // ==========================================================
            // LOGIKA CHECK-IN vs CHECK-OUT
            // ==========================================================

            // KASUS 1: BELUM ADA ABSENSI (Ini adalah Check-In)
            if (!$existing) {
                // Ambil jam masuk standar dari sekolah
                $checkInTime = Carbon::parse($this->school->teacher_check_in_time);
                
                // Tentukan status (Tepat Waktu atau Terlambat)
                $attendanceStatus = now()->lessThanOrEqualTo($checkInTime) ? 'tepat_waktu' : 'terlambat';

                TeacherAttendance::create([
                    'teacher_id' => $teacher->id,
                    'school_id' => $this->school->id,
                    'foundation_id' => $this->school->foundation_id,
                    'date' => $today,
                    'status' => $attendanceStatus,
                    'timestamp_in' => now(), // Catat jam masuk
                    'created_by' => null,
                ]);

                $this->status = 'success';
                $this->message = 'Absen Masuk Berhasil!';
                $this->dispatch('speak-text', text: 'Selamat Datang, ' . $teacher->name);

            // KASUS 2: SUDAH CHECK-IN, TAPI BELUM CHECK-OUT
            } elseif ($existing && !$existing->timestamp_out) {
                
                $existing->update([
                    'timestamp_out' => now(), // Catat jam pulang
                ]);

                $this->status = 'info'; // Kita pakai warna 'info' (biru) untuk pulang
                $this->message = 'Absen Pulang Berhasil!';
                $this->dispatch('speak-text', text: 'Selamat Pulang, ' . $teacher->name . '. Hati-hati di jalan.');

            // KASUS 3: SUDAH CHECK-IN DAN SUDAH CHECK-OUT
            } else {
                $this->status = 'error';
                $this->message = 'Anda sudah absen pulang hari ini.';
                $this->dispatch('speak-text', text: 'Oops, ' . $teacher->name . ' sudah absen pulang hari ini.');
            }

            $this->dispatchReset(); // Reset setelah 3 detik

        } catch (\Exception $e) {
            $this->status = 'error';
            $this->message = 'Error: Gagal menyimpan data.';
            $this->dispatch('speak-text', text: 'Error, gagal menyimpan data.');
            $this->dispatchReset();
            Log::error('Gagal Absen Kiosk RFID Guru: ' . $e->getMessage());
        }
    }

    /**
     * Kirim event (yang didengar Alpine) untuk mereset
     */
    public function dispatchReset(): void
    {
        // Event ini akan ditangkap oleh Alpine.js di .blade.php
        $this->dispatch('auto-reset-kiosk'); 
    }

    /**
     * Mengosongkan state (dipanggil oleh event)
     */
    public function resetKiosk(): void
    {
        $this->rfid = '';
        $this->foundTeacher = null;
        $this->message = '';
        $this->status = '';
    }

    /**
     * Render tampilan
     */
    public function render()
    {
        return view('livewire.attendance.rfid-kiosk-teacher')
            ->layout('components.layouts.app', [ // Pakai layout utama kita
                'title' => 'Kiosk Absensi RFID Guru - ' . $this->school->name
            ]);
    }
}