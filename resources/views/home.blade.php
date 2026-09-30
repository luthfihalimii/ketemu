@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <section class="relative overflow-hidden border-b border-slate-200 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-brand-200 ring-inset">
                        <x-icon name="sparkles" class="h-3.5 w-3.5" />
                        Lost &amp; Found PENS
                    </span>

                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                        Kehilangan barang di PENS?
                        <span class="block text-brand-600">Tenang, siapa tahu sudah ditemukan.</span>
                    </h1>

                    <p class="mt-4 max-w-xl text-base text-slate-600">
                        Cari barang hilang atau laporkan barang yang kamu temukan.
                        KETEMU PENS memusatkan informasinya, sedangkan penitipan dan pengambilan tetap
                        melalui petugas keamanan kampus.
                    </p>

                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                        <x-button :href="route('items.index')" size="lg" icon="search">Cari Barang</x-button>
                        <x-button :href="auth()->check() ? route('items.create-found') : route('register')" variant="secondary" size="lg" icon="plus">
                            Laporkan Barang
                        </x-button>
                    </div>

                    <dl class="mt-8 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Tersedia saat ini</dt>
                            <dd class="text-2xl font-bold text-emerald-600">{{ $availableCount }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Sudah dikembalikan</dt>
                            <dd class="text-2xl font-bold text-brand-600">{{ $returnedCount }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="hidden lg:block">
                    <div class="card p-6">
                        <p class="text-sm font-semibold text-slate-500">Alur KETEMU PENS</p>
                        <ol class="mt-4 space-y-4">
                            @foreach ([
                                ['package', 'Mahasiswa menemukan barang', 'Laporkan lewat KETEMU PENS'],
                                ['shield', 'Barang dititipkan ke satpam', 'Tentukan pos penitipan'],
                                ['search', 'Pemilik mencari informasinya', 'Cari berdasarkan kategori & lokasi'],
                                ['ticket', 'Klaim & ambil dengan kode', 'Verifikasi kepemilikan lebih dulu'],
                            ] as $index => [$icon, $title, $subtitle])
                                <li class="flex gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                                        <x-icon :name="$icon" class="h-4.5 w-4.5" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ $index + 1 }}. {{ $title }}
                                        </p>
                                        <p class="text-sm text-slate-500">{{ $subtitle }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-5 sm:grid-cols-3">
            <a href="{{ route('items.index') }}" class="card group flex flex-col gap-3 p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                    <x-icon name="search" class="h-5 w-5" />
                </span>
                <h2 class="font-semibold text-slate-900">Cari Barang</h2>
                <p class="text-sm text-slate-500">Temukan barangmu yang sudah dilaporkan orang lain.</p>
                <span class="mt-auto inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700">
                    Mulai cari <x-icon name="arrow-left" class="h-4 w-4 rotate-180" />
                </span>
            </a>

            <a href="{{ auth()->check() ? route('items.create-found') : route('login') }}" class="card group flex flex-col gap-3 p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    <x-icon name="package" class="h-5 w-5" />
                </span>
                <h2 class="font-semibold text-slate-900">Laporkan Temuan</h2>
                <p class="text-sm text-slate-500">Menemukan barang? Laporkan agar pemiliknya bisa menemukannya.</p>
                <span class="mt-auto inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700">
                    Buat laporan <x-icon name="arrow-left" class="h-4 w-4 rotate-180" />
                </span>
            </a>

            <a href="{{ auth()->check() ? route('items.create-lost') : route('login') }}" class="card group flex flex-col gap-3 p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-700">
                    <x-icon name="clipboard-list" class="h-5 w-5" />
                </span>
                <h2 class="font-semibold text-slate-900">Barang Hilang</h2>
                <p class="text-sm text-slate-500">Laporkan barangmu yang hilang supaya lebih mudah dicocokkan.</p>
                <span class="mt-auto inline-flex items-center gap-1.5 text-sm font-semibold text-rose-700">
                    Buat laporan <x-icon name="arrow-left" class="h-4 w-4 rotate-180" />
                </span>
            </a>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-900">Barang terbaru ditemukan</h2>
                <p class="text-sm text-slate-500">Laporan yang sudah dititipkan ke pos satpam.</p>
            </div>
            <x-button :href="route('items.index')" variant="secondary" size="sm" iconTrailing="arrow-left">
                Lihat semua
            </x-button>
        </div>

        @if ($latestItems->isEmpty())
            <x-empty-state
                icon="inbox"
                title="Belum ada barang temuan"
                description="Saat ini belum ada laporan barang ditemukan yang tersedia. Coba periksa kembali nanti."
            />
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($latestItems as $item)
                    <x-item-card :item="$item" />
                @endforeach
            </div>
        @endif
    </section>

    <section class="border-t border-slate-200 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <h2 class="text-xl font-bold tracking-tight text-slate-900">Bagaimana KETEMU PENS bekerja?</h2>
            <p class="mt-1 max-w-3xl text-sm text-slate-600">
                Teknologi membantu proses pencarian dan verifikasi, sedangkan manusia tetap memegang peran utama
                dalam penitipan dan pengembalian barang.
            </p>

            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <h3 class="flex items-center gap-2 font-semibold text-slate-900">
                        <x-icon name="layout-dashboard" class="h-5 w-5 text-brand-600" />
                        Sistem menangani
                    </h3>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-600">
                        @foreach (['Penyimpanan informasi barang', 'Pencarian dan filter', 'Klaim dan verifikasi kepemilikan', 'Kode pengambilan', 'Pelacakan status'] as $entry)
                            <li class="flex items-start gap-2">
                                <x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-600" />
                                {{ $entry }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <h3 class="flex items-center gap-2 font-semibold text-slate-900">
                        <x-icon name="handshake" class="h-5 w-5 text-brand-600" />
                        Manusia menangani
                    </h3>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-600">
                        @foreach (['Menemukan barang', 'Menyerahkan barang ke satpam', 'Menyimpan barang secara fisik', 'Memverifikasi identitas saat pengambilan'] as $entry)
                            <li class="flex items-start gap-2">
                                <x-icon name="check" class="mt-0.5 h-4 w-4 text-emerald-600" />
                                {{ $entry }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>
@endsection
