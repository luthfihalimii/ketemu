@extends('layouts.app')

@section('title', 'Verifikasi Email')

@section('content')
    <div class="mx-auto flex w-full max-w-lg flex-col justify-center px-4 py-12 sm:px-6">
        <div class="card space-y-5 p-6 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                <x-icon name="bell" class="h-6 w-6" />
            </div>

            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Verifikasi email kampusmu</h1>
                <p class="mt-2 text-sm text-slate-600">
                    Kami sudah mengirim tautan verifikasi ke <span class="font-semibold text-slate-800">{{ auth()->user()->email }}</span>.
                    Buka email itu lalu klik tautannya untuk mengaktifkan fitur lapor dan klaim.
                </p>
            </div>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-button type="submit" size="lg" icon="check-circle" block>Kirim ulang email verifikasi</x-button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-xs text-slate-500">
                @csrf
                Salah email? <button type="submit" class="font-semibold text-brand-700 hover:underline">Keluar</button>
                lalu daftar ulang dengan email yang benar.
            </form>
        </div>

    </div>
@endsection
