@extends('layouts.app')

@section('title', 'Ajukan Klaim')

@section('content')
    <div class="mx-auto w-full max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Apakah ini barangmu?"
            description="Untuk memastikan barang benar-benar milikmu, jawab pertanyaan verifikasi berikut."
            icon="hand-raised"
        />

        <div class="card mb-6 p-5">
            <div class="flex items-start gap-4">
                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-slate-100">
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

                <div class="min-w-0">
                    <h2 class="truncate font-semibold text-slate-900">{{ $item->title }}</h2>
                    <p class="text-sm text-slate-500">
                        {{ $item->category?->name }}
                        @if ($item->location)
                            &middot; {{ $item->location->name }}
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        Dititipkan di
                        <span class="font-medium text-slate-700">{{ $item->depositLocation?->name ?? 'pos satpam' }}</span>
                    </p>
                </div>
            </div>
        </div>

        @if ($remainingAttempts <= 0)
            <x-alert type="error" title="Batas percobaan habis" :dismissible="false">
                Kamu sudah mencoba {{ $maxAttempts }} kali. Bila barang ini benar-benar milikmu,
                silakan hubungi admin kampus untuk verifikasi lanjutan.
            </x-alert>
        @else
            <form method="POST" action="{{ route('claims.store', $item) }}" class="card space-y-6 p-6">
                @csrf

                <div>
                    <p class="label">Pertanyaan verifikasi</p>
                    <p class="rounded-xl bg-slate-50 p-4 text-sm font-medium text-slate-800 ring-1 ring-slate-200 ring-inset">
                        {{ $item->verification_question ?: 'Apa ciri khusus barang ini?' }}
                    </p>
                </div>

                <x-input
                    name="answer"
                    label="Jawabanmu"
                    placeholder="Tulis ciri yang hanya diketahui pemilik"
                    required
                    autofocus
                    hint="Jawaban tidak disimpan dalam bentuk aslinya."
                />

                <p class="flex items-center gap-2 text-sm text-slate-500">
                    <x-icon name="info" class="h-4 w-4" />
                    Sisa percobaan: <span class="font-semibold text-slate-700">{{ $remainingAttempts }} dari {{ $maxAttempts }}</span>
                </p>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                    <x-button type="submit" size="lg" icon="shield">Kirim Verifikasi</x-button>
                    <x-button :href="route('items.show', $item)" variant="secondary" size="lg">Batal</x-button>
                </div>
            </form>
        @endif
    </div>
@endsection
