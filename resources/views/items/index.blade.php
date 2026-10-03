@extends('layouts.app')

@section('title', 'Cari Barang')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Cari Barang"
            description="Temukan informasi barang yang sudah ditemukan dan dititipkan ke satpam."
            icon="search"
        />

        <form method="GET" action="{{ route('items.index') }}" role="search" aria-label="Pencarian barang" class="card mb-8 p-5">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <x-input
                        name="q"
                        label="Kata kunci"
                        placeholder="Contoh: dompet hitam, AirPods, kunci motor"
                        :value="$filters['q'] ?? null"
                    />
                </div>

                <x-select
                    name="category"
                    label="Kategori"
                    placeholder="Semua kategori"
                    :selected="$filters['category'] ?? null"
                    :options="$categories->pluck('name', 'id')->all()"
                />

                <x-select
                    name="location"
                    label="Lokasi ditemukan"
                    placeholder="Semua lokasi"
                    :selected="$filters['location'] ?? null"
                    :options="$locations->pluck('name', 'id')->all()"
                />

                <x-input
                    name="date"
                    type="date"
                    label="Tanggal ditemukan"
                    :value="$filters['date'] ?? null"
                />

                <x-select
                    name="status"
                    label="Status"
                    placeholder="Semua status"
                    :selected="$filters['status'] ?? null"
                    :options="$statusOptions->all()"
                />

                <div class="flex items-end gap-3 md:col-span-2 lg:col-span-2">
                    <x-button type="submit" icon="search">Terapkan filter</x-button>
                    @if (collect($filters)->filter()->isNotEmpty())
                        <x-button :href="route('items.index')" variant="secondary">Hapus filter</x-button>
                    @endif
                </div>
            </div>
        </form>

        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-lg font-semibold text-slate-900">Hasil Pencarian</h2>
            <p class="text-sm text-slate-500">{{ $items->total() }} barang ditemukan</p>
        </div>

        @if ($items->isEmpty())
            <x-empty-state
                icon="search"
                title="Barang belum ditemukan"
                description="Coba gunakan kata kunci atau filter yang berbeda. Barang baru muncul setelah penemu mengonfirmasi penitipan ke satpam."
            >
                <x-button :href="route('items.index')" variant="secondary" icon="search">Cari Lagi</x-button>
            </x-empty-state>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($items as $item)
                    <x-item-card :item="$item" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $items->links() }}
            </div>
        @endif
    </div>
@endsection
