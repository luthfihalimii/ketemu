@extends('layouts.app')

@section('title', 'Pengaturan Telegram')

@section('content')
    <div class="mx-auto w-full max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Notifikasi Telegram"
            description="Terima kabar klaim dan laporanmu langsung lewat Telegram, tanpa perlu membuka web."
            icon="megaphone"
        />

        @if ($chatId)
            <x-alert type="success" title="Telegram terhubung" :dismissible="false" class="mb-6">
                <p>Notifikasi akan dikirim ke akun Telegram yang sudah kamu hubungkan.</p>
                @if ($linkedAt)
                    <p class="mt-1">Dihubungkan pada {{ $linkedAt->translatedFormat('d F Y, H:i') }}.</p>
                @endif
            </x-alert>

            <form method="POST" action="{{ route('telegram.unlink') }}" class="card p-6" data-confirm="Putuskan tautan Telegram? Kamu tidak akan menerima notifikasi di Telegram lagi.">
                @csrf
                @method('DELETE')
                <p class="text-sm text-slate-600">
                    Memutus tautan tidak menghapus akunmu. Notifikasi tetap muncul di lonceng web.
                </p>
                <x-button type="submit" variant="secondary" icon="x" class="mt-4">Putuskan Telegram</x-button>
            </form>
        @elseif (! $configured)
            <x-alert type="warning" title="Telegram belum aktif" :dismissible="false">
                <p>
                    Administrator belum mengatur bot Telegram untuk kampus ini. Sementara itu, notifikasi
                    tetap muncul di <a href="{{ route('notifications.index') }}" class="font-medium underline">lonceng notifikasi</a>.
                </p>
            </x-alert>
        @elseif ($linkUrl)
            <div class="card space-y-5 p-6">
                <div>
                    <h2 class="font-semibold text-slate-900">Cara menghubungkan</h2>
                    <ol class="mt-3 space-y-2 text-sm text-slate-600">
                        <li class="flex gap-2"><span class="font-semibold text-slate-800">1.</span> Tekan tombol di bawah, Telegram akan terbuka.</li>
                        <li class="flex gap-2"><span class="font-semibold text-slate-800">2.</span> Tekan <span class="font-mono font-medium">Start</span> pada bot.</li>
                        <li class="flex gap-2"><span class="font-semibold text-slate-800">3.</span> Bot akan membalas bahwa akunmu sudah terhubung.</li>
                    </ol>
                </div>

                <x-button :href="$linkUrl" target="_blank" rel="noopener" size="lg" icon="megaphone" block>
                    Hubungkan Telegram
                </x-button>

                <div class="border-t border-slate-200 pt-5">
                    <p class="text-xs font-medium text-slate-500">Atau buka tautan ini secara manual:</p>
                    <code class="mt-1.5 block overflow-x-auto rounded-lg bg-slate-100 p-3 font-mono text-xs text-slate-700">{{ $linkUrl }}</code>
                    <p class="mt-2 text-xs text-slate-400">
                        Tautan hanya berlaku {{ \App\Services\TelegramService::TOKEN_TTL_MINUTES }} menit dan hanya bisa dipakai sekali.
                        Buka ulang halaman ini untuk mendapat tautan baru.
                    </p>
                </div>
            </div>
        @endif
    </div>
@endsection
