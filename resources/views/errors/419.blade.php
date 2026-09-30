@extends('layouts.guest')

@section('title', 'Sesi berakhir')

@section('content')
    <div class="mx-auto flex w-full max-w-lg flex-col items-center px-4 py-20 text-center sm:px-6">
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-50 text-amber-600">
            <x-icon name="clock" class="h-8 w-8" />
        </span>

        <h1 class="mt-6 text-2xl font-bold tracking-tight text-slate-900">Sesi kamu sudah berakhir</h1>
        <p class="mt-2 text-sm text-slate-600">
            Demi keamanan, sesi otomatis berakhir setelah tidak ada aktivitas. Silakan masuk kembali.
        </p>

        <x-button :href="route('login')" class="mt-6" icon="lock">Masuk kembali</x-button>
    </div>
@endsection
