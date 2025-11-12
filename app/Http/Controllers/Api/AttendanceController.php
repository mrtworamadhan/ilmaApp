<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\School; // Penting untuk type-hint
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Menerima dan memproses scan RFID dari hardware.
     * Diasumsikan middleware 'school.api' sudah berjalan
     * dan menempelkan $request->school.
     */
    public function storeScan(Request $request)
    {
        // 1. Validasi Input dari Scanner
        $validator = Validator::make($request->all(), [
            'rfid_tag_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors()
            ], 422); // Unprocessable Entity
        }

        $rfid = $request->input('rfid_tag_id');
        
        // 2. Ambil data Sekolah (dari Middleware 'school.api' kita)
        $school = $request->school;
        
        // 3. Cari pemilik RFID (Siswa atau Guru)
        $user = null;
        $userType = null;

        $student = Student::where('rfid_tag_id', $rfid)
                        ->where('school_id', $school->id)
                        ->first();
        
        if ($student) {
            $user = $student;
            $userType = 'student';
        } else {
            $teacher = Teacher::where('rfid_tag_id', $rfid)
                            ->where('school_id', $school->id)
                            ->first();
            if ($teacher) {
                $user = $teacher;
                $userType = 'teacher';
            }
        }

        // 4. Jika RFID tidak terdaftar di sekolah ini
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kartu RFID tidak terdaftar di sekolah ini.'
            ], 404); // Not Found
        }

        // 5. Proses Absensi berdasarkan Tipe User
        if ($userType === 'student') {
            return $this->processStudentAttendance($user, $school);
        } else {
            return $this->processTeacherAttendance($user, $school);
        }
    }

    /**
     * Logika Absensi untuk SISWA (Global Check-in)
     */
    private function processStudentAttendance(Student $student, School $school)
    {
        $today = Carbon::today();

        // Cek apakah sudah absen hari ini
        $existing = StudentAttendance::where('student_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sudah absen hari ini.',
                'user_name' => $student->nickname,
                'type' => 'student'
            ], 409); // Conflict (sudah ada)
        }

        // Buat absensi baru (Global Check-in)
        try {
            StudentAttendance::create([
                'student_id' => $student->id,
                'school_id' => $school->id,
                'foundation_id' => $school->foundation_id,
                'class_id' => $student->school_class_id,
                'date' => $today,
                'status' => 'h', // 'h' = hadir
                'timestamp_in' => now(),
                'created_by' => null, // Via API
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Absen Masuk Berhasil!',
                'user_name' => $student->nickname,
                'type' => 'student'
            ], 201); // Created

        } catch (\Exception $e) {
            // Tangkap error jika misal class_id null
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500); // Internal Server Error
        }
    }

    /**
     * Logika Absensi untuk GURU (Check-in / Check-out)
     */
    private function processTeacherAttendance(Teacher $teacher, School $school)
    {
        $today = Carbon::today();
        
        // Cek absensi hari ini
        $existing = TeacherAttendance::where('teacher_id', $teacher->id)
            ->whereDate('date', $today)
            ->first();

        try {
            // KASUS 1: BELUM ADA ABSENSI (Ini adalah Check-In)
            if (!$existing) {
                $checkInTime = Carbon::parse($school->teacher_check_in_time);
                $status = now()->lessThanOrEqualTo($checkInTime) ? 'tepat_waktu' : 'terlambat';

                TeacherAttendance::create([
                    'teacher_id' => $teacher->id,
                    'school_id' => $school->id,
                    'foundation_id' => $school->foundation_id,
                    'date' => $today,
                    'status' => $status,
                    'timestamp_in' => now(),
                    'created_by' => null,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Absen Masuk Berhasil!',
                    'user_name' => $teacher->name,
                    'type' => 'teacher',
                    'check_in_status' => $status
                ], 201);
            }
            
            // KASUS 2: SUDAH CHECK-IN, TAPI BELUM CHECK-OUT
            elseif ($existing && !$existing->timestamp_out) {
                $existing->update(['timestamp_out' => now()]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Absen Pulang Berhasil!',
                    'user_name' => $teacher->name,
                    'type' => 'teacher'
                ], 200); // OK
            } 

            // KASUS 3: SUDAH CHECK-IN DAN SUDAH CHECK-OUT
            else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sudah absen pulang hari ini.',
                    'user_name' => $teacher->name,
                    'type' => 'teacher'
                ], 409); // Conflict
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }
}