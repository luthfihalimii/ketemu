@php
    $user = auth()->user();
    // One cheap indexed count per page so the bell badge stays accurate.
    $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'KETEMU PENS') — Kembali Temukan Barangmu di PENS</title>
    <meta name="description" content="@yield('meta_description', 'Platform Lost & Found Politeknik Elektronika Negeri Surabaya. Cari barang hilang atau laporkan barang temuan.')">

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-slate-50">
    <a
        href="#main"
        class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white"
    >
        Lompat ke konten utama
    </a>

    <x-flash />

    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex w-full max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-lg focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-icon name="package" class="h-5 w-5" />
                </span>
                <span class="leading-tight">
                    <span class="block text-base font-bold tracking-tight text-slate-900">KETEMU PENS</span>
                    <span class="hidden text-xs text-slate-500 sm:block">Lost &amp; Found Kampus</span>
                </span>
            </a>

            <nav class="ml-auto hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                <x-nav-link :href="route('home')" :active="request()->routeIs('home')" icon="home">Beranda</x-nav-link>
                <x-nav-link :href="route('items.index')" :active="request()->routeIs('items.index')" icon="search">Cari Barang</x-nav-link>

                @auth
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard*')" icon="layout-dashboard">Riwayat</x-nav-link>

                    @if ($user->canVerifyPickup())
                        <x-nav-link :href="route('guard.pickup.create')" :active="request()->routeIs('guard.*')" icon="shield">Verifikasi Kode</x-nav-link>
                    @endif

                    @if ($user->isAdmin())
                        <x-nav-link :href="route('admin.items.index')" :active="request()->routeIs('admin.*')" icon="shield">Moderasi</x-nav-link>
                    @endif

                    <x-button :href="route('items.create-found')" size="sm" icon="plus" class="ml-2">Laporkan</x-button>

                    <div class="ml-2 flex items-center gap-2 border-l border-slate-200 pl-3">
                        <a
                            href="{{ route('notifications.index') }}"
                            class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-brand-700 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none"
                            title="Notifikasi"
                            aria-label="Notifikasi{{ $unreadCount > 0 ? ", {$unreadCount} belum dibaca" : '' }}"
                        >
                            <x-icon name="bell" class="h-5 w-5" />
                            @if ($unreadCount > 0)
                                <span class="absolute -top-0.5 -right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-xs font-bold text-white">
                                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                </span>
                            @endif
                        </a>

                        <span class="max-w-[9rem] truncate text-sm font-medium text-slate-600">{{ $user->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-rose-600 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none"
                                title="Keluar"
                                aria-label="Keluar"
                            >
                                <x-icon name="log-out" class="h-5 w-5" />
                            </button>
                        </form>
                    </div>
                @else
                    <x-nav-link :href="route('login')" :active="request()->routeIs('login')" icon="lock">Masuk</x-nav-link>
                    <x-button :href="route('register')" size="sm" class="ml-2">Daftar</x-button>
                @endauth
            </nav>

            <details class="group ml-auto md:hidden">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 marker:hidden">
                    <x-icon name="menu" class="h-5 w-5" />
                    Menu
                </summary>

                <div class="absolute inset-x-4 top-full mt-2 origin-top rounded-2xl border border-slate-200 bg-white p-2 shadow-lg">
                    <x-mobile-nav-link :href="route('home')" icon="home">Beranda</x-mobile-nav-link>
                    <x-mobile-nav-link :href="route('items.index')" icon="search">Cari Barang</x-mobile-nav-link>

                    @auth
                        <x-mobile-nav-link :href="route('items.create-found')" icon="package">Laporkan Temuan</x-mobile-nav-link>
                        <x-mobile-nav-link :href="route('items.create-lost')" icon="clipboard-list">Laporkan Hilang</x-mobile-nav-link>
                        <x-mobile-nav-link :href="route('dashboard')" icon="layout-dashboard">Riwayat Saya</x-mobile-nav-link>

                        @if ($user->canVerifyPickup())
                            <x-mobile-nav-link :href="route('guard.pickup.create')" icon="shield">Verifikasi Kode</x-mobile-nav-link>
                        @endif

                        @if ($user->isAdmin())
                            <x-mobile-nav-link :href="route('admin.items.index')" icon="shield">Moderasi Admin</x-mobile-nav-link>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
                                <x-icon name="log-out" class="h-5 w-5" />
                                Keluar
                            </button>
                        </form>
                    @else
                        <x-mobile-nav-link :href="route('login')" icon="lock">Masuk</x-mobile-nav-link>
                        <x-mobile-nav-link :href="route('register')" icon="user">Daftar</x-mobile-nav-link>
                    @endauth
                </div>
            </details>
        </div>
    </header>

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-auto border-t border-slate-200 bg-white">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-slate-500 sm:px-6 lg:px-8">
            <p class="font-semibold text-slate-700">KETEMU PENS</p>
            <p>Kembali Temukan Barangmu di PENS.</p>
            <p class="text-xs">
                Barang fisik tetap dititipkan dan diserahkan melalui petugas keamanan PENS.
            </p>
        </div>
    </footer>
</body>
</html>
