<?php

namespace App\Livewire\Public;

use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use App\Models\School;
use App\Models\AdmissionBatch;
use App\Models\AdmissionRegistration;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View;

class PpdbForm extends Component implements HasForms
{
    use InteractsWithForms;
    public ?array $data = []; 

    public ?School $selectedSchool = null;

    public ?AdmissionBatch $selectedBatch = null;

    public bool $registrationSuccess = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Wizard::make([
                    Step::make('1. Pilih Sekolah & Gelombang')
                        ->schema([
                            Select::make('school_id')
                                ->label('Pilih Sekolah Tujuan')
                                ->options(
                                    School::whereHas('admissionBatches', function ($query) {
                                        $query->where('is_active', true)
                                              ->whereDate('start_date', '<=', now())
                                              ->whereDate('end_date', '>=', now());
                                    })->pluck('name', 'id')
                                )
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    $this->selectedSchool = School::find($state);
                                    $set('admission_batch_id', null); 
                                })
                                ->required(),

                            Select::make('admission_batch_id')
                                ->label('Pilih Gelombang Pendaftaran')
                                ->options(function ($get) {
                                    $schoolId = $get('school_id');
                                    if (!$schoolId) {
                                        return []; 
                                    }
                                    return AdmissionBatch::where('school_id', $schoolId)
                                        ->where('is_active', true)
                                        ->whereDate('start_date', '<=', now())
                                        ->whereDate('end_date', '>=', now())
                                        ->pluck('name', 'id');
                                })
                                ->live()
                                ->afterStateUpdated(function ($state) {
                                    $this->selectedBatch = AdmissionBatch::find($state);
                                })
                                ->required()
                                ->helperText(function () {
                                    if ($this->selectedBatch && $this->selectedBatch->fee_amount > 0) {
                                        return new HtmlString('Biaya Pendaftaran: <strong>Rp ' . number_format($this->selectedBatch->fee_amount, 0, ',', '.') . '</strong>');
                                    }
                                    return null;
                                }),
                        ]),
                    
                    Step::make('2. Data Calon Siswa')
                        ->schema([
                            TextInput::make('full_name')
                                ->label('Nama Lengkap Calon Siswa')
                                ->required(),
                            Select::make('gender')
                                ->label('Jenis Kelamin')
                                ->options(['L' => 'Laki-laki', 'P' => 'Perempuan'])
                                ->required(),
                            TextInput::make('birth_place')
                                ->label('Tempat Lahir')
                                ->required(),
                            DatePicker::make('birth_date')
                                ->label('Tanggal Lahir')
                                ->required(),
                            TextInput::make('religion')
                                ->label('Agama')
                                ->required(),
                            TextInput::make('previous_school')
                                ->label('Asal Sekolah (TK/PAUD)')
                                ->required(),
                        ]),
                    
                    Step::make('3. Data Orang Tua/Wali')
                        ->schema([
                            TextInput::make('parent_name')
                                ->label('Nama Orang Tua / Wali')
                                ->required()
                                ->helperText('Kontak utama yang akan dihubungi oleh sekolah.'),
                            TextInput::make('parent_phone')
                                ->label('No. Handphone (WhatsApp)')
                                ->tel()
                                ->required(),
                            TextInput::make('parent_email')
                                ->label('Alamat Email')
                                ->email(),
                        ]),
                    
                    Step::make('4. Unggah Berkas')
                        ->schema([
                            FileUpload::make('payment_proof_path')
                                ->label('Unggah Bukti Pembayaran')
                                ->directory('ppdb-payments')
                                ->visibility('public')
                                ->helperText('Unggah bukti pembayaran jika gelombang ini berbayar.')
                                // Logika required: hanya jika gelombang dipilih DAN berbayar
                                ->required(function () {
                                    return $this->selectedBatch && $this->selectedBatch->fee_amount > 0;
                                })
                                // Sembunyikan field ini jika gelombang belum dipilih ATAU gratis
                                ->visible(function () {
                                    return $this->selectedBatch && $this->selectedBatch->fee_amount > 0;
                                }),
                        ]),
                ])
                ->submitAction(new HtmlString('
                    <button type="submit"
                        class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Daftar Sekarang
                    </button>
                '))
            ])
            ->model(AdmissionRegistration::class) // Kita set modelnya
            ->statePath('data'); // Kita bind semua data ke property $data
    }

    /**
     * Fungsi yang dijalankan saat form di-submit
     */
    public function create()
    {
        // Jika sudah sukses, jangan proses lagi
        if ($this->registrationSuccess) {
            return;
        }
        
        // Ambil data yang sudah divalidasi oleh form
        $data = $this->form->getState();

        // Ambil data batch untuk dapat foundation_id
        $batch = AdmissionBatch::find($data['admission_batch_id']);
        if (!$batch) {
            // Ini seharusnya tidak terjadi jika validasi lolos
            throw ValidationException::withMessages(['admission_batch_id' => 'Gelombang pendaftaran tidak valid.']);
        }

        try {
            // Kita pakai database transaction
            DB::transaction(function () use ($data, $batch) {
                AdmissionRegistration::create([
                    'foundation_id' => $batch->foundation_id, // Ambil dari batch
                    'school_id' => $data['school_id'],
                    'status' => 'baru',
                    'registration_wave' => $batch->name, // Ambil nama gelombang
                    'registration_number' => 'PPDB-' . $data['school_id'] . '-' . time(), // Nomor pendaftaran simpel
                    
                    'full_name' => $data['full_name'],
                    'gender' => $data['gender'],
                    'birth_place' => $data['birth_place'],
                    'birth_date' => $data['birth_date'],
                    'religion' => $data['religion'],
                    'previous_school' => $data['previous_school'],
                    
                    'parent_name' => $data['parent_name'],
                    'parent_phone' => $data['parent_phone'],
                    'parent_email' => $data['parent_email'],
                    
                    'payment_proof_path' => $data['payment_proof_path'] ?? null,
                ]);
            });

            // Reset form
            $this->form->fill(); 
            // Set flag sukses
            $this->registrationSuccess = true;

        } catch (\Exception $e) {
            // Kirim notifikasi error ke user
            Notification::make()
                ->title('Pendaftaran Gagal')
                ->body('Terjadi kesalahan saat menyimpan data: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Render komponen (dan set layout publik)
     */
    public function render(): View
    {
        // Menggunakan layout public.blade.php yang kita buat tadi
        return view('livewire.public.ppdb-form');
    }
}