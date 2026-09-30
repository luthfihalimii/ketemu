@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
    <div class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Notifikasi"
            description="Perkembangan klaim dan laporanmu."
            icon="bell"
        >
            @if ($notifications->contains(fn ($n) => $n->read_at === null))
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-button type="submit" variant="secondary" size="sm" icon="check">Tandai semua dibaca</x-button>
                </form>
            @endif
        </x-page-header>

        @unless (auth()->user()->hasLinkedTelegram())
            <x-alert type="info" title="Ingin diberi tahu tanpa membuka web?" :dismissible="false" class="mb-6">
                <p>
                    Hubungkan akun Telegrammu supaya notifikasi penting dikirim langsung ke sana.
                    <a href="{{ route('telegram.show') }}" class="font-semibold underline">Hubungkan sekarang</a>.
                </p>
            </x-alert>
        @endunless

        @if ($notifications->isEmpty())
            <x-empty-state
                icon="bell"
                title="Belum ada notifikasi"
                description="Kamu akan diberi tahu di sini saat klaim diverifikasi, kode terbit, atau laporanmu perlu ditindaklanjuti."
            />
        @else
            @php
                $styles = [
                    'success' => ['bg-emerald-50', 'text-emerald-700', 'check-circle'],
                    'error' => ['bg-rose-50', 'text-rose-700', 'x-circle'],
                    'warning' => ['bg-amber-50', 'text-amber-700', 'alert-triangle'],
                    'info' => ['bg-sky-50', 'text-sky-700', 'info'],
                ];
            @endphp

            <div class="space-y-3">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        [$bg, $fg, $icon] = $styles[$data['level'] ?? 'info'] ?? $styles['info'];
                        $isUnread = $notification->read_at === null;
                    @endphp

                    <a
                        href="{{ route('notifications.show', $notification->id) }}"
                        @class([
                            'card flex items-start gap-4 p-4 transition hover:shadow-md',
                            'ring-2 ring-brand-200' => $isUnread,
                        ])
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $bg }} {{ $fg }}">
                            <x-icon :name="$icon" class="h-5 w-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-slate-900">{{ $data['title'] ?? 'Notifikasi' }}</p>
                                @if ($isUnread)
                                    <span class="rounded-full bg-brand-600 px-2 py-0.5 text-xs font-bold text-white">Baru</span>
                                @endif
                            </div>

                            <p class="mt-1 text-sm text-slate-600">{{ $data['body'] ?? '' }}</p>

                            <p class="mt-1.5 text-xs text-slate-400">
                                {{ $notification->created_at->translatedFormat('d F Y, H:i') }}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
