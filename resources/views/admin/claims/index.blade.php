@extends('layouts.app')

@section('title', 'Moderasi Klaim')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Moderasi Klaim"
            description="Tinjau klaim yang perlu tindakan lanjutan dan terbitkan ulang kode yang kedaluwarsa."
            icon="hand-raised"
        >
            <x-button :href="route('admin.items.index')" variant="secondary" size="sm" icon="shield">Laporan</x-button>
            <x-button :href="route('admin.audit.index')" variant="secondary" size="sm" icon="list">Audit Log</x-button>
        </x-page-header>

        @if (session('reissued_code'))
            <x-alert type="success" title="Kode pengambilan baru" class="mb-6">
                <p class="font-mono text-lg font-bold tracking-[0.3em]">{{ session('reissued_code') }}</p>
            </x-alert>
        @endif

        <form method="GET" action="{{ route('admin.claims.index') }}" class="card mb-6 grid gap-4 p-5 sm:grid-cols-3">
            <x-select name="status" label="Status klaim" placeholder="Semua status" :selected="$filters['status'] ?? null" :options="$statusOptions->all()" />
            <div class="flex items-end gap-3">
                <x-button type="submit" icon="search">Terapkan</x-button>
                <x-button :href="route('admin.claims.index')" variant="secondary">Reset</x-button>
            </div>
        </form>

        @if ($claims->isEmpty())
            <x-empty-state icon="inbox" title="Tidak ada klaim" description="Belum ada klaim yang cocok dengan filter ini." />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3">Pengklaim</th>
                                <th class="px-4 py-3">Barang</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Kode</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($claims as $claim)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-800">{{ $claim->user?->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $claim->user?->email }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-slate-700">{{ $claim->item?->title }}</p>
                                        <p class="font-mono text-xs text-slate-400">{{ $claim->item?->code }}</p>
                                    </td>
                                    <td class="px-4 py-3"><x-claim-status-badge :status="$claim->status" /></td>
                                    <td class="px-4 py-3">
                                        @if ($claim->pickupCode)
                                            <x-pickup-code-status-badge :status="$claim->pickupCode->effectiveStatus()" />
                                        @else
                                            <span class="text-xs text-slate-400">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            @if ($claim->status->isActive())
                                                <form method="POST" action="{{ route('admin.claims.reject', $claim) }}" class="flex items-center gap-2" data-confirm="Tolak klaim ini?">
                                                    @csrf
                                                    <input type="text" name="reason" placeholder="Alasan" required class="field w-36 px-3 py-1.5 text-sm">
                                                    <x-button type="submit" variant="danger" size="sm">Tolak</x-button>
                                                </form>
                                            @endif

                                            @if ($claim->status === \App\Enums\ClaimStatus::Approved)
                                                <form method="POST" action="{{ route('admin.claims.reissue', $claim) }}">
                                                    @csrf
                                                    <x-button type="submit" variant="secondary" size="sm" icon="ticket">Kode baru</x-button>
                                                </form>
                                            @endif

                                            @if ($claim->item)
                                                <x-button :href="route('admin.items.show', $claim->item)" variant="ghost" size="sm">Tinjau</x-button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">{{ $claims->links() }}</div>
        @endif
    </div>
@endsection
