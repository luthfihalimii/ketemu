@extends('layouts.app')

@section('title', 'Cari Kecocokan')

@section('content')
    <div class="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="mb-5 text-sm text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('dashboard.reports') }}" class="font-medium text-brand-700 hover:underline">Laporan Saya</a>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-600">Kecocokan</span>
        </nav>

        <x-page-header
            title="Cari Kecocokan"
            description="Barang temuan yang mungkin milikmu. Mencocokkan hanya menandai laporan, bukan bukti kepemilikan."
            icon="search"
        />

        <x-alert type="info" :dismissible="false" class="mb-6">
            <p>
                Laporanmu: <span class="font-semibold">{{ $item->title }}</span>
                <span class="font-mono text-xs">({{ $item->code }})</span>
            </p>
            @if ($item->matchedItem)
                <p class="mt-1">
                    Saat ini dicocokkan dengan
                    <span class="font-semibold">{{ $item->matchedItem->title }}</span>
                    <span class="font-mono text-xs">({{ $item->matchedItem->code }})</span>.
                </p>
            @endif
        </x-alert>

        @error('found_item_id')
            <x-alert type="error" :dismissible="false" class="mb-6">{{ $message }}</x-alert>
        @enderror

        @error('match')
            <x-alert type="error" :dismissible="false" class="mb-6">{{ $message }}</x-alert>
        @enderror

        @if ($item->matchedItem)
            <form method="POST" action="{{ route('dashboard.matches.destroy', $item) }}" class="mb-6">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="secondary" icon="x">Batalkan kecocokan saat ini</x-button>
            </form>
        @endif

        <x-alert type="info" :dismissible="false" class="mb-6">
            Skor kecocokan dihitung otomatis (teks + kategori + lokasi + waktu) dan
            <strong>bukan bukti kepemilikan</strong>. Tautan tidak memberi hak ambil.
        </x-alert>

        @if ($candidates->isEmpty())
            <x-empty-state
                icon="inbox"
                title="Belum ada barang temuan yang mirip"
                description="Barang temuan dengan kategori yang sama belum ada. Coba periksa lagi nanti, atau jelajahi seluruh katalog."
            >
                <x-button :href="route('items.index')" icon="search">Jelajahi katalog</x-button>
            </x-empty-state>
        @else
            <h2 class="mb-4 text-base font-semibold text-slate-900">
                Kandidat berperingkat untuk laporanmu
            </h2>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($candidates as $candidate)
                    <div class="flex flex-col gap-3">
                        <x-item-card :item="$candidate" />
                        @if (($candidate->match_score ?? 0) > 0)
                            <p class="rounded-xl bg-brand-50 px-4 py-2 text-center text-sm ring-1 ring-brand-200 ring-inset">
                                <span class="font-bold text-brand-800">Kemiripan {{ $candidate->match_score }}%</span>
                                @if (! empty($candidate->match_reasons))
                                    <span class="block text-xs text-slate-600">{{ implode(' · ', $candidate->match_reasons) }}</span>
                                @endif
                            </p>
                        @endif

                        @if ($item->matched_item_id === $candidate->id)
                            <span class="rounded-xl bg-emerald-50 px-4 py-2.5 text-center text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200 ring-inset">
                                Sudah dicocokkan
                            </span>
                        @else
                            <form method="POST" action="{{ route('dashboard.matches.store', $item) }}">
                                @csrf
                                <input type="hidden" name="found_item_id" value="{{ $candidate->id }}">
                                <x-button type="submit" variant="secondary" block icon="check">
                                    Ini barangku
                                </x-button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-8 rounded-xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200 ring-inset">
                <p class="flex items-start gap-2">
                    <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                    Menandai kecocokan tidak otomatis memberi hak mengambil barang. Kamu tetap harus
                    membuka barang temuan tersebut dan menjawab pertanyaan verifikasi dari penemu.
                </p>
            </div>
        @endif
    </div>
@endsection
