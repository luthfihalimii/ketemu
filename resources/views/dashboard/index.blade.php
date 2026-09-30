@extends('layouts.app')

@section('title', 'Riwayat Saya')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Halo, {{ $user->name }}"
            description="Pantau laporan dan klaim yang kamu buat."
            icon="layout-dashboard"
        >
            <x-button :href="route('items.create-found')" icon="plus">Laporkan Temuan</x-button>
            <x-button :href="route('items.create-lost')" variant="secondary" icon="clipboard-list">Laporkan Hilang</x-button>
        </x-page-header>

        <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['clipboard-list', 'Laporan dibuat', $stats['reports'], 'text-slate-900'],
                ['clock', 'Menunggu dititipkan', $stats['awaiting_deposit'], 'text-amber-600'],
                ['archive-box', 'Tersedia', $stats['available'], 'text-emerald-600'],
                ['ticket', 'Siap diambil', $stats['ready_for_pickup'], 'text-brand-700'],
            ] as [$icon, $label, $value, $color])
                <div class="card flex items-center gap-4 p-5">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                        <x-icon :name="$icon" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-2xl font-bold {{ $color }}">{{ $value }}</p>
                        <p class="text-sm text-slate-500">{{ $label }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="card p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Laporan terakhir</h2>
                    <a href="{{ route('dashboard.reports') }}" class="text-sm font-semibold text-brand-700 hover:underline">Lihat semua</a>
                </div>

                @if ($myReports->isEmpty())
                    <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                        Kamu belum membuat laporan.
                    </p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($myReports as $report)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <a href="{{ route('items.show', $report) }}" class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-800 hover:text-brand-700">{{ $report->title }}</p>
                                    <p class="text-xs text-slate-500">{{ $report->created_at->diffForHumans() }}</p>
                                </a>
                                <x-status-badge :status="$report->status" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Klaim terakhir</h2>
                    <a href="{{ route('dashboard.claims') }}" class="text-sm font-semibold text-brand-700 hover:underline">Lihat semua</a>
                </div>

                @if ($myClaims->isEmpty())
                    <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
                        Kamu belum mengajukan klaim.
                    </p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($myClaims as $claim)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $claim->item?->title }}</p>
                                    <p class="text-xs text-slate-500">
                                        Diajukan {{ $claim->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                @if ($claim->status === \App\Enums\ClaimStatus::Approved && $claim->pickupCode?->isUsable())
                                    <x-button :href="route('claims.pickup', $claim)" size="sm" icon="ticket">Kode</x-button>
                                @else
                                    <x-claim-status-badge :status="$claim->status" />
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        @if ($user->canVerifyPickup())
            <section class="card mt-6 flex flex-col items-start gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                        <x-icon name="shield" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="font-semibold text-slate-900">Panel Petugas Keamanan</h2>
                        <p class="text-sm text-slate-500">Verifikasi kode pengambilan saat pemilik datang.</p>
                    </div>
                </div>
                <x-button :href="route('guard.pickup.create')" icon="shield">Buka verifikasi kode</x-button>
            </section>
        @endif
    </div>
@endsection
