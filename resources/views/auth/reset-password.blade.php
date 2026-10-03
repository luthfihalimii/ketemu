@extends('layouts.guest')
@section('title', 'Reset Password')
@section('content')
    <div class="mx-auto w-full max-w-md px-4 py-12">
        <h1 class="mb-6 text-2xl font-bold text-slate-900">Buat password baru</h1>
        <form method="POST" action="{{ route('password.update') }}" class="card space-y-5 p-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-input name="email" type="email" label="Email akun" :value="old('email', $email)" required autocomplete="email" />
            <x-input name="password" type="password" label="Password baru" required autocomplete="new-password" />
            <x-input name="password_confirmation" type="password" label="Konfirmasi password" required autocomplete="new-password" />
            <x-button type="submit" block>Simpan password</x-button>
        </form>
    </div>
@endsection
