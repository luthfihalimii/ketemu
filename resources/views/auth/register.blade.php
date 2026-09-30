@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
    <div class="mx-auto flex w-full max-w-md flex-col justify-center px-4 py-12 sm:px-6">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Buat Akun KETEMU PENS</h1>
            <p class="mt-1 text-sm text-slate-500">Gratis untuk seluruh mahasiswa PENS.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="card space-y-5 p-6">
            @csrf

            <x-input
                name="name"
                label="Nama lengkap"
                placeholder="Nama sesuai KTM"
                required
                autofocus
                autocomplete="name"
            />

            <x-input
                name="email"
                type="email"
                label="Email"
                placeholder="nama@student.pens.ac.id"
                hint="Gunakan email yang aktif. Email tidak ditampilkan kepada pengguna lain."
                required
                autocomplete="username"
            />

            <x-input
                name="password"
                type="password"
                label="Password"
                hint="Minimal 8 karakter, kombinasi huruf dan angka."
                required
                autocomplete="new-password"
            />

            <x-input
                name="password_confirmation"
                type="password"
                label="Ulangi password"
                required
                autocomplete="new-password"
            />

            <x-button type="submit" size="lg" icon="user" block>Daftar</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Masuk di sini</a>
        </p>
    </div>
@endsection
