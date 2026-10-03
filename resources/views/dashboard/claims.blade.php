@extends('layouts.app')

@section('title', 'Klaim Saya')

@section('content')
    <div class="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Klaim Saya"
            description="Status klaim barang yang kamu ajukan."
            icon="hand-raised"
        />

        @if ($claims->isEmpty())
            <x-empty-state
                icon="search"
                title="Belum ada klaim"
                description="Cari barangmu di halaman pencarian, lalu ajukan klaim bila menemukan yang cocok."
            >
                <x-button :href="route('items.index')" icon="search">Cari Barang</x-button>
            </x-empty-state>
        @else
            <div class="space-y-4">
                @foreach ($claims as $claim)
                    <div class="card p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                                @if ($claim->item)
                                    <x-item-photo :item="$claim->item" />
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-claim-status-badge :status="$claim->status" />
                                    @if ($claim->item)
                                        <x-status-badge :status="$claim->item->status" />
                                    @endif
                                </div>

                                <p class="mt-1.5 font-semibold text-slate-900">{{ $claim->item?->title }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ $claim->item?->location?->name }}
                                    &middot; Diajukan {{ $claim->created_at->translatedFormat('d M Y, H:i') }}
                                </p>

                                @if ($claim->status === \App\Enums\ClaimStatus::Rejected)
                                    <p class="mt-2 text-sm text-rose-600">
                                        {{ $claim->rejection_reason ?? 'Verifikasi gagal.' }}
                                        ({{ $claim->attempt_count }} percobaan)
                                    </p>
                                @elseif ($claim->status === \App\Enums\ClaimStatus::Submitted)
                                    <p class="mt-2 text-sm text-slate-500">
                                        Sisa percobaan: {{ max(0, $maxAttempts - $claim->attempt_count) }} dari {{ $maxAttempts }}.
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                                @if ($claim->status === \App\Enums\ClaimStatus::Approved && $claim->pickupCode)
                                    <x-button :href="route('claims.pickup', $claim)" size="sm" icon="ticket">
                                        Lihat kode pengambilan
                                    </x-button>
                                @endif

                                @if ($claim->item && in_array($claim->status, [\App\Enums\ClaimStatus::Submitted, \App\Enums\ClaimStatus::Cancelled], true) && $claim->item->status->acceptsClaims() && $claim->attempt_count < $maxAttempts)
                                    <x-button :href="route('claims.create', $claim->item)" variant="secondary" size="sm" icon="hand-raised">
                                        Coba lagi
                                    </x-button>
                                @endif

                                @if ($claim->status->isActive())
                                    <form method="POST" action="{{ route('claims.cancel', $claim) }}" data-confirm="Batalkan klaim ini? Barang akan kembali tersedia untuk orang lain.">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="ghost" size="sm">Batalkan klaim</x-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">{{ $claims->links() }}</div>
        @endif
    </div>
@endsection
