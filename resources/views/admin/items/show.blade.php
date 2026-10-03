@extends('layouts.app')

@section('title', 'Tinjau Laporan')

@section('content')
    <div class="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="mb-5 text-sm text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('admin.items.index') }}" class="font-medium text-brand-700 hover:underline">Moderasi</a>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-600">{{ $item->title }}</span>
        </nav>

        @if ($item->isFlagged())
            <x-alert type="warning" title="Ditandai mencurigakan" :dismissible="false" class="mb-6">
                {{ $item->flag_reason }}
            </x-alert>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="card p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-status-badge :status="$item->status" />
                        <span class="break-all font-mono text-xs text-slate-600">{{ $item->code }}</span>
                    </div>

                    <h1 class="mt-3 text-2xl font-bold text-slate-900">{{ $item->title }}</h1>
                    <div class="mt-4 aspect-16/10 overflow-hidden rounded-xl"><x-item-photo :item="$item" :contain="true" :src="$item->archived_photo_path ? route('admin.items.photo', $item) : null" /></div>
                    <p class="mt-1 text-sm text-slate-600">{{ $item->description }}</p>

                    <dl class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-slate-500">Kategori</dt><dd class="font-medium text-slate-800">{{ $item->category?->name ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500">Warna / Merek</dt><dd class="font-medium text-slate-800">{{ $item->color ?? '-' }} / {{ $item->brand ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500">Lokasi ditemukan</dt><dd class="font-medium text-slate-800">{{ $item->location?->name ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500">Waktu</dt><dd class="font-medium text-slate-800">{{ $item->occurred_at?->translatedFormat('d F Y, H:i') ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500">Lokasi penitipan</dt><dd class="font-medium text-slate-800">{{ $item->depositLocation?->name ?? '-' }}</dd></div>
                        <div><dt class="text-slate-500">Pelapor</dt><dd class="font-medium text-slate-800">{{ $item->user?->name }} ({{ $item->user?->email }})</dd></div>

                        @if ($item->isLostReport())
                            <div>
                                <dt class="text-slate-500">Jenis laporan</dt>
                                <dd class="font-medium text-slate-800">Barang hilang</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Dicocokkan dengan</dt>
                                <dd class="font-medium text-slate-800">
                                    @if ($item->matchedItem)
                                        <a href="{{ route('admin.items.show', $item->matchedItem) }}" class="text-brand-700 hover:underline">
                                            {{ $item->matchedItem->title }} ({{ $item->matchedItem->code }})
                                        </a>
                                    @else
                                        <span class="text-slate-600">Belum dicocokkan</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if ($item->moderation_note)
                        <div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-700 ring-1 ring-slate-200 ring-inset">
                            <p class="font-semibold">Catatan moderasi</p>
                            <p class="mt-1">{{ $item->moderation_note }}</p>
                            <p class="mt-1 text-xs text-slate-600">
                                Oleh {{ $item->moderator?->name ?? 'admin' }} pada {{ $item->moderated_at?->translatedFormat('d F Y, H:i') }}
                            </p>
                        </div>
                    @endif
                </div>

                <div class="card p-6">
                    <h2 class="font-semibold text-slate-900">Klaim ({{ $item->claims->count() }})</h2>

                    @if ($item->claims->isEmpty())
                        <p class="mt-3 text-sm text-slate-500">Belum ada klaim untuk barang ini.</p>
                    @else
                        <ul class="mt-4 divide-y divide-slate-100">
                            @foreach ($item->claims as $claim)
                                <li class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <x-claim-status-badge :status="$claim->status" />
                                            @if ($claim->pickupCode)
                                                <x-pickup-code-status-badge :status="$claim->pickupCode->effectiveStatus()" />
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm font-medium text-slate-800">{{ $claim->user?->name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $claim->user?->email }} &middot; {{ $claim->attempt_count }} percobaan
                                            &middot; {{ $claim->created_at->translatedFormat('d M Y, H:i') }}
                                        </p>
                                        @if ($claim->rejection_reason)
                                            <p class="mt-1 text-xs text-rose-600">{{ $claim->rejection_reason }}</p>
                                        @endif
                                    </div>

                                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                                        @if ($claim->status->isActive())
                                            <form method="POST" action="{{ route('admin.claims.reject', $claim) }}" class="flex items-center gap-2" data-confirm="Tolak klaim ini?">
                                                @csrf
                                                <input type="text" name="reason" aria-label="Alasan penolakan klaim {{ $claim->id }}" value="{{ old('reason') }}" placeholder="Alasan" required
                                                       class="field w-40 px-3 py-1.5 text-sm">
                                                <x-button type="submit" variant="danger" size="sm">Tolak klaim</x-button>
                                            </form>
                                        @endif

                                        @if ($claim->status === \App\Enums\ClaimStatus::Approved)
                                            <form method="POST" action="{{ route('admin.claims.reissue', $claim) }}" data-confirm="Terbitkan kode baru? Kode pengambilan lama akan dibatalkan.">
                                                @csrf
                                                <x-button type="submit" variant="secondary" size="sm" icon="ticket">Terbitkan kode baru</x-button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                <div class="card p-5">
                    <h2 class="font-semibold text-slate-900">Tindakan moderasi</h2>

                    <div class="mt-4 space-y-4">
                        <form method="POST" action="{{ $item->isFlagged() ? route('admin.items.unflag', $item) : route('admin.items.flag', $item) }}">
                            @csrf
                            @if ($item->isFlagged())
                                @method('DELETE')
                                <x-button type="submit" variant="secondary" size="sm" block icon="check">Hapus tanda</x-button>
                            @else
                                <div class="space-y-2">
                                    <x-input name="reason" id="flag-reason" label="Alasan mencurigakan" required />
                                    @error('reason') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                                    <x-button type="submit" variant="secondary" size="sm" block icon="alert-triangle">Tandai mencurigakan</x-button>
                                </div>
                            @endif
                        </form>

                        @if ($canExpire)
                            <form method="POST" action="{{ route('admin.items.expire', $item) }}" data-confirm="Kedaluwarsakan laporan ini?">
                                @csrf
                                <x-button type="submit" variant="secondary" size="sm" block icon="clock">Kedaluwarsakan</x-button>
                            </form>
                        @endif

                        @if ($canRestore)
                            <form method="POST" action="{{ route('admin.items.restore', $item) }}" data-confirm="Pulihkan laporan yang ditolak ini?">
                                @csrf
                                <x-button type="submit" variant="success" size="sm" block icon="check">Pulihkan laporan</x-button>
                            </form>
                        @endif

                        @if ($canReject)
                            <form method="POST" action="{{ route('admin.items.reject', $item) }}" class="space-y-2" data-confirm="Nonaktifkan laporan ini dan batalkan semua klaim aktif?">
                                @csrf
                                <x-input name="reason" id="reject-reason" label="Alasan penonaktifan" required />
                                @error('reason') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                                <x-button type="submit" variant="danger" size="sm" block icon="x-circle">Nonaktifkan laporan</x-button>
                            </form>
                        @endif

                        @if (! $canReject && ! $canRestore && ! $canExpire)
                            <p class="text-sm text-slate-500">
                                Laporan berstatus {{ $item->status->label() }} tidak memiliki tindakan lanjutan.
                            </p>
                        @endif
                    </div>
                </div>

                <x-alert type="info" :dismissible="false">
                    Setiap tindakan moderasi dicatat pada audit log beserta nilai sebelumnya dan sesudahnya.
                </x-alert>
            </div>
        </div>
    </div>
@endsection
