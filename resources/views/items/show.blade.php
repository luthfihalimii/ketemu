@extends('layouts.app')

@section('title', $item->title)
@section('meta_description', $item->title.' — '.$item->status->label().'. Informasi barang dan lokasi penitipan di KETEMU PENS.')
@section('meta_extra')
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="{{ route('items.show', $item) }}">
@endsection

@section('content')
    <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="mb-5 text-sm text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('items.index') }}" class="font-medium text-brand-700 hover:underline">Cari Barang</a>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-600">{{ $item->title }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-5">
            <div class="lg:col-span-3">
                <div class="card overflow-hidden">
                    <div class="aspect-16/10 bg-slate-100">
                        <x-item-photo :item="$item" :contain="true" :lazy="false" />
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="flex flex-wrap items-center gap-2">
                    <x-status-badge :status="$item->status" />
                    @if ($item->category)
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                            {{ $item->category->name }}
                        </span>
                    @endif
                </div>

                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $item->title }}</h1>

                <p class="mt-1 break-all font-mono text-xs text-slate-600">Kode laporan: {{ $item->code }}</p>

                @if ($item->isFoundReport() && $item->deposit_requested_at && ! $item->isDepositConfirmed())
                    <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-600/20 ring-inset">
                        Menunggu verifikasi fisik satpam
                    </p>
                @endif

                @if ($item->isFoundReport() && $item->isDepositConfirmed())
                    <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-600/20 ring-inset">
                        Fisik terkonfirmasi satpam
                    </p>
                @endif

                <dl class="mt-6 space-y-3 text-sm">
                    <div class="flex items-start gap-3">
                        <dt class="flex w-36 shrink-0 items-center gap-2 text-slate-500">
                            <x-icon name="map-pin" class="h-4 w-4" /> {{ $item->isLostReport() ? 'Perkiraan lokasi kehilangan' : 'Lokasi ditemukan' }}
                        </dt>
                        <dd class="font-medium text-slate-800">
                            {{ $item->location?->name ?? 'Tidak dicatat' }}
                            @if ($item->location_detail)
                                <span class="block text-sm font-normal text-slate-500">{{ $item->location_detail }}</span>
                            @endif
                        </dd>
                    </div>

                    <div class="flex items-start gap-3">
                        <dt class="flex w-36 shrink-0 items-center gap-2 text-slate-500">
                            <x-icon name="calendar" class="h-4 w-4" /> Waktu
                        </dt>
                        <dd class="font-medium text-slate-800">
                            {{ $item->occurred_at?->translatedFormat('d F Y, H:i') ?? 'Tidak dicatat' }}
                        </dd>
                    </div>

                    @if ($item->color)
                        <div class="flex items-start gap-3">
                            <dt class="flex w-36 shrink-0 items-center gap-2 text-slate-500">
                                <x-icon name="tag" class="h-4 w-4" /> Warna
                            </dt>
                            <dd class="font-medium text-slate-800">{{ $item->color }}</dd>
                        </div>
                    @endif

                    @if ($item->brand)
                        <div class="flex items-start gap-3">
                            <dt class="flex w-36 shrink-0 items-center gap-2 text-slate-500">
                                <x-icon name="tag" class="h-4 w-4" /> Merek
                            </dt>
                            <dd class="font-medium text-slate-800">{{ $item->brand }}</dd>
                        </div>
                    @endif

                    @if ($item->depositLocation && $item->status->isPubliclyAvailable())
                        <div class="flex items-start gap-3">
                            <dt class="flex w-36 shrink-0 items-center gap-2 text-slate-500">
                                <x-icon name="shield" class="h-4 w-4" /> Dititipkan di
                            </dt>
                            <dd class="font-medium text-slate-800">{{ $item->depositLocation->name }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($item->description)
                    <div class="mt-6">
                        <h2 class="text-sm font-semibold text-slate-700">Deskripsi</h2>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $item->description }}</p>
                    </div>
                @endif

                @if (! empty($canSeePrivate) && $item->private_note)
                    <div class="mt-6 rounded-xl bg-slate-100 p-4 ring-1 ring-slate-200 ring-inset">
                        <h2 class="text-sm font-semibold text-slate-700">Catatan internal (privat)</h2>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $item->private_note }}</p>
                        <p class="mt-1 text-xs text-slate-500">Hanya terlihat oleh pelapor, satpam, admin, atau pengklaim terverifikasi.</p>
                    </div>
                @endif

                <div class="mt-8 border-t border-slate-200 pt-6">
                    @auth
                        @if ($item->user_id === auth()->id())
                            <div class="rounded-xl bg-sky-50 p-4 text-sm text-sky-900 ring-1 ring-sky-200 ring-inset">
                                <p class="font-semibold">Ini laporanmu.</p>
                                @if ($item->status === \App\Enums\ItemStatus::WaitingDeposit)
                                    <p class="mt-1">Konfirmasi setelah barang benar-benar kamu titipkan ke satpam. Satpam akan memverifikasi fisiknya di pos.</p>
                                    <form method="POST" action="{{ route('items.confirm-deposit', $item) }}" class="mt-3">
                                        @csrf
                                        <x-button type="submit" variant="success" size="sm" icon="check">
                                            Barang sudah saya titipkan ke satpam
                                        </x-button>
                                    </form>
                                @elseif ($item->isLostReport() && $item->status === \App\Enums\ItemStatus::Reported)
                                    <p class="mt-1">
                                        @if ($item->matchedItem)
                                            Sudah dicocokkan dengan
                                            <span class="font-semibold">{{ $item->matchedItem->title }}</span>.
                                            Ajukan klaim pada barang tersebut untuk membuktikan kepemilikan.
                                        @else
                                            Cari barang temuan yang cocok, lalu ajukan klaim untuk membuktikan kepemilikan.
                                        @endif
                                    </p>
                                    <x-button :href="route('dashboard.matches', $item)" variant="success" size="sm" icon="search" class="mt-3">
                                        {{ $item->matchedItem ? 'Ubah kecocokan' : 'Cari kecocokan' }}
                                    </x-button>
                                @else
                                    <p class="mt-1">Pantau prosesnya melalui halaman riwayat laporan.</p>
                                @endif
                                <x-button :href="route('dashboard.reports')" variant="secondary" size="sm" class="mt-3">
                                    Lihat laporan saya
                                </x-button>
                                @if (in_array($item->status, [\App\Enums\ItemStatus::WaitingDeposit, \App\Enums\ItemStatus::Reported], true))
                                    <x-button :href="route('items.edit', $item)" variant="secondary" size="sm" icon="pencil" class="mt-3">
                                        Ubah laporan
                                    </x-button>
                                @endif
                            </div>
                        @elseif ($myClaim)
                            <div class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200 ring-inset">
                                <p class="font-semibold">Kamu sudah mengajukan klaim untuk barang ini.</p>
                                <p class="mt-1">Status klaim: {{ $myClaim->status->label() }}.</p>
                                <x-button :href="route('dashboard.claims')" variant="secondary" size="sm" class="mt-3">
                                    Lihat klaim saya
                                </x-button>
                            </div>
                        @elseif ($canClaim)
                            <x-button :href="route('claims.create', $item)" size="lg" icon="hand-raised" block>
                                Ajukan Klaim
                            </x-button>
                            <p class="help">
                                Kamu akan diminta menjawab pertanyaan verifikasi kepemilikan.
                                Jawabannya hanya diketahui pemilik sebenarnya.
                            </p>
                        @elseif ($item->status === \App\Enums\ItemStatus::Claimed)
                            <x-alert type="warning" :dismissible="false">
                                Barang ini sedang dalam proses klaim oleh pengguna lain.
                            </x-alert>
                        @elseif (in_array($item->status, [\App\Enums\ItemStatus::Verified, \App\Enums\ItemStatus::ReadyForPickup], true))
                            <x-alert type="info" :dismissible="false">
                                Barang ini sedang menunggu pengambilan oleh pemilik terverifikasi.
                            </x-alert>
                        @elseif ($item->status === \App\Enums\ItemStatus::Returned)
                            <x-alert type="success" :dismissible="false">
                                Barang ini sudah dikembalikan kepada pemiliknya.
                            </x-alert>
                        @else
                            <x-alert type="info" :dismissible="false">Barang ini belum tersedia untuk klaim. Pantau status laporan melalui KETEMU PENS.</x-alert>
                        @endif
                    @else
                        @if ($item->status->acceptsClaims())
                        <x-button :href="route('login')" size="lg" icon="lock" block>
                            Masuk untuk mengajukan klaim
                        </x-button>
                        <p class="help">Hanya pemilik barang yang dapat mengajukan klaim.</p>
                        @else
                            <x-alert :dismissible="false">Barang ini sedang diproses untuk pemilik terverifikasi dan belum tersedia untuk klaim baru.</x-alert>
                        @endif
                    @endauth
                </div>
            </div>
        </div>

        @if (! empty($timeline) && $timeline->isNotEmpty())
            <div class="card mt-8 p-6">
                <h2 class="text-base font-semibold text-slate-900">Riwayat proses</h2>
                <ol class="mt-4 space-y-3">
                    @foreach ($timeline as $log)
                        <li class="flex flex-wrap gap-x-2 gap-y-1 text-sm">
                            <span class="text-slate-500">{{ $log->created_at?->translatedFormat('d M Y, H:i') }}</span>
                            <span class="font-medium text-slate-800">{{ $log->description }}</span>
                            @if ($log->user)
                                <span class="text-slate-500">oleh {{ $log->user->name }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </div>
@endsection
