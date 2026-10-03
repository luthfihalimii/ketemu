@extends('layouts.app')

@section('title', 'Kelola Lokasi')

@section('content')
    <div class="mx-auto w-full max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header title="Kelola Lokasi" description="Kampus untuk titik hilang/temu, pos satpam untuk penitipan." icon="map-pin">
            <x-button :href="route('admin.items.index')" variant="secondary" size="sm" icon="shield">Moderasi</x-button>
        </x-page-header>

        <form method="POST" action="{{ route('admin.locations.store') }}" class="card mb-6 grid gap-4 p-5 sm:grid-cols-4">
            @csrf
            <x-input name="name" label="Nama lokasi" required placeholder="Contoh: Gedung D4" class="sm:col-span-2" />
            <x-select name="type" label="Tipe" required :options="['campus' => 'Kampus', 'security_post' => 'Pos satpam']" />
            <div class="flex items-end"><x-button type="submit" icon="plus">Tambah</x-button></div>
        </form>

        <div class="card overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase"><tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Tipe</th><th class="px-4 py-3">Dipakai</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($locations as $location)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $location->name }}</td>
                            <td class="px-4 py-3">{{ $location->type === 'security_post' ? 'Pos satpam' : 'Kampus' }}</td>
                            <td class="px-4 py-3">{{ $location->items_count + $location->deposits_count }}</td>
                            <td class="px-4 py-3">{{ $location->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.locations.toggle', $location) }}" class="inline">
                                    @csrf
                                    <x-button type="submit" variant="secondary" size="sm">{{ $location->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</x-button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
