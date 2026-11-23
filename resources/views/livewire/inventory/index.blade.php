<div class="space-y-7">
    <section class="flex flex-col gap-5 border-b border-zinc-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">Pengelolaan aset</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">Inventaris barang</h1>
            <p class="mt-2 text-sm text-zinc-600">Pantau jumlah, kondisi, dan nilai barang yang Anda kelola.</p>
        </div>
        <flux:button variant="primary" wire:click="create">Tambah barang</flux:button>
    </section>

    @if (session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <section aria-label="Ringkasan inventaris" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-600">Jenis barang</p>
            <p class="mt-3 text-3xl font-semibold tabular-nums tracking-tight text-zinc-950">{{ number_format($summary['total_items']) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Barang aktif di inventaris</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-600">Jumlah unit</p>
            <p class="mt-3 text-3xl font-semibold tabular-nums tracking-tight text-zinc-950">{{ number_format($summary['total_quantity']) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Gabungan semua satuan</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-600">Perlu perhatian</p>
            <p class="mt-3 text-3xl font-semibold tabular-nums tracking-tight {{ $summary['attention_items'] > 0 ? 'text-amber-700' : 'text-zinc-950' }}">{{ number_format($summary['attention_items']) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Kondisi rusak ringan atau berat</p>
        </article>
        <article class="rounded-xl border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-600">Nilai perolehan</p>
            <p class="mt-3 text-2xl font-semibold tabular-nums tracking-tight text-zinc-950">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-zinc-500">Jumlah dikali harga per unit</p>
        </article>
    </section>

    @if ($showForm)
        <section class="rounded-xl border border-zinc-200 bg-white p-5 sm:p-7" aria-labelledby="form-title">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500">Data barang</p>
                    <h2 id="form-title" class="mt-1 text-xl font-semibold tracking-tight text-zinc-950">{{ $editingId ? 'Ubah barang' : 'Tambah barang' }}</h2>
                </div>
                <flux:button variant="ghost" wire:click="cancel" aria-label="Tutup formulir">Tutup</flux:button>
            </div>
            <form wire:submit="save" class="grid gap-x-5 gap-y-4 sm:grid-cols-2 xl:grid-cols-3">
                <flux:input wire:model="code" label="Kode barang" placeholder="INV-0001" required />
                <flux:input wire:model="name" label="Nama barang" required />
                <flux:input wire:model="category" label="Kategori" required />
                <flux:input wire:model="quantity" type="number" min="0" label="Jumlah" required />
                <flux:input wire:model="unit" label="Satuan" placeholder="unit, rim" required />
                <flux:select wire:model="condition" label="Kondisi" required>
                    <option value="baik">Baik</option>
                    <option value="rusak ringan">Rusak ringan</option>
                    <option value="rusak berat">Rusak berat</option>
                </flux:select>
                <flux:input wire:model="location" label="Lokasi penyimpanan" required />
                <flux:input wire:model="acquired_at" type="date" label="Tanggal perolehan" required />
                <flux:input wire:model="acquisition_cost" type="number" min="0" step="0.01" label="Harga perolehan per unit (Rp)" required />
                <flux:input wire:model="acquisition_source" label="Sumber perolehan" placeholder="Pembelian, hibah" required />
                <div class="flex flex-wrap items-center gap-3 border-t border-zinc-100 pt-4 sm:col-span-2 xl:col-span-3">
                    <flux:button type="submit" variant="primary">Simpan barang</flux:button>
                    <flux:button type="button" variant="ghost" wire:click="cancel">Batal</flux:button>
                </div>
            </form>
        </section>
    @endif

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
        <div class="flex flex-col gap-4 border-b border-zinc-200 p-4 sm:p-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <h2 class="font-semibold text-zinc-950">Daftar barang</h2>
                <p class="mt-1 text-sm text-zinc-600">{{ $items->total() }} data · 10 baris per halaman</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3 xl:w-[min(100%,52rem)]">
                <flux:input wire:model.live.debounce.300ms="search" type="search" label="Cari" placeholder="Kode, nama, lokasi" />
                <flux:select wire:model.live="categoryFilter" label="Kategori">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption }}">{{ $categoryOption }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="conditionFilter" label="Kondisi">
                    <option value="">Semua kondisi</option>
                    <option value="baik">Baik</option>
                    <option value="rusak ringan">Rusak ringan</option>
                    <option value="rusak berat">Rusak berat</option>
                </flux:select>
            </div>
        </div>

        <div class="overflow-x-auto [contain:layout]">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                    <tr>
                        <th scope="col" class="px-4 py-3.5">Barang</th>
                        <th scope="col" class="px-4 py-3.5">Kategori</th>
                        <th scope="col" class="px-4 py-3.5">Jumlah</th>
                        <th scope="col" class="px-4 py-3.5">Kondisi</th>
                        <th scope="col" class="px-4 py-3.5">Lokasi</th>
                        <th scope="col" class="px-4 py-3.5">Perolehan</th>
                        <th scope="col" class="px-4 py-3.5"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($items as $item)
                        <tr wire:key="item-{{ $item->id }}" class="align-top transition-colors hover:bg-zinc-50/80">
                            <td class="whitespace-nowrap px-4 py-4"><span class="font-medium text-zinc-950">{{ $item->name }}</span><span class="mt-1 block font-mono text-xs text-zinc-500">{{ $item->code }}</span></td>
                            <td class="whitespace-nowrap px-4 py-4 text-zinc-700">{{ $item->category }}</td>
                            <td class="whitespace-nowrap px-4 py-4 font-medium tabular-nums text-zinc-900">{{ number_format($item->quantity) }} <span class="font-normal text-zinc-600">{{ $item->unit }}</span></td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                    'bg-emerald-50 text-emerald-800' => $item->condition === 'baik',
                                    'bg-amber-50 text-amber-800' => $item->condition === 'rusak ringan',
                                    'bg-red-50 text-red-800' => $item->condition === 'rusak berat',
                                ])>{{ ucfirst($item->condition) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-zinc-700">{{ $item->location }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-zinc-700">{{ $item->acquired_at->format('d/m/Y') }}<span class="mt-1 block text-xs text-zinc-600">Rp {{ number_format((float) $item->acquisition_cost, 0, ',', '.') }} / unit</span></td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex gap-1">
                                    <flux:button size="sm" variant="ghost" wire:click="edit({{ $item->id }})">Ubah</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="delete({{ $item->id }})" wire:confirm="Pindahkan barang ini ke daftar terhapus?">Hapus</flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                @if ($summary['total_items'] === 0 && ! $hasFilters)
                                    <div class="mx-auto max-w-md">
                                        <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-zinc-100 text-zinc-700" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="size-6"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v9M8 5.3l8 4.5"/></svg>
                                        </div>
                                        <h3 class="mt-4 text-base font-semibold text-zinc-950">Belum ada barang</h3>
                                        <p class="mt-2 text-sm leading-6 text-zinc-600">Tambahkan barang pertama untuk mulai mencatat jumlah, kondisi, lokasi, dan nilai perolehannya.</p>
                                        <flux:button class="mt-5" variant="primary" wire:click="create">Tambah barang pertama</flux:button>
                                    </div>
                                @elseif ($hasFilters)
                                    <div class="mx-auto max-w-md">
                                        <h3 class="font-semibold text-zinc-950">Tidak ada hasil</h3>
                                        <p class="mt-2 text-sm text-zinc-600">Coba ubah kata pencarian atau pilihan filter.</p>
                                        <flux:button class="mt-4" variant="ghost" wire:click="clearFilters">Hapus filter</flux:button>
                                    </div>
                                @else
                                    <p class="text-sm text-zinc-600">Belum ada barang aktif.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3">{{ $items->links() }}</div>
        @endif
    </section>

    @if ($deletedItems->isNotEmpty())
        <section x-data="{ open: false }" class="rounded-xl border border-zinc-200 bg-white">
            <button type="button" class="flex w-full items-center justify-between gap-4 p-5 text-left" x-on:click="open = !open" x-bind:aria-expanded="open">
                <span>
                    <span class="block font-semibold text-zinc-950">Barang terhapus</span>
                    <span class="mt-1 block text-sm text-zinc-600">{{ $deletedItems->count() }} barang · dapat dipulihkan kapan saja</span>
                </span>
                <span class="flex size-8 items-center justify-center rounded-lg border border-zinc-200 text-zinc-700" aria-hidden="true" x-text="open ? '−' : '+'"></span>
            </button>
            <ul x-cloak x-show="open" class="border-t border-zinc-200 px-5 py-2">
                @foreach ($deletedItems as $item)
                    <li wire:key="deleted-{{ $item->id }}" class="flex flex-col gap-3 border-b border-zinc-100 py-3 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                        <div><span class="font-medium text-zinc-800">{{ $item->name }}</span><span class="ml-2 font-mono text-xs text-zinc-500">{{ $item->code }}</span></div>
                        <flux:button size="sm" variant="ghost" wire:click="restore({{ $item->id }})">Pulihkan</flux:button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
