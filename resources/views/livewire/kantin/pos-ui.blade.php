<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" wire:poll.15s="loadProducts">
    {{-- KOLOM KIRI: DAFTAR PRODUK --}}
    <div class="lg:col-span-2">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @forelse ($products as $product)
                <div wire:click="addToCart({{ $product->id }})"
                    class="relative flex flex-col cursor-pointer overflow-hidden rounded-xl bg-white dark:bg-gray-800 
                           shadow-sm hover:shadow-lg hover:scale-[1.02] transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="aspect-square w-full overflow-hidden bg-gray-100 dark:bg-gray-700">
                        @if ($product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                class="h-full w-full object-cover transition-transform duration-300 hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <x-heroicon-o-photo class="h-1/2 w-1/2 text-gray-400 dark:text-gray-500" />
                            </div>
                        @endif
                    </div>

                    <div class="p-3 text-center">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                            {{ $product->name }}
                        </h3>
                        <p class="mt-1 text-xs font-bold text-primary-600 dark:text-primary-400">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-500 dark:text-gray-400">
                    <p>Tidak ada produk yang tersedia untuk dijual.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- KOLOM KANAN: KERANJANG & PEMBAYARAN --}}
    <div class="lg:col-span-1">
        <div class="sticky top-16 space-y-6 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 
                    shadow-md p-4 transition-all duration-200">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Keranjang</h2>

                <div class="max-h-64 space-y-3 overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-400 dark:scrollbar-thumb-gray-600">
                    @forelse ($cart as $productId => $item)
                        <div class="flex items-center justify-between gap-3 py-1 border-b border-gray-100 dark:border-gray-700 last:border-none"
                             wire:key="cart-item-{{ $productId }}">
                            {{-- Foto & Nama --}}
                            <div class="flex flex-1 items-center gap-2 overflow-hidden">
                                <img src="{{ $item['image'] ? Storage::url($item['image']) : url('/images/default-product.png') }}"
                                    alt="{{ $item['name'] }}" class="h-10 w-10 rounded-md object-cover">
                                <div class="flex-1 overflow-hidden">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Rp {{ number_format($item['price'], 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>

                            {{-- Quantity & Hapus --}}
                            <div class="flex items-center gap-2">
                                <input type="number" min="1" value="{{ $item['quantity'] }}"
                                    wire:change="updateCartQuantity({{ $productId }}, $event.target.value)"
                                    class="w-16 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 
                                           dark:text-gray-100 focus:border-primary-500 focus:ring-primary-500">
                                <button wire:click="removeFromCart({{ $productId }})"
                                    class="text-danger-500 hover:text-danger-700 dark:hover:text-danger-400 transition">
                                    <x-heroicon-o-trash class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-gray-500 dark:text-gray-400 py-3">
                            Keranjang masih kosong
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <div class="flex items-center justify-between">
                    <span class="text-base font-medium text-gray-700 dark:text-gray-200">Total</span>
                    <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Rp {{ number_format($total, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                {{-- Input RFID --}}
                <div>
                    <label for="rfid" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Scan Kartu RFID Pembeli
                    </label>
                    <div class="relative">
                        <input type="text" id="rfid" wire:model.live.debounce.300ms="rfid_tag_id"
                            placeholder="Tempelkan kartu atau ketik ID..."
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 
                                   bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                                   placeholder-gray-400 dark:placeholder-gray-500 px-4 py-2.5 pr-10
                                   shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-400/30 transition" autofocus>
                        <x-heroicon-o-identification class="absolute right-3 top-2.5 h-5 w-5 text-gray-400 dark:text-gray-500" />
                    </div>
                    <div wire:loading wire:target="updatedRidTagId" class="mt-2 text-sm text-primary-500">
                        Mencari kartu...
                    </div>
                </div>

                {{-- Info Pembeli --}}
                @if ($buyer && $buyerSavingAccount)
                    <div class="rounded-lg border border-green-300 bg-green-50 dark:border-green-600 dark:bg-green-900/40 p-3">
                        <p class="text-sm font-semibold text-green-800 dark:text-green-300">{{ $buyer->name }}</p>
                        <p class="text-xs text-gray-700 dark:text-gray-300">
                            Saldo: <span class="font-bold">
                                Rp {{ number_format($buyerSavingAccount->balance, 0, ',', '.') }}
                            </span>
                        </p>
                    </div>
                @endif

                {{-- Tombol Bayar --}}
                <button
                    wire:click="processPayment"
                    @disabled(empty($cart) || !$buyerSavingAccount)
                    class="w-full rounded-lg bg-blue-600 px-5 py-3 text-base font-medium text-black shadow-sm
                        hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                        disabled:cursor-not-allowed disabled:opacity-50 transition dark:bg-blue-500 dark:hover:bg-blue-400 dark:focus:ring-blue-400">
                    <span wire:loading.remove wire:target="processPayment">
                        PROSES & BAYAR
                    </span>
                    <span wire:loading wire:target="processPayment">
                        Memproses...
                    </span>
                </button>

            </div>
        </div>
    </div>
</div>
