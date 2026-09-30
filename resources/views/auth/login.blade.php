@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="mx-auto flex w-full max-w-md flex-col justify-center px-4 py-12 sm:px-6">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Masuk ke KETEMU PENS</h1>
            <p class="mt-1 text-sm text-slate-500">Gunakan email kampusmu untuk melanjutkan.</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="card space-y-5 p-6">
            @csrf

            <x-input
                name="email"
                type="email"
                label="Email"
                placeholder="nama@student.pens.ac.id"
                required
                autofocus
                autocomplete="username"
            />

            <x-input
                name="password"
                type="password"
                label="Password"
                required
                autocomplete="current-password"
            />

            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                >
                Ingat saya di perangkat ini
            </label>

            <x-button type="submit" size="lg" icon="lock" block>Masuk</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Daftar sekarang</a>
        </p>
    </div>
@endsection
