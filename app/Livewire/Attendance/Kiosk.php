<?php

namespace App\Livewire\Attendance;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Livewire\Component;

class Kiosk extends Component
{
    // --- PROPERTI STATE ---
    
    public School $school; // Akan diisi dari Route
    public $students = []; // Daftar siswa di sekolah ini
    public ?Student $selectedStudent = null; // Siswa yang fotonya diklik
    
    /**
     * @var string|null Status untuk UI ('hadir', 'sakit', 'izin', 'sudah_absen', 'berhasil')
     */
    public ?string $uiState = null; 

    /**
     * Dijalankan saat komponen di-load
     */
    public function mount(School $school)
    {
        $this->school = $school;
        $this->loadStudents();
    }

    /**
     * Mengambil daftar siswa dari database
     */
    public function loadStudents(): void
    {
        $this->students = Student::where('school_id', $this->school->id)
            ->where('status', 'active') 
            ->whereNotNull('photo_path') 
            ->with('todaysAttendance')
            ->orderBy('nickname') 
            ->get();
    }

    /**
     * Dipanggil saat foto siswa diklik
     */
    public function selectStudent(int $studentId): void
    {
        // Jika sudah ada yang submit, jangan biarkan klik lagi
        if ($this->uiState) {
            return;
        }
        
        $this->selectedStudent = Student::find($studentId);
        $this->uiState = 'konfirmasi'; 

       $textToSpeak = "Selamat Pagi, " . $this->selectedStudent->nickname . ", silahkan pilih kehadiran.";
        $this->dispatch('speak-text', text: $textToSpeak);
    }

    /**
     * Dipanggil saat tombol (Hadir/Sakit/Izin) ditekan
     */
    public function submitAttendance(string $status): void
    {
        $statusMap = [
            'hadir' => 'h',
            'sakit' => 's',
            'izin'  => 'i',
        ];

        if (!$this->selectedStudent || !in_array($status, ['hadir', 'sakit', 'izin'])) {
            return; // Keamanan, jika tidak ada siswa/status tidak valid
        }

        $dbStatus = $statusMap[$status];
        if (!$this->selectedStudent) {
            return; 
        }
        $textToSpeak = 'Terimakasih!'; // Default untuk 'izin'
        if ($dbStatus === 'h') {
            $textToSpeak = 'Terimakasih, semangat ya belajarnya!';
        } elseif ($dbStatus === 's') {
            $textToSpeak = 'Semoga Cepat Sembuh ya.';
        }

        $today = Carbon::today();

        // CEK KRUSIAL: Apakah siswa ini sudah absen hari ini?
        $existing = StudentAttendance::where('student_id', $this->selectedStudent->id)
            ->whereDate('date', $today)
            ->first();
        
        if ($existing) {
            $this->uiState = 'sudah_absen'; // Beri status "sudah absen"
            $this->dispatch('speak-text', text: 'Oops, kamu sudah absen hari ini.'); // <-- BENAR (v3)
            return;
        }

        // Jika belum, BUAT REKOR BARU
        StudentAttendance::create([
            'student_id' => $this->selectedStudent->id,
            'school_id' => $this->school->id,
            'foundation_id' => $this->school->foundation_id,
            'class_id' => $this->selectedStudent->class_id, 
            'date' => $today,
            'status' => $dbStatus, 
            'timestamp_in' => ($dbStatus === 'h') ? now() : null, // Cek pakai 'h'
            'created_by' => null,
        ]);

        // Beri status "berhasil" untuk UI
        $this->uiState = 'berhasil';
        $this->dispatch('speak-text', text: $textToSpeak);
    }

    /**
     * Dipanggil oleh tombol "Kembali" atau "Selesai" di UI
     */
    public function resetKiosk(): void
    {
        $this->selectedStudent = null;
        $this->uiState = null; // Kembali ke layar pemilihan foto
    }

    /**
     * Render tampilan
     */
    public function render()
    {
        // KITA BERITAHU LIVEWIRE UNTUK MEMAKAI LAYOUT ANDA
        return view('livewire.attendance.kiosk')
            ->layout('components.layouts.app', [
                'title' => 'Absensi Kiosk - ' . $this->school->name
            ]);
    }
}