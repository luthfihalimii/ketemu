@extends('layouts.app')

@section('title', 'Konfirmasi Password')

@section('content')
    <div class="mx-auto flex w-full max-w-md flex-col justify-center px-4 py-12 sm:px-6">
        <div class="card space-y-5 p-6">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Konfirmasi password</h1>
                <p class="mt-2 text-sm text-slate-600">
                    Halaman kode pengambilan setara dengan barang fisik. Masukkan passwordmu untuk melanjutkan.
                </p>
            </div>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                @csrf

                <x-input
                    name="password"
                    type="password"
                    label="Password"
                    required
                    autofocus
                    autocomplete="current-password"
                />

                <x-button type="submit" size="lg" icon="lock" block>Konfirmasi</x-button>
            </form>
        </div>
    </div>
@endsection
