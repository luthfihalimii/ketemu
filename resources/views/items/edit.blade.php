@extends('layouts.app')

@section('title', 'Ubah Laporan')

@section('content')
    <div class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Ubah Laporan"
            description="Perbaiki data selama laporan masih dapat diubah. Jawaban verifikasi tidak dapat diubah di sini — hubungi admin bila salah."
            icon="pencil"
        />

        <form
            method="POST"
            action="{{ route('items.update', $item) }}"
            enctype="multipart/form-data"
            class="card space-y-6 p-6"
        >
            @csrf
            @method('PUT')

            <div class="grid gap-5 sm:grid-cols-2">
                <x-select
                    name="category_id"
                    label="Kategori barang"
                    placeholder="Pilih kategori"
                    required
                    :options="$categories->pluck('name', 'id')->all()"
                    :selected="old('category_id', $item->category_id)"
                />

                <x-input
                    name="title"
                    label="Nama barang"
                    required
                    :value="old('title', $item->title)"
                />
            </div>

            <x-textarea
                name="description"
                label="Deskripsi singkat"
                hint="Hanya informasi umum. Jangan tulis ciri rahasia."
                :value="old('description', $item->description)"
            />

            @if ($item->isFoundReport())
                <x-textarea
                    name="private_note"
                    label="Catatan internal (tidak tampil ke publik)"
                    hint="Hanya terlihat oleh kamu, satpam, dan admin."
                    :value="old('private_note', $item->private_note)"
                />
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="color" label="Warna" :value="old('color', $item->color)" />
                <x-input name="brand" label="Merek" :value="old('brand', $item->brand)" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-select
                    name="location_id"
                    label="{{ $item->isLostReport() ? 'Perkiraan lokasi kehilangan' : 'Lokasi ditemukan' }}"
                    placeholder="Pilih lokasi"
                    required
                    :options="$locations->pluck('name', 'id')->all()"
                    :selected="old('location_id', $item->location_id)"
                />

                <x-input
                    name="location_detail"
                    label="Detail lokasi"
                    :value="old('location_detail', $item->location_detail)"
                />
            </div>

            <x-input
                name="occurred_at"
                type="datetime-local"
                label="Waktu"
                required
                :value="old('occurred_at', $item->occurred_at?->format('Y-m-d\TH:i'))"
            />

            @if ($item->isFoundReport())
                <x-select
                    name="deposit_location_id"
                    label="Lokasi penitipan"
                    placeholder="Pilih pos satpam"
                    :options="$depositLocations->pluck('name', 'id')->all()"
                    :selected="old('deposit_location_id', $item->deposit_location_id)"
                    hint="Tidak dapat diubah setelah barang dititipkan."
                    :disabled="$item->status !== \App\Enums\ItemStatus::WaitingDeposit"
                />

                <x-input
                    name="deposit_note"
                    label="Catatan untuk satpam (opsional)"
                    :value="old('deposit_note', $item->deposit_note)"
                />
            @endif

            <x-photo-upload />

            <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                <x-button type="submit" size="lg" icon="check">Simpan Perubahan</x-button>
                <x-button :href="route('items.show', $item)" variant="secondary" size="lg">Batal</x-button>
            </div>
        </form>
    </div>
@endsection
