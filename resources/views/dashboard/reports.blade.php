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
                            @if ($item->photo_path)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->photo_path) }}"
                                    alt="Foto {{ $item->title }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center text-slate-300">
                                    <x-icon name="package" class="h-7 w-7" />
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-status-badge :status="$item->status" />
                                <span class="font-mono text-xs text-slate-400">{{ $item->code }}</span>
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
                                        <span class="text-slate-400">Belum dicocokkan dengan barang temuan.</span>
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
                        </div>

                        <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                            @if ($item->status === \App\Enums\ItemStatus::WaitingDeposit)
                                <form method="POST" action="{{ route('items.confirm-deposit', $item) }}">
                                    @csrf
                                    <x-button type="submit" variant="success" size="sm" icon="check">
                                        Sudah dititipkan
                                    </x-button>
                                </form>
                            @endif

                            @if ($item->isLostReport() && $item->status === \App\Enums\ItemStatus::Reported)
                                <x-button :href="route('dashboard.matches', $item)" variant="secondary" size="sm" icon="search">
                                    {{ $item->matchedItem ? 'Ubah kecocokan' : 'Cari kecocokan' }}
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
