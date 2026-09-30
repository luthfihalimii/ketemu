@extends('layouts.guest')

@section('title', 'Terjadi kesalahan')

@section('content')
    <div class="mx-auto flex w-full max-w-lg flex-col items-center px-4 py-20 text-center sm:px-6">
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-rose-600">
            <x-icon name="alert-triangle" class="h-8 w-8" />
        </span>

        <h1 class="mt-6 text-2xl font-bold tracking-tight text-slate-900">Terjadi kesalahan</h1>
        <p class="mt-2 text-sm text-slate-600">
            Data belum dapat dimuat. Silakan coba kembali beberapa saat lagi. Bila terus berulang,
            hubungi admin kampus.
        </p>

        <x-button :href="route('home')" class="mt-6" icon="home">Kembali ke Beranda</x-button>
    </div>
@endsection
