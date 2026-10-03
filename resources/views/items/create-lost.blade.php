@extends('layouts.app')

@section('title', 'Laporkan Barang Hilang')

@section('content')
    <div class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Laporkan Barang Hilang"
            description="Catat barangmu yang hilang agar lebih mudah dicocokkan dengan barang temuan."
            icon="clipboard-list"
        />

        <x-alert type="info" :dismissible="false" class="mb-6">
            Barang hilang tidak muncul di halaman pencarian publik. Ciri khusus yang kamu tulis
            hanya dipakai sebagai bukti kepemilikan bila barangmu ditemukan.
        </x-alert>

        <form
            method="POST"
            action="{{ route('items.store-lost') }}"
            enctype="multipart/form-data"
            class="card space-y-6 p-6"
        >
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <x-select
                    name="category_id"
                    label="Kategori barang"
                    placeholder="Pilih kategori"
                    required
                    :options="$categories->pluck('name', 'id')->all()"
                />

                <x-input
                    name="title"
                    label="Nama barang"
                    placeholder="Contoh: Tumbler biru"
                    required
                />
            </div>

            <x-textarea
                name="description"
                label="Deskripsi"
                placeholder="Contoh: Tumbler stainless 500ml, ada tulisan nama di bagian bawah."
                required
            />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="color" label="Warna" placeholder="Contoh: Biru" />
                <x-input name="brand" label="Merek" placeholder="Contoh: Lock & Lock" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-select
                    name="location_id"
                    label="Perkiraan lokasi kehilangan"
                    placeholder="Pilih lokasi"
                    required
                    :options="$locations->pluck('name', 'id')->all()"
                />

                <x-input
                    name="location_detail"
                    label="Detail lokasi"
                    placeholder="Contoh: Kantin lantai 1"
                />
            </div>

            <x-input
                name="occurred_at"
                type="datetime-local"
                label="Perkiraan waktu kehilangan"
                required
                :value="old('occurred_at', now()->format('Y-m-d\TH:i'))"
            />

            <x-photo-upload />

            <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                <x-button type="submit" size="lg" icon="check">Kirim Laporan</x-button>
                <x-button :href="route('items.index')" variant="secondary" size="lg">Batal</x-button>
            </div>
        </form>
    </div>
@endsection
