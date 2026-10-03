@extends('layouts.app')

@section('title', 'Analitik')

@section('content')
    <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-page-header
            title="Analitik"
            description="Tren {{ $report['days'] }} hari ({{ $report['from'] }} – {{ $report['to'] }}). Tanpa tracker eksternal, murni agregasi database."
            icon="chart"
        >
            <x-button :href="route('admin.dashboard')" variant="secondary" size="sm" icon="layout-dashboard">Dasbor</x-button>
            <x-button :href="route('admin.items.export')" variant="secondary" size="sm" icon="download">Export CSV</x-button>
        </x-page-header>

        <form method="GET" action="{{ route('admin.analytics') }}" class="card mb-6 flex flex-wrap items-end gap-4 p-5">
            <x-input name="from" type="date" label="Dari" :value="$filters['from']" required />
            <x-input name="to" type="date" label="Sampai" :value="$filters['to']" required />
            <x-button type="submit" icon="search">Terapkan</x-button>
            <x-button :href="route('admin.analytics', ['range' => 7])" variant="secondary" size="sm">7 hari</x-button>
            <x-button :href="route('admin.analytics', ['range' => 30])" variant="secondary" size="sm">30 hari</x-button>
            <x-button :href="route('admin.analytics', ['range' => 90])" variant="secondary" size="sm">90 hari</x-button>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="card p-5"><p class="text-xs font-semibold uppercase text-slate-500">Temuan</p><p class="mt-1 text-3xl font-bold">{{ $report['totals']['found'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold uppercase text-slate-500">Hilang</p><p class="mt-1 text-3xl font-bold">{{ $report['totals']['lost'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold uppercase text-slate-500">Klaim</p><p class="mt-1 text-3xl font-bold">{{ $report['totals']['claims'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold uppercase text-slate-500">Kembali</p><p class="mt-1 text-3xl font-bold">{{ $report['totals']['returned'] }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold uppercase text-slate-500">Return rate</p><p class="mt-1 text-3xl font-bold">{{ $report['totals']['return_rate'] === null ? '–' : $report['totals']['return_rate'].'%' }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold uppercase text-slate-500">Median titip → kembali</p><p class="mt-1 text-3xl font-bold">{{ $report['sla']['median_days_to_return'] === null ? '–' : $report['sla']['median_days_to_return'].' hari' }}</p></div>
            <div class="card p-5"><p class="text-xs font-semibold uppercase text-slate-500">Rata-rata percobaan klaim</p><p class="mt-1 text-3xl font-bold">{{ $report['sla']['avg_claim_attempts'] === null ? '–' : $report['sla']['avg_claim_attempts'].'x' }}</p></div>
        </div>

        @php($maxDaily = max(1, collect($report['daily'])->max(fn ($d) => max($d['found'], $d['lost'], $d['claims'], $d['returned']))))
        <div class="card mt-6 p-5">
            <h2 class="font-semibold">Tren harian</h2>
            <p class="text-xs text-slate-500">Hijau = temuan, kuning = hilang, biru = klaim, ungu = kembali.</p>
            <div class="mt-4 space-y-1.5">
                @foreach ($report['daily'] as $day)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-24 shrink-0 font-mono text-slate-500">{{ $day['date'] }}</span>
                        <div class="flex h-4 flex-1 gap-0.5 overflow-hidden rounded bg-slate-100">
                            <div class="bg-emerald-500" style="width: {{ $day['found'] / $maxDaily * 100 }}%" title="Temuan: {{ $day['found'] }}"></div>
                            <div class="bg-amber-400" style="width: {{ $day['lost'] / $maxDaily * 100 }}%" title="Hilang: {{ $day['lost'] }}"></div>
                            <div class="bg-sky-500" style="width: {{ $day['claims'] / $maxDaily * 100 }}%" title="Klaim: {{ $day['claims'] }}"></div>
                            <div class="bg-violet-500" style="width: {{ $day['returned'] / $maxDaily * 100 }}%" title="Kembali: {{ $day['returned'] }}"></div>
                        </div>
                        <span class="w-28 shrink-0 text-slate-500">{{ $day['found'] }}/{{ $day['lost'] }}/{{ $day['claims'] }}/{{ $day['returned'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="card p-5">
                <h2 class="font-semibold">Kategori teratas</h2>
                @php($maxCat = max(1, collect($report['top_categories'])->max('total') ?? 1))
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($report['top_categories'] as $row)
                        <li>
                            <div class="flex justify-between"><span>{{ $row['name'] }}</span><span class="font-bold">{{ $row['total'] }}</span></div>
                            <div class="mt-1 h-2 rounded bg-slate-100"><div class="h-2 rounded bg-brand-500" style="width: {{ $row['total'] / $maxCat * 100 }}%"></div></div>
                        </li>
                    @empty
                        <li class="text-slate-500">Belum ada data pada rentang ini.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card p-5">
                <h2 class="font-semibold">Lokasi teratas</h2>
                @php($maxLoc = max(1, collect($report['top_locations'])->max('total') ?? 1))
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($report['top_locations'] as $row)
                        <li>
                            <div class="flex justify-between"><span>{{ $row['name'] }}</span><span class="font-bold">{{ $row['total'] }}</span></div>
                            <div class="mt-1 h-2 rounded bg-slate-100"><div class="h-2 rounded bg-emerald-500" style="width: {{ $row['total'] / $maxLoc * 100 }}%"></div></div>
                        </li>
                    @empty
                        <li class="text-slate-500">Belum ada data pada rentang ini.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card p-5">
                <h2 class="font-semibold">Funnel status</h2>
                <ul class="mt-3 space-y-1 text-sm">
                    @foreach ($report['funnel'] as $status => $total)
                        <li class="flex justify-between"><span class="font-mono text-xs">{{ $status }}</span><span class="font-bold">{{ $total }}</span></li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-slate-500">Funnel dihitung dari laporan yang dibuat pada rentang ini (bukan posisi global).</p>
            </div>
        </div>
    </div>
@endsection
