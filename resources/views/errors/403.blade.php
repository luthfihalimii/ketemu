@extends('layouts.guest')
@section('title', 'Akses ditolak')
@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Akses ditolak</h1>
        <p class="mt-3 text-slate-600">Kamu tidak memiliki izin untuk membuka halaman ini. Jika laporanmu seharusnya bisa diakses, hubungi admin.</p>
        <x-button :href="route('home')" class="mt-6">Kembali ke beranda</x-button>
    </div>
@endsection
