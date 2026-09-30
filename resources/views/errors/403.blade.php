@extends('layouts.guest')

@section('title', 'Akses ditolak')

@section('content')
    <div class="mx-auto flex w-full max-w-lg flex-col items-center px-4 py-20 text-center sm:px-6">
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-rose-600">
            <x-icon name="lock" class="h-8 w-8" />
        </span>

        <h1 class="mt-6 text-2xl font-bold tracking-tight text-slate-900">Akses ditolak</h1>
        <p class="mt-2 text-sm text-slate-600">
            Kamu tidak memiliki izin untuk membuka halaman ini. Bila menurutmu ini sebuah kesalahan,
            silakan hubungi admin kampus.
        </p>

        <x-button :href="route('home')" class="mt-6" icon="home">Kembali ke Beranda</x-button>
    </div>
@endsection
