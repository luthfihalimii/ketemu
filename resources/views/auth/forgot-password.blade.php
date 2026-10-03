@extends('layouts.guest')
@section('title', 'Lupa Password')
@section('content')
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <h1 class="mb-6 text-2xl font-bold text-slate-900">Pulihkan password</h1>
        <form method="POST" action="{{ route('password.email') }}" class="card space-y-5 p-6">
            @csrf
            <x-input name="email" type="email" label="Email akun" :value="old('email')" required autocomplete="email" />
            <x-button type="submit" block>Kirim tautan pemulihan</x-button>
            <a href="{{ route('login') }}" class="text-brand-700 hover:underline">Kembali ke halaman masuk</a>
        </form>
    </div>
@endsection
