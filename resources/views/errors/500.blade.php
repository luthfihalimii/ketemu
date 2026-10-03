@extends('layouts.guest')
@section('title', 'Terjadi kesalahan')
@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Terjadi kesalahan</h1>
        <p class="mt-3 text-slate-600">Terjadi kesalahan saat memproses permintaanmu. Silakan coba kembali beberapa saat lagi. Laporan yang sudah tersimpan tetap aman.</p>
        <x-button :href="route('home')" class="mt-6">Kembali ke beranda</x-button>
    </div>
@endsection
