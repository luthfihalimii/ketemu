@extends('layouts.app')

@section('title', 'Data Pengguna')

@section('content')
    <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Data Pengguna"
            description="Data pribadi hanya dapat diakses oleh admin untuk keperluan administrasi."
            icon="user"
        >
            <x-button :href="route('admin.dashboard')" variant="secondary" size="sm" icon="layout-dashboard">Dasbor</x-button>
            <x-button :href="route('admin.items.index')" variant="secondary" size="sm" icon="shield">Moderasi</x-button>
        </x-page-header>

        <form method="GET" action="{{ route('admin.users.index') }}" class="card mb-6 flex flex-wrap gap-4 p-5">
            <x-input name="q" label="Cari pengguna" placeholder="Nama atau email" :value="request('q')" class="flex-1" />
            <div class="flex items-end gap-3">
                <x-button type="submit" icon="search">Cari</x-button>
                <x-button :href="route('admin.users.index')" variant="secondary">Hapus filter</x-button>
            </div>
        </form>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        <tr>
                            <th scope="col" class="px-4 py-3">Nama</th>
                            <th scope="col" class="px-4 py-3">Email</th>
                            <th scope="col" class="px-4 py-3">Peran</th>
                            <th scope="col" class="px-4 py-3">Laporan</th>
                            <th scope="col" class="px-4 py-3">Klaim</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Terdaftar</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700">
                                        {{ $user->role->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $user->items_count }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $user->claims_count }}</td>
                                <td class="px-4 py-3">
                                    @if ($user->banned_at)
                                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Dinonaktifkan</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-500">
                                    {{ $user->created_at?->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}" class="mb-1 inline">
                                        @csrf
                                        @method('PUT')
                                        <select name="role" onchange="this.form.submit()" class="field !w-auto !py-1 text-xs" aria-label="Ubah peran {{ $user->email }}">
                                            @foreach (['student' => 'Mahasiswa', 'guard' => 'Satpam', 'admin' => 'Admin'] as $value => $label)
                                                <option value="{{ $value }}" @selected($user->role->value === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    @if ($user->banned_at)
                                        <form method="POST" action="{{ route('admin.users.unban', $user) }}" class="inline" data-confirm="Pulihkan akun {{ $user->email }}?">
                                            @csrf
                                            <x-button type="submit" variant="secondary" size="sm">Pulihkan</x-button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.ban', $user) }}" class="inline" data-confirm="Nonaktifkan akun {{ $user->email }}? Akun tidak bisa masuk lagi.">
                                            @csrf
                                            <x-button type="submit" variant="secondary" size="sm">Nonaktifkan</x-button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $users->links() }}</div>
    </div>
@endsection
