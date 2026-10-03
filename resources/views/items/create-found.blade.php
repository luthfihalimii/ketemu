@extends('layouts.app')

@section('title', 'Laporkan Barang Temuan')

@section('content')
    <div class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Laporkan Barang Temuan"
            description="Isi informasi barang yang kamu temukan, lalu titipkan ke pos satpam terdekat."
            icon="package"
        />

        <x-alert type="info" :dismissible="false" class="mb-6">
            <p class="font-semibold">Barang tetap diserahkan secara fisik.</p>
            <p class="mt-1">
                Laporan ini hanya menyebarkan informasinya. Setelah melapor, serahkan barang ke pos satpam
                yang kamu pilih, lalu konfirmasi penitipannya pada halaman laporan.
            </p>
        </x-alert>

        <form
            method="POST"
            action="{{ route('items.store-found') }}"
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
                    placeholder="Contoh: Dompet kulit"
                    required
                />
            </div>

            <x-textarea
                name="description"
                label="Deskripsi singkat"
                placeholder="Contoh: Dompet berisi beberapa kartu, ditemukan di kursi lantai 2."
                hint="Jangan tuliskan ciri rahasia di sini — cukup informasi umum."
            />

            <x-textarea
                name="private_note"
                label="Catatan internal (tidak tampil ke publik)"
                placeholder="Contoh: ada goresan di sisi kiri untuk dikenali satpam."
                hint="Hanya terlihat oleh kamu, satpam, dan admin."
            />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="color" label="Warna" placeholder="Contoh: Hitam" />
                <x-input name="brand" label="Merek" placeholder="Contoh: Eiger" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-select
                    name="location_id"
                    label="Lokasi ditemukan"
                    placeholder="Pilih lokasi"
                    required
                    :options="$locations->pluck('name', 'id')->all()"
                />

                <x-input
                    name="location_detail"
                    label="Detail lokasi"
                    placeholder="Contoh: Lantai 2 dekat lift"
                />
            </div>

            <x-input
                name="occurred_at"
                type="datetime-local"
                label="Waktu ditemukan"
                required
                :value="old('occurred_at', now()->format('Y-m-d\TH:i'))"
            />

            <x-photo-upload />

            <div class="border-t border-slate-200 pt-6">
                <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                    <x-icon name="shield" class="h-5 w-5 text-brand-600" />
                    Penitipan &amp; verifikasi kepemilikan
                </h2>

                <div class="mt-4 space-y-5">
                    <x-select
                        name="deposit_location_id"
                        label="Lokasi penitipan"
                        placeholder="Pilih pos satpam"
                        required
                        :options="$depositLocations->pluck('name', 'id')->all()"
                        hint="Pos satpam tempat kamu akan menyerahkan barang."
                    />

                    <x-input
                        name="deposit_note"
                        label="Catatan untuk satpam (opsional)"
                        placeholder="Contoh: Diserahkan ke Pak Budi shift siang"
                    />

                    <div class="rounded-xl bg-amber-50 p-4 ring-1 ring-amber-200 ring-inset">
                        <p class="flex items-center gap-2 text-sm font-semibold text-amber-900">
                            <x-icon name="lock" class="h-4 w-4" />
                            Pertanyaan verifikasi kepemilikan
                        </p>
                        <p class="mt-1 text-sm text-amber-800">
                            Tulis satu ciri yang <strong>tidak terlihat jelas di foto</strong>. Jawaban ini
                            disimpan sebagai hash dan <strong>tidak pernah ditampilkan</strong> kepada publik —
                            hanya dipakai untuk memverifikasi pemilik.
                        </p>

                        <div class="mt-4 space-y-4">
                            <x-input
                                name="verification_question"
                                label="Pertanyaan"
                                placeholder="Contoh: Apa yang ada di dalam dompet ini?"
                                hint="Biarkan kosong untuk memakai pertanyaan bawaan."
                            />

                            <x-input
                                name="verification_answer"
                                label="Jawaban yang benar"
                                placeholder="Contoh: ada stiker bulan sabit di bagian dalam"
                                required
                                hint="Gunakan ciri yang spesifik, bukan yang mudah ditebak."
                            />
                        </div>
                    </div>

                    <x-checkbox
                        name="confirm_deposit"
                        label="Barang sudah saya titipkan ke satpam"
                        hint="Centang bila barang sudah benar-benar berada di pos satpam. Setelah ini barang dapat diklaim pemiliknya."
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
