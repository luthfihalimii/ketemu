@extends('layouts.app')

@section('title', 'Moderasi Laporan')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Moderasi Laporan"
            description="Tinjau laporan mencurigakan, nonaktifkan laporan palsu, dan pantau aktivitas sistem."
            icon="shield"
        >
            <x-button :href="route('admin.items.index', ['only' => 'flagged'])" variant="{{ ($filters['only'] ?? null) === 'flagged' ? 'primary' : 'secondary' }}" size="sm" icon="alert-triangle">
                Ditandai ({{ $flagCount }})
            </x-button>
            <x-button :href="route('admin.items.index', ['only' => 'deposit_overdue'])" variant="{{ ($filters['only'] ?? null) === 'deposit_overdue' ? 'primary' : 'secondary' }}" size="sm" icon="clock">
                Belum Dititipkan ({{ $depositFollowUpCount }})
            </x-button>
            <x-button :href="route('admin.claims.index')" variant="secondary" size="sm" icon="hand-raised">Klaim</x-button>
            <x-button :href="route('admin.audit.index')" variant="secondary" size="sm" icon="list">Log Aktivitas</x-button>
            <x-button :href="route('admin.categories.index')" variant="secondary" size="sm" icon="tag">Kategori</x-button>
            <x-button :href="route('admin.users.index')" variant="secondary" size="sm" icon="user">Pengguna</x-button>
        </x-page-header>

        <form method="GET" action="{{ route('admin.items.index') }}" class="card mb-6 grid gap-4 p-5 sm:grid-cols-3">
            <x-select name="status" label="Status" placeholder="Semua status" :selected="$filters['status'] ?? null" :options="$statusOptions" />
            <x-select name="only" label="Tampilkan" placeholder="Semua laporan" :selected="$filters['only'] ?? null" :options="['flagged' => 'Hanya yang ditandai', 'deposit_overdue' => 'Belum dikonfirmasi dititipkan']" />
            <div class="flex items-end gap-3">
                <x-button type="submit" icon="search">Terapkan</x-button>
                <x-button :href="route('admin.items.index')" variant="secondary">Hapus filter</x-button>
            </div>
        </form>

        @if ($items->isEmpty())
            <x-empty-state icon="inbox" title="Tidak ada laporan" description="Belum ada laporan yang perlu ditinjau." />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th scope="col" class="px-4 py-3">Barang</th>
                                <th scope="col" class="px-4 py-3">Pelapor</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Ditandai</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Tindakan</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($items as $item)
                                <tr @class(['bg-amber-50/60' => $item->isFlagged(), 'bg-sky-50/60' => ! $item->isFlagged() && $item->isDepositOverdue()])>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-800">{{ $item->title }}</p>
                                        <p class="font-mono text-xs text-slate-600">{{ $item->code }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-slate-700">{{ $item->user?->name }}</p>
                                        <p class="text-xs text-slate-600">{{ $item->user?->email }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-status-badge :status="$item->status" />
                                        @if ($item->isDepositOverdue())
                                            <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-sky-700">
                                                <x-icon name="clock" class="h-3.5 w-3.5" />
                                                Belum dikonfirmasi
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($item->isFlagged())
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700">
                                                <x-icon name="alert-triangle" class="h-4 w-4" />
                                                {{ $item->flag_reason }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-600">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <x-button :href="route('admin.items.show', $item)" variant="secondary" size="sm">Tinjau</x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">{{ $items->links() }}</div>
        @endif
    </div>
@endsection
