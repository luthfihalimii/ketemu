@extends('layouts.guest')
@section('title', 'Layanan sedang perawatan')
@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Layanan sedang perawatan</h1>
        <p class="mt-3 text-slate-600">KETEMU PENS sedang diperbarui. Silakan kembali beberapa saat lagi. Barang fisik tetap aman di pos satpam.</p>
        <x-button :href="route('home')" class="mt-6">Muat ulang</x-button>
    </div>
@endsection
