@php
    $title = trim($__env->yieldContent('title')) ?: 'KETEMU PENS';
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} — KETEMU PENS</title>

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="description" content="@yield('meta_description', 'Akun KETEMU PENS — platform Lost & Found kampus PENS.')">
    <meta name="robots" content="noindex, nofollow">
    @stack('head')
</head>
<body class="flex min-h-full flex-col bg-slate-50">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-white">Lompat ke konten utama</a>
    <x-flash />

    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-lg focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-icon name="package" class="h-5 w-5" />
                </span>
                <span class="text-base font-bold tracking-tight text-slate-900">KETEMU PENS</span>
            </a>

            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Kembali
            </a>
        </div>
    </header>

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-auto border-t border-slate-200 bg-white">
        <div class="mx-auto w-full max-w-7xl px-4 py-6 text-xs text-slate-500 sm:px-6 lg:px-8">
            &copy; {{ date('Y') }} KETEMU PENS — Politeknik Elektronika Negeri Surabaya.
        </div>
    </footer>
</body>
</html>
