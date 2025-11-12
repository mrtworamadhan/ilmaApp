<?php

namespace App\Livewire\Attendance;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Livewire\Component;

class RfidKioskStudent extends Component
{
    // --- PROPERTI STATE ---
    public School $school; // Akan diisi dari Route

    /**
     * @var string Input dari scanner RFID
     */
    public $rfid = '';

    /**
     * @var Student|null Siswa yang ditemukan
     */
    public ?Student $foundStudent = null;

    /**
     * @var string Pesan status (cth: "Absen Berhasil!")
     */
    public $message = '';

    /**
     * @var string Status UI ('success' atau 'error')
     */
    public $status = '';

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
        // 1. Bersihkan input
        $this->rfid = trim($value);
        if (empty($this->rfid)) {
            return;
        }

        // 2. Cari Siswa
        $student = Student::where('rfid_tag_id', $this->rfid)
                        ->where('school_id', $this->school->id) // Pastikan dari sekolah yg benar
                        ->first();

        // 3. JIKA SISWA TIDAK DITEMUKAN
        if (!$student) {
            $this->status = 'error';
            $this->message = 'Kartu RFID Tidak Dikenali.';
            $this->dispatch('speak-text', text: 'Kartu tidak dikenal.');
            // $this->dispatch('auto-reset-kiosk'); // Kirim event untuk reset
            return;
        }

        // 4. JIKA SISWA DITEMUKAN
        $this->foundStudent = $student;
        $today = Carbon::today();

        // Cek apakah sudah absen hari ini
        $existing = StudentAttendance::where('student_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        // 5. JIKA SUDAH ABSEN
        if ($existing) {
            $this->status = 'error';
            $this->message = $student->nickname . ' sudah absen hari ini.';
            $this->dispatch('speak-text', text: 'Oops, ' . $student->nickname . ' sudah absen hari ini.');
            $this->dispatch('auto-reset-kiosk');
            return;
        }

        // 6. JIKA BERHASIL (PROSES ABSEN)
        try {
            StudentAttendance::create([
                'student_id' => $student->id,
                'school_id' => $this->school->id,
                'foundation_id' => $this->school->foundation_id,
                'class_id' => $student->class_id, // Kita ambil dari data siswa
                'date' => $today,
                'status' => 'h', // Otomatis 'Hadir'
                'timestamp_in' => now(), // Catat jam masuk
                'created_by' => null, // Via Kiosk
            ]);

            $this->status = 'success';
            $this->message = 'Absen Berhasil!';
            $this->dispatch('speak-text', text: 'Terima kasih, ' . $student->nickname . '. Absen berhasil.');
            $this->dispatch('auto-reset-kiosk');

        } catch (\Exception $e) {
            // Jika gagal (misal: class_id null, dll)
            $this->status = 'error';
            $this->message = 'Error: Gagal menyimpan data.';
            $this->dispatch('speak-text', text: 'Error, gagal menyimpan data.');
            $this->dispatch('auto-reset-kiosk');
            
            // Catat error di log server
            \Illuminate\Support\Facades\Log::error('Gagal Absen Kiosk RFID: ' . $e->getMessage());
        }
    }

    /**
     * Mengosongkan state (dipanggil oleh event)
     */
    public function resetKiosk(): void
    {
        $this->rfid = '';
        $this->foundStudent = null;
        $this->message = '';
        $this->status = '';
    }

    /**
     * Render tampilan
     */
    public function render()
    {
        // Kita pakai layout 'app.blade.php' yang sudah ada TTS-nya
        return view('livewire.attendance.rfid-kiosk-student')
            ->layout('components.layouts.app', [
                'title' => 'Kiosk Absensi RFID Siswa - ' . $this->school->name
            ]);
    }
}