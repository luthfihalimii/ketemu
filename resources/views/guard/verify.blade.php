@extends('layouts.app')

@section('title', 'Verifikasi Kode Pengambilan')

@section('content')
    <div class="mx-auto w-full max-w-xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Verifikasi Kode Pengambilan"
            description="Masukkan kode dan identitas penerima, lalu serahkan barangnya."
            icon="shield"
        />

        @if ($lastPickup)
            <x-alert type="success" title="Barang telah diserahkan" class="mb-6">
                <p>
                    <span class="font-semibold">{{ $lastPickup->title }}</span>
                    <span class="font-mono text-xs">({{ $lastPickup->code }})</span>
                </p>
                @if ($lastRecipient)
                    <p class="mt-1">Diterima oleh: <span class="font-medium">{{ $lastRecipient }}</span></p>
                @endif
                <p class="mt-1">Selesai pada {{ now()->translatedFormat('d F Y, H:i') }}.</p>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('guard.pickup.store') }}" class="card space-y-6 p-6">
            @csrf

            <div>
                <label for="code" class="label">Kode pengambilan</label>
                <input
                    type="text"
                    name="code"
                    id="code"
                    value="{{ old('code') }}"
                    data-pickup-code
                    inputmode="text"
                    autocomplete="off"
                    autocapitalize="characters"
                    spellcheck="false"
                    placeholder="XXXX-XXXX"
                    maxlength="9"
                    required
                    autofocus
                    class="field text-center font-mono text-2xl tracking-[0.3em] uppercase"
                >
                <div class="mt-3">
                    <button type="button" data-qr-scan="code" class="hidden rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                        Scan QR mahasiswa
                    </button>
                    <p data-qr-scan-status role="status" class="mt-1 text-xs text-slate-500"></p>
                    <video data-qr-scan-video playsinline muted class="mt-2 hidden max-h-64 w-full rounded-xl bg-black"></video>
                </div>
                @error('code')
                    <p class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-rose-600">
                        <x-icon name="alert-triangle" class="h-4 w-4" /> {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="space-y-5 border-t border-slate-200 pt-6">
                <p class="flex items-start gap-2 text-sm text-slate-600">
                    <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                    Kode hanya membuktikan hak mengambil, bukan siapa yang membawa kode. Catat identitas
                    yang kamu periksa langsung sebelum menyerahkan barang.
                </p>

                <x-input
                    name="recipient_id_number"
                    label="Nomor identitas (KTM/KTP)"
                    placeholder="Contoh: 2141720001"
                    hint="Dicatat sebagai bukti penerima barang."
                    required
                />

                <x-input
                    name="recipient_name"
                    label="Nama penerima"
                    placeholder="Sesuai identitas yang ditunjukkan"
                    required
                />
            </div>

            <x-button type="submit" size="lg" icon="check" block>
                Verifikasi &amp; Serahkan
            </x-button>

            <ol class="space-y-2 border-t border-slate-200 pt-6 text-sm text-slate-600">
                <li class="flex gap-2"><span class="font-semibold text-slate-800">1.</span> Cocokkan kode dengan barang yang disimpan.</li>
                <li class="flex gap-2"><span class="font-semibold text-slate-800">2.</span> Minta mahasiswa menunjukkan identitas (KTM) dan periksa fotonya.</li>
                <li class="flex gap-2"><span class="font-semibold text-slate-800">3.</span> Isi nomor identitas dan nama penerima di atas.</li>
                <li class="flex gap-2"><span class="font-semibold text-slate-800">4.</span> Tekan tombol setelah barang benar-benar diserahkan.</li>
            </ol>
        </form>

        <p class="mt-4 text-center text-xs text-slate-600">
            Setiap percobaan verifikasi dan identitas penerima dicatat untuk keperluan audit.
        </p>

        @if ($pendingDeposits->isNotEmpty())
            <div class="card mt-6 p-6">
                <h2 class="text-base font-semibold text-slate-900">Menunggu konfirmasi fisik ({{ $pendingDeposits->count() }})</h2>
                <p class="mt-1 text-sm text-slate-600">Pastikan barang fisik sudah ada di pos sebelum menekan konfirmasi.</p>
                <ul class="mt-4 space-y-3">
                    @foreach ($pendingDeposits as $pending)
                        <li class="flex flex-wrap items-center gap-2 rounded-xl bg-slate-50 p-3 text-sm ring-1 ring-slate-200 ring-inset">
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-slate-900">{{ $pending->title }}</span>
                                <span class="block font-mono text-xs text-slate-500">{{ $pending->code }} · {{ $pending->depositLocation?->name ?? 'Pos belum jelas' }}</span>
                            </span>
                            <form method="POST" action="{{ route('guard.deposit.confirm', $pending) }}">
                                @csrf
                                <x-button type="submit" size="sm" variant="success" icon="check">Konfirmasi fisik</x-button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
