@extends('layouts.guest')
@section('title', 'Halaman tidak ditemukan')
@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Halaman tidak ditemukan</h1>
        <p class="mt-3 text-slate-600">Tautan yang kamu buka sudah tidak tersedia atau kode laporan salah. Coba cari ulang barangnya.</p>
        <x-button :href="route('items.index')" class="mt-6">Cari barang</x-button>
    </div>
@endsection
