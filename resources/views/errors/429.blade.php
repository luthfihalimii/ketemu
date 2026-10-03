@extends('layouts.guest')
@section('title', 'Terlalu banyak percobaan')
@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Terlalu banyak percobaan</h1>
        <p class="mt-3 text-slate-600">Tunggu beberapa saat sebelum mencoba kembali. Data yang sudah tersimpan tetap aman.</p>
        @if (isset($exception) && ($seconds = $exception->getHeaders()['Retry-After'] ?? null))
            <p class="mt-2 text-sm text-slate-600">Coba kembali dalam {{ (int) $seconds }} detik.</p>
        @endif
        <x-button :href="route('home')" class="mt-6">Kembali ke beranda</x-button>
    </div>
@endsection
