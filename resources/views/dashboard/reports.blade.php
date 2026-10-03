@extends('layouts.app')

@section('title', 'Laporan Saya')

@section('content')
    <div class="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Laporan Saya"
            description="Semua laporan barang temuan dan kehilangan yang kamu buat."
            icon="clipboard-list"
        >
            <x-button :href="route('items.create-found')" icon="plus">Laporkan Temuan</x-button>
            <x-button :href="route('items.create-lost')" variant="secondary" icon="clipboard-list">Laporkan Hilang</x-button>
        </x-page-header>

        @if ($items->isEmpty())
            <x-empty-state
                icon="inbox"
                title="Belum ada laporan"
                description="Laporan barang temuan membantumu mengembalikan barang kepada pemiliknya."
            >
                <x-button :href="route('items.create-found')" icon="plus">Buat laporan pertama</x-button>
            </x-empty-state>
        @else
            <div class="space-y-4">
                @foreach ($items as $item)
                    <div class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                            <x-item-photo :item="$item" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-status-badge :status="$item->status" />
                                <span class="break-all font-mono text-xs text-slate-600">{{ $item->code }}</span>
                            </div>

                            <a href="{{ route('items.show', $item) }}" class="mt-1.5 block font-semibold text-slate-900 hover:text-brand-700">
                                {{ $item->title }}
                            </a>

                            <p class="text-sm text-slate-500">
                                {{ $item->category?->name ?? 'Tanpa kategori' }}
                                &middot; Dilaporkan {{ $item->created_at->translatedFormat('d M Y') }}
                            </p>

                            @if ($item->isLostReport())
                                <p class="mt-2 text-sm">
                                    @if ($item->matchedItem)
                                        <span class="inline-flex flex-wrap items-center gap-1.5 text-slate-600">
                                            <x-icon name="check-circle" class="h-4 w-4 text-emerald-600" />
                                            Dicocokkan dengan
                                            <a href="{{ route('items.show', $item->matchedItem) }}" class="font-medium text-brand-700 hover:underline">
                                                {{ $item->matchedItem->title }}
                                            </a>
                                        </span>
                                    @else
                                        <span class="text-slate-600">Belum ditautkan (opsional — klaim langsung dari katalog tetap bisa).</span>
                                    @endif
                                </p>
                            @endif

                            @if ($item->isDepositOverdue())
                                <div class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200 ring-inset">
                                    <p class="flex items-start gap-2 font-semibold">
                                        <x-icon name="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0" />
                                        Perlu ditindaklanjuti
                                    </p>
                                    <p class="mt-1">
                                        Sudah lebih dari {{ \App\Models\Item::depositReminderDays() }} hari sejak laporan dibuat, tapi penitipan ke satpam
                                        belum dikonfirmasi. Kalau barangnya sudah kamu titipkan, tekan tombol
                                        <span class="font-medium">Sudah dititipkan</span>.
                                    </p>
                                </div>
                            @endif

                            @if ($item->status === \App\Enums\ItemStatus::WaitingDeposit && $item->hold_until)
                                @if ($item->isHoldOverdue())
                                    <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800 ring-1 ring-rose-600/20 ring-inset">
                                        Tenggat titip lewat — segera titipkan ke satpam
                                    </p>
                                @else
                                    <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 ring-1 ring-sky-600/20 ring-inset">
                                        Wajib titip sebelum {{ $item->hold_until->translatedFormat('d M, H:i') }}
                                    </p>
                                @endif
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                            @if ($item->status === \App\Enums\ItemStatus::WaitingDeposit)
                                <img src="{{ app(\App\Services\PickupQrService::class)->svgDataUri($item->code) }}" alt="QR titip {{ $item->title }}" width="120" height="120" class="h-24 w-24 rounded-xl bg-white p-1.5 ring-1 ring-slate-200" loading="lazy" decoding="async">
                                <p class="text-xs text-slate-500">Tunjukkan ke satpam</p>
                                <form method="POST" action="{{ route('items.confirm-deposit', $item) }}">
                                    @csrf
                                    <x-button type="submit" variant="secondary" size="sm" icon="check">
                                        Sudah dititipkan
                                    </x-button>
                                </form>
                            @endif

                            @if ($item->isLostReport() && $item->status === \App\Enums\ItemStatus::Reported)
                                <x-button :href="route('dashboard.matches', $item)" variant="secondary" size="sm" icon="search">
                                    {{ $item->matchedItem ? 'Ubah tautan (opsional)' : 'Lihat yang mirip (opsional)' }}
                                </x-button>
                            @endif

                            <x-button :href="route('items.show', $item)" variant="secondary" size="sm">Detail</x-button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">{{ $items->links() }}</div>
        @endif
    </div>
@endsection
