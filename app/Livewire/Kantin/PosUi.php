<?php

namespace App\Livewire\Kantin;

use App\Models\Pos\Product;
use App\Models\Pos\SaleTransaction;
use App\Models\Pos\Vendor;
use App\Models\Pos\VendorLedger;
use App\Models\SavingAccount;
use App\Models\SavingTransaction;
use App\Models\Student;
use App\Models\Teacher;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Filament\Notifications\Concerns\InteractsWithNotifications; // <-- WAJIB
use Filament\Notifications\Contracts\HasNotifications; // <-- WAJIB
use Illuminate\Support\Facades\Log;

class PosUi extends Component
{
    // --- PROPERTI STATE ---
    public ?Vendor $vendor;
    public $products = [];
    public $cart = [];
    public $total = 0;
    public $rfid_tag_id = '';
    public $buyer = null;
    public ?SavingAccount $buyerSavingAccount = null;

    /**
     * Jalankan saat halaman di-load
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->vendor = $user->vendor;

        if (!$this->vendor) {
            $this->dispatch('show-toast', 
                title: 'Akses Ditolak',
                body: 'Akun Anda tidak tertaut ke Vendor manapun.',
                status: 'danger'
            );
            return;

        }

        $this->loadProducts();
    }

    /**
     * Ambil daftar produk
     */
    public function loadProducts(): void
    {
        if ($this->vendor) {
            $this->products = Product::where('vendor_id', $this->vendor->id)
                ->where('status', 'available')
                ->orderBy('name')
                ->get();
        }
    }

    /**
     * Tambah produk ke keranjang
     */
    public function addToCart(int $productId): void
    {
        $product = $this->products->find($productId);
        if (!$product) {
            return; // Produk tidak ditemukan
        }

        // Cek jika sudah ada di keranjang
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        } else {
            // Tambah baru
            $this->cart[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image,
                'quantity' => 1,
            ];
        }

        $this->calculateTotal();
    }

    /**
     * Update jumlah item di keranjang
     */
    public function updateCartQuantity(int $productId, int $quantity): void
    {
        if (isset($this->cart[$productId])) {
            if ($quantity < 1) {
                $this->removeFromCart($productId);
            } else {
                $this->cart[$productId]['quantity'] = $quantity;
            }
            $this->calculateTotal();
        }
    }

    /**
     * Hapus item dari keranjang
     */
    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
        $this->calculateTotal();
    }

    /**
     * Hitung ulang total belanja
     */
    public function calculateTotal(): void
    {
        $this->total = 0;
        foreach ($this->cart as $item) {
            $this->total += $item['price'] * $item['quantity'];
        }
    }

    /**
     * Livewire Hook: Jalan otomatis saat $rfid_tag_id di-scan/diisi
     */
    public function updatedRfidTagId($value): void
    {
        $this->resetBuyer(); 

        if (empty($value)) {
            return;
        }

        // 1. HANYA Cari di tabel Student
        $this->buyer = Student::where('rfid_tag_id', $value)->first();

        // 2. Proses jika pembeli (Student) ditemukan
        if ($this->buyer) {
            $this->buyerSavingAccount = $this->buyer->savingAccount;

            if ($this->buyerSavingAccount) {
                $this->dispatch('show-toast', 
                    title: 'Pembeli Ditemukan: ' . $this->buyer->name,
                    body: 'Saldo: Rp ' . number_format($this->buyerSavingAccount->balance, 0, ',', '.'),
                    status: 'success'
                );
            } else {
                $this->resetBuyer();
                $this->dispatch('show-toast', 
                    title: 'Rekening Tidak Ditemukan',
                    body: 'Siswa' . $this->buyer->name . ' tidak memiliki rekening tabungan aktif.',
                    status: 'danger'
                );
            }
        } else {
            $this->dispatch('show-toast', 
                title: 'Tidak Ditemukan',
                body: 'ID Kartu tidak terdaftar sebagai Siswa',
                status: 'danger'
            );
        }
    }

    /**
     * Tombol "BAYAR" ditekan
     */
    public function processPayment(): void
    {
        // Log Langkah 1: Fungsi dimulai
        Log::info('--- POS DEBUG: processPayment() Dimulai ---');

        // --- Validasi ---
        if (empty($this->cart)) {
            Log::warning('POS DEBUG: Validasi GAGAL - Keranjang Kosong.');
            
            $this->dispatch('show-toast', 
                title: 'Keranjang Kosong',
                body: 'Pilih Item Produk Terlebih Dahulu!',
                status: 'danger'
            );
            return;
        }
        Log::info('POS DEBUG: Validasi 1 Lolos - Keranjang ada isinya.');

        if (!$this->buyer || !$this->buyerSavingAccount) {
           Log::warning('POS DEBUG: Validasi GAGAL - Pembeli atau Rekening null.');
           
           $this->dispatch('show-toast', 
                title: 'Scan Kartu Pembeli',
                body: 'Silakan scan kartu RFID pembeli terlebih dahulu.',
                status: 'danger'
            );
           return;
        }
        Log::info('POS DEBUG: Validasi 2 Lolos - Pembeli & Rekening ditemukan (' . $this->buyer->name . ').');

        if ($this->buyerSavingAccount->balance < $this->total) {
            Log::warning('POS DEBUG: Validasi GAGAL - Saldo Tidak Cukup. Saldo: ' . $this->buyerSavingAccount->balance . ' | Total: ' . $this->total);
            $this->dispatch('show-toast', 
                title: 'Saldo Tidak Cukup', 
                status: 'danger',
                body: 'Saldo pembeli (Rp ' . number_format($this->buyerSavingAccount->balance) . ') tidak cukup untuk membayar (Rp ' . number_format($this->total) . ''
            );
            return;
        }
        Log::info('POS DEBUG: Validasi 3 Lolos - Saldo Cukup.');

        // --- Proses Transaksi ---
        try {
            Log::info('POS DEBUG: Memulai DB::transaction...');
            
            DB::transaction(function () {
                Log::info('POS DEBUG: Di dalam DB::transaction. Membuat SavingTransaction...');
                
                // 1. Potong Saldo Pembeli (via SavingTransaction)
                $savingTrx = SavingTransaction::create([
                    'saving_account_id' => $this->buyerSavingAccount->id,
                    'foundation_id' => $this->vendor->foundation_id,
                    // 'school_id' => $this->vendor->school_id,
                    'type' => 'debit',
                    'amount' => $this->total,
                    'description' => 'Pembelian di ' . $this->vendor->name,
                ]);
                Log::info('POS DEBUG: SavingTransaction DIBUAT (ID: ' . $savingTrx->id . ')');

                // 2. Catat Struk Penjualan (SaleTransaction)
                Log::info('POS DEBUG: Membuat SaleTransaction...');
                $saleTrx = SaleTransaction::create([
                    'foundation_id' => $this->vendor->foundation_id,
                    'school_id' => $this->vendor->school_id,
                    'vendor_id' => $this->vendor->id,
                    'buyer_id' => $this->buyer->id,
                    'buyer_type' => get_class($this->buyer),
                    'transaction_code' => 'SALE-' . strtoupper(Str::random(10)),
                    'total_amount' => $this->total,
                    'items' => array_values($this->cart), 
                ]);
                Log::info('POS DEBUG: SaleTransaction DIBUAT (ID: ' . $saleTrx->id . ')');

                // 3. Tambah Saldo Vendor (VendorLedger)
                Log::info('POS DEBUG: Membuat VendorLedger...');
                $lastBalance = $this->vendor->ledgers()->latest()->first()?->balance_after ?? 0;
                $newBalance = $lastBalance + $this->total;

                VendorLedger::create([
                    'foundation_id' => $this->vendor->foundation_id,
                    'school_id' => $this->vendor->school_id,
                    'vendor_id' => $this->vendor->id,
                    'type' => 'credit',
                    'amount' => $this->total,
                    'balance_after' => $newBalance,
                    'description' => 'Penjualan ' . $saleTrx->transaction_code,
                    'reference_id' => $saleTrx->id,
                    'reference_type' => SaleTransaction::class,
                ]);
                Log::info('POS DEBUG: VendorLedger DIBUAT. Transaksi DB Selesai.');
            });

            // --- Jika Sukses ---
            Log::info('POS DEBUG: Transaksi DB SUKSES.');
                $this->dispatch('show-toast', 
                    title: 'Pembayaran Berhasil!', 
                    status: 'success',
                    body: 'Total Rp ' . number_format($this->total) . ' telah dibayar oleh ' . $this->buyer->name
                );

            $this->resetAll();

        } catch (\Exception $e) {
            // --- Jika Gagal ---
            Log::error('POS DEBUG: Transaksi GAGAL - Exception ditangkap!');
            Log::error('Error Message:' . $e->getMessage());
            Log::error('Error File: ' . $e->getFile() . ' on line ' . $e->getLine());
            // Log::error('Error Trace: ' . $e->getTraceAsString()); // Uncomment jika perlu trace lengkap
            
            $this->dispatch('show-toast', 
                title: 'Transaksi Gagal', 
                status: 'danger',
                body: $e->getMessage()
            );
        }

        Log::info('--- POS DEBUG: processPayment() Selesai ---');
    }

    /**
     * Reset semua state ke awal
     */
    public function resetAll(): void
    {
        $this->cart = [];
        $this->total = 0;
        $this->rfid_tag_id = '';
        $this->buyer = null;
        $this->buyerSavingAccount = null;
    }

    /**
     * Reset data pembeli
     */
    public function resetBuyer(): void
    {
        $this->buyer = null;
        $this->buyerSavingAccount = null;
    }

    /**
     * Render view-nya
     */
    public function render()
    {
        return view('livewire.kantin.pos-ui')
        ->layout('components.layouts.pos', [ // Pakai layout utama kita
                'title' => 'POS KASIR- ' . $this->vendor->name
            ]);
    }
}