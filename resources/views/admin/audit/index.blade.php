@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Log Aktivitas"
            description="Catatan aktivitas penting: login, laporan, klaim, verifikasi, kode pengambilan, dan moderasi."
            icon="list"
        >
            <x-button :href="route('admin.items.index')" variant="secondary" size="sm" icon="shield">Laporan</x-button>
            <x-button :href="route('admin.claims.index')" variant="secondary" size="sm" icon="hand-raised">Klaim</x-button>
        </x-page-header>

        <form method="GET" action="{{ route('admin.audit.index') }}" class="card mb-6 grid gap-4 p-5 sm:grid-cols-3">
            <x-input name="event" label="Aktivitas" placeholder="Contoh: claim." :value="$filters['event'] ?? null" />
            <x-select name="user" label="Pengguna" placeholder="Semua pengguna" :selected="$filters['user'] ?? null" :options="$users->pluck('name', 'id')->all()" />
            <div class="flex items-end gap-3">
                <x-button type="submit" icon="search">Filter</x-button>
                <x-button :href="route('admin.audit.index')" variant="secondary">Hapus filter</x-button>
            </div>
        </form>

        @if ($logs->isEmpty())
            <x-empty-state icon="list" title="Belum ada aktivitas" description="Audit log akan terisi seiring penggunaan sistem." />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th scope="col" class="px-4 py-3">Waktu</th>
                                <th scope="col" class="px-4 py-3">Aktivitas</th>
                                <th scope="col" class="px-4 py-3">Deskripsi</th>
                                <th scope="col" class="px-4 py-3">Pelaku</th>
                                <th scope="col" class="px-4 py-3">Objek</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-500">
                                        {{ $log->created_at?->translatedFormat('d M Y, H:i:s') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-xs font-medium text-slate-700">
                                            {{ $log->event }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $log->description }}
                                        @if ($log->properties)
                                            <p class="mt-1 font-mono text-xs text-slate-600">
                                                {{ json_encode($log->properties) }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($log->user)
                                            <p class="text-slate-700">{{ $log->user->name }}</p>
                                            <p class="text-xs text-slate-600">{{ $log->user->email }}</p>
                                        @else
                                            <span class="text-xs text-slate-600">sistem</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500">
                                        @if ($log->auditable_type)
                                            {{ class_basename($log->auditable_type) }}#{{ $log->auditable_id }}
                                        @else
                                            &mdash;
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
