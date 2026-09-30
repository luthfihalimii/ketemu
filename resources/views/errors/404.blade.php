@extends('layouts.guest')

@section('title', 'Halaman tidak ditemukan')

@section('content')
    <div class="mx-auto flex w-full max-w-lg flex-col items-center px-4 py-20 text-center sm:px-6">
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-500">
            <x-icon name="search" class="h-8 w-8" />
        </span>

        <h1 class="mt-6 text-2xl font-bold tracking-tight text-slate-900">Halaman tidak ditemukan</h1>
        <p class="mt-2 text-sm text-slate-600">
            Halaman atau barang yang kamu cari mungkin sudah tidak tersedia.
        </p>

        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <x-button :href="route('items.index')" icon="search">Cari Barang</x-button>
            <x-button :href="route('home')" variant="secondary" icon="home">Beranda</x-button>
        </div>
    </div>
@endsection
