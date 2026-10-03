@props(['item'])


<a
    href="{{ route('items.show', $item) }}"
    {{ $attributes->merge(['class' => 'card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md']) }}
>
    <div class="relative aspect-4/3 overflow-hidden bg-slate-100">
        <x-item-photo :item="$item" class="transition duration-200 group-hover:scale-[1.03]" />

        <div class="absolute top-3 left-3">
            <x-status-badge :status="$item->status" class="shadow-xs" />
        </div>
    </div>

    <div class="flex flex-1 flex-col gap-1.5 p-4">
        <h3 class="line-clamp-2 font-semibold text-slate-900 group-hover:text-brand-700">
            {{ $item->title }}
        </h3>

        <p class="flex items-center gap-1.5 text-sm text-slate-500">
            <x-icon name="map-pin" class="h-4 w-4" />
            {{ $item->location?->name ?? 'Lokasi tidak dicatat' }}
        </p>

        <p class="flex items-center gap-1.5 text-sm text-slate-500">
            <x-icon name="calendar" class="h-4 w-4" />
            {{ $item->occurred_at?->translatedFormat('d F Y') ?? 'Waktu tidak dicatat' }}
        </p>

        @if ($item->category)
            <span class="mt-1 inline-flex w-fit rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                {{ $item->category->name }}
            </span>
        @endif
    </div>
</a>
