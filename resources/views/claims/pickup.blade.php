@extends('layouts.app')

@section('title', 'Kode Pengambilan')

@section('content')
    <div class="mx-auto w-full max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
        @php
            $status = $pickupCode?->effectiveStatus();
        @endphp

        @if ($pickupCode && $status === \App\Enums\PickupCodeStatus::Active)
            <div class="mb-6 flex items-center gap-3 rounded-2xl bg-emerald-50 p-5 ring-1 ring-emerald-200 ring-inset">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-600 text-white">
                    <x-icon name="check" class="h-6 w-6" />
                </span>
                <div>
                    <p class="font-semibold text-emerald-900">Verifikasi Berhasil</p>
                    <p class="text-sm text-emerald-800">Barang cocok dengan informasi yang kamu berikan.</p>
                </div>
            </div>
        @endif

        <div class="card overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                <h1 class="text-lg font-bold text-slate-900">Pengambilan Barang</h1>
                <p class="text-sm text-slate-500">Tunjukkan kode ini kepada petugas keamanan.</p>
            </div>

            <div class="space-y-6 p-6">
                @if ($pickupCode)
                    <div class="text-center">
                        <p class="text-sm font-semibold tracking-wide text-slate-500 uppercase">Kode Pengambilan</p>
                        @if (! empty($qrUri) && $status === \App\Enums\PickupCodeStatus::Active)
                            <img src="{{ $qrUri }}" alt="QR kode pengambilan {{ $item->title }}" width="240" height="240" class="mx-auto mt-4 h-48 w-48 rounded-xl bg-white p-2 ring-1 ring-slate-200" loading="eager" decoding="async">
                            <p class="mt-2 text-xs text-slate-500">Satpam scan QR ini — tidak perlu mengetik. Jangan kirim screenshot ke orang lain.</p>
                        @endif
                        <p id="pickup-code" class="mt-2 select-all rounded-2xl border-2 border-dashed border-brand-300 bg-brand-50 px-3 py-6 font-mono text-2xl font-bold tracking-widest text-brand-800 sm:text-4xl">
                            {{ $pickupCode->plainCode() ?? str_repeat('•', 9) }}
                        </p>
                        @if ($status === \App\Enums\PickupCodeStatus::Active)
                            <button type="button" data-copy-code="pickup-code" class="hidden mt-3 rounded-lg border border-brand-300 px-4 py-2 text-sm font-semibold text-brand-700 focus-visible:ring-2 focus-visible:ring-brand-500">Salin kode</button>
                            <p id="copy-code-status" role="status" class="mt-2 text-sm text-slate-600"></p>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                            <x-pickup-code-status-badge :status="$status" />
                            @if ($status === \App\Enums\PickupCodeStatus::Active)
                                <span class="text-sm text-slate-500">
                                    Berlaku sampai {{ $pickupCode->expires_at->translatedFormat('d F Y, H:i') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($status === \App\Enums\PickupCodeStatus::Used)
                        <x-alert type="success" title="Barang sudah diambil" :dismissible="false">
                            Kode ini sudah digunakan pada
                            {{ $pickupCode->used_at?->translatedFormat('d F Y, H:i') }}.
                        </x-alert>
                    @elseif ($status === \App\Enums\PickupCodeStatus::Expired)
                        <x-alert type="warning" title="Kode kedaluwarsa" :dismissible="false">
                            Kode ini sudah melewati masa berlakunya. Hubungi admin untuk menerbitkan kode baru.
                        </x-alert>
                    @elseif ($status === \App\Enums\PickupCodeStatus::Cancelled)
                        <x-alert type="error" title="Kode dibatalkan" :dismissible="false">
                            Kode ini sudah dibatalkan dan tidak dapat digunakan.
                        </x-alert>
                    @endif
                @else
                    <x-alert type="warning" title="Kode belum tersedia" :dismissible="false">
                        Klaim ini belum memiliki kode pengambilan aktif.
                    </x-alert>
                @endif

                <dl class="space-y-3 border-t border-slate-200 pt-6 text-sm">
                    <div class="flex items-start gap-3">
                        <dt class="flex w-32 shrink-0 items-center gap-2 text-slate-500">
                            <x-icon name="package" class="h-4 w-4" /> Barang
                        </dt>
                        <dd class="font-medium text-slate-800">{{ $item->title }}</dd>
                    </div>

                    <div class="flex items-start gap-3">
                        <dt class="flex w-32 shrink-0 items-center gap-2 text-slate-500">
                            <x-icon name="shield" class="h-4 w-4" /> Lokasi
                        </dt>
                        <dd class="font-medium text-slate-800">{{ $item->depositLocation?->name ?? 'Pos satpam' }}</dd>
                    </div>

                    <div class="flex items-start gap-3">
                        <dt class="flex w-32 shrink-0 items-center gap-2 text-slate-500">
                            <x-icon name="clock" class="h-4 w-4" /> Status
                        </dt>
                        <dd><x-status-badge :status="$item->status" /></dd>
                    </div>
                </dl>

                <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200 ring-inset">
                    <p class="flex items-start gap-2">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                        Kode ini hanya dapat digunakan satu kali. Petugas keamanan akan memverifikasi kode
                        dan identitasmu sebelum menyerahkan barang.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <x-button :href="route('dashboard.claims')" variant="secondary" size="lg">Lihat Klaim Saya</x-button>
                    <x-button :href="route('items.index')" variant="ghost" size="lg">Cari barang lain</x-button>
                </div>
            </div>
        </div>
    </div>
@endsection
