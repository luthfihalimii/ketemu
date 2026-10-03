@extends('layouts.app')

@section('title', 'Struk Serah Terima')

@section('content')
    <div class="mx-auto w-full max-w-xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header title="Struk Serah Terima" description="Arsip pos satpam. Nomor identitas hanya tampil tersamar." icon="receipt" />

        <div class="card space-y-3 p-6 text-sm">
            <p><span class="text-slate-500">Barang:</span> <span class="font-semibold">{{ $item->title }}</span> <span class="font-mono text-xs">({{ $item->code }})</span></p>
            <p><span class="text-slate-500">Kode:</span> <span class="font-mono font-bold">{{ $pickupCode->code_hint }} ({{ $pickupCode->status->label() }})</span></p>
            <p><span class="text-slate-500">Penerima:</span> <span class="font-medium">{{ $pickupCode->recipient_name }}</span></p>
            <p><span class="text-slate-500">ID tersamar:</span> <span class="font-mono">{{ \App\Services\PickupService::maskIdNumber($pickupCode->recipient_id_number ?? '----') }}</span></p>
            <p><span class="text-slate-500">Diserahkan oleh:</span> {{ $pickupCode->verifier?->name ?? '-' }} pada {{ $pickupCode->verified_at?->translatedFormat('d F Y, H:i') ?? '-' }}</p>
            <p class="text-xs text-slate-500">ID utuh tersimpan terenkripsi di database dan dihapus otomatis setelah masa retensi. Struk ini hanya menyimpan bentuk tersamar.</p>
        </div>

        <div class="mt-4 flex gap-3">
            <x-button onclick="window.print()" icon="printer">Cetak</x-button>
            <x-button :href="route('guard.pickup.create')" variant="secondary">Kembali</x-button>
        </div>
    </div>
@endsection
