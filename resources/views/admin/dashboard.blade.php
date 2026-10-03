@extends('layouts.app')

@section('title', 'Dasbor Admin')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Dasbor Admin"
            description="Ringkasan operasional KETEMU PENS: laporan, klaim, penitipan, dan pengguna."
            icon="layout-dashboard"
        >
            <x-button :href="route('admin.items.index')" variant="secondary" size="sm" icon="shield">Moderasi</x-button>
            <x-button :href="route('admin.analytics')" variant="secondary" size="sm" icon="chart">Analitik</x-button>
            <x-button :href="route('admin.items.export')" variant="secondary" size="sm" icon="download">Export CSV</x-button>
        </x-page-header>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Total laporan</p><p class="mt-1 text-3xl font-bold">{{ $stats['items_total'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Tersedia (STORED)</p><p class="mt-1 text-3xl font-bold">{{ $stats['items_stored'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Menunggu titip</p><p class="mt-1 text-3xl font-bold">{{ $stats['items_waiting_deposit'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Sudah kembali</p><p class="mt-1 text-3xl font-bold">{{ $stats['items_returned'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Ditandai</p><p class="mt-1 text-3xl font-bold">{{ $stats['items_flagged'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Titip overdue</p><p class="mt-1 text-3xl font-bold">{{ $stats['deposit_overdue'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Belum konfirmasi satpam</p><p class="mt-1 text-3xl font-bold">{{ $stats['unconfirmed_deposits'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Klaim aktif</p><p class="mt-1 text-3xl font-bold">{{ $stats['claims_active'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Klaim selesai</p><p class="mt-1 text-3xl font-bold">{{ $stats['claims_completed'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Kode aktif</p><p class="mt-1 text-3xl font-bold">{{ $stats['pickup_active'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Pengguna</p><p class="mt-1 text-3xl font-bold">{{ $stats['users_total'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold text-slate-500 uppercase">Dinonaktifkan</p><p class="mt-1 text-3xl font-bold">{{ $stats['users_banned'] }}</p></div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <div class="card p-5">
                <h2 class="font-semibold">Laporan per status</h2>
                <ul class="mt-3 space-y-1 text-sm">
                    @foreach ($itemsByStatus as $status => $total)
                        <li class="flex justify-between"><span class="font-mono">{{ $status }}</span><span class="font-bold">{{ $total }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="card p-5">
                <h2 class="font-semibold">Klaim per status</h2>
                <ul class="mt-3 space-y-1 text-sm">
                    @foreach ($claimsByStatus as $status => $total)
                        <li class="flex justify-between"><span class="font-mono">{{ $status }}</span><span class="font-bold">{{ $total }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card mt-6 p-5">
            <h2 class="font-semibold">Aktivitas terbaru</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($recentLogs as $log)
                    <li class="flex flex-wrap gap-2"><span class="text-slate-500">{{ $log->created_at?->format('d M H:i') }}</span><span class="font-mono text-xs">{{ $log->event }}</span><span>{{ $log->description }}</span></li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
