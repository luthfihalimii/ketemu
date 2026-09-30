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

            <div class="border-t border-slate-200 pt-6">
                <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                    <x-icon name="camera" class="h-5 w-5 text-brand-600" />
                    Foto barang (jika tersedia)
                </h2>
                <p class="help mb-3">Format JPG, PNG, atau WEBP. Maksimal 5 MB.</p>
                <input
                    type="file"
                    name="photo"
                    accept="image/jpeg,image/png,image/webp"
                    class="field file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100"
                >
                @error('photo')
                    <p class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-rose-600">
                        <x-icon name="alert-triangle" class="h-4 w-4" /> {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200 ring-inset">
                <p class="flex items-center gap-2 text-sm font-semibold text-amber-900">
                    <x-icon name="lock" class="h-4 w-4" />
                    Ciri khusus barang
                </p>
                <p class="mt-1 text-sm text-amber-800">
                    Sebutkan ciri yang hanya kamu ketahui. Informasi ini tidak ditampilkan kepada siapa pun.
                </p>

                <div class="mt-4">
                    <x-input
                        name="verification_answer"
                        label="Ciri khusus"
                        placeholder="Contoh: ada goresan inisial di bagian bawah"
                        required
                    />
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                <x-button type="submit" size="lg" icon="check">Kirim Laporan</x-button>
                <x-button :href="route('items.index')" variant="secondary" size="lg">Batal</x-button>
            </div>
        </form>
    </div>
@endsection
