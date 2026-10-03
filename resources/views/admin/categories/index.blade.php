@extends('layouts.app')

@section('title', 'Kelola Kategori')

@section('content')
    <div class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Kelola Kategori"
            description="Nonaktifkan kategori agar tidak lagi muncul pada formulir laporan."
            icon="tag"
        >
            <x-button :href="route('admin.dashboard')" variant="secondary" size="sm" icon="layout-dashboard">Dasbor</x-button>
            <x-button :href="route('admin.items.index')" variant="secondary" size="sm" icon="shield">Moderasi</x-button>
        </x-page-header>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="card mb-6 grid gap-4 p-5 sm:grid-cols-4">
            @csrf
            <x-input name="name" label="Nama kategori" required placeholder="Contoh: Dompet" class="sm:col-span-2" />
            <x-input name="icon" label="Ikon (opsional)" placeholder="Contoh: wallet" />
            <div class="flex items-end"><x-button type="submit" icon="plus">Tambah</x-button></div>
        </form>

        <div class="card overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th scope="col" class="px-4 py-3">Kategori</th>
                        <th scope="col" class="px-4 py-3">Jumlah laporan</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Tindakan</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($categories as $category)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $category->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $category->items_count }}</td>
                            <td class="px-4 py-3">
                                @if ($category->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.categories.toggle', $category) }}" data-confirm="Ubah status kategori {{ $category->name }}? Kategori nonaktif tidak tersedia untuk laporan baru.">
                                    @csrf
                                    <x-button type="submit" variant="secondary" size="sm">
                                        {{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </x-button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
