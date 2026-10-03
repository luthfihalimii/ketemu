@extends('layouts.guest')
@section('title', 'Silakan masuk kembali')
@section('content')
    <div class="mx-auto max-w-lg px-4 py-20 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Silakan masuk kembali</h1>
        <p class="mt-3 text-slate-600">Masuk ke akunmu untuk melanjutkan tindakan ini.</p>
        <x-button :href="route('login')" class="mt-6">Masuk</x-button>
    </div>
@endsection
