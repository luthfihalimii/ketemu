@extends('layouts.guest')

@section('title', 'Sedang dalam perbaikan')

@section('content')
    <div class="mx-auto flex w-full max-w-lg flex-col items-center px-4 py-20 text-center sm:px-6">
        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-700">
            <x-icon name="shield" class="h-8 w-8" />
        </span>

        <h1 class="mt-6 text-2xl font-bold tracking-tight text-slate-900">KETEMU PENS sedang dalam perbaikan</h1>
        <p class="mt-2 text-sm text-slate-600">
            Kami sedang melakukan pemeliharaan singkat. Silakan coba beberapa saat lagi.
        </p>
    </div>
@endsection
