@extends('layouts.guest')
@section('title', 'Tindakan tidak tersedia')
@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Tindakan tidak tersedia</h1>
        <p class="mt-3 text-slate-600">Gunakan tombol atau formulir pada halaman aplikasi untuk melanjutkan.</p>
        <x-button :href="route('home')" class="mt-6">Kembali ke beranda</x-button>
    </div>
@endsection
