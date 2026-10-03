@props(['type' => 'info', 'title' => null, 'dismissible' => true])

@php
    $styles = [
        'info' => ['bg-sky-50 border-sky-200 text-sky-900', 'info'],
        'success' => ['bg-emerald-50 border-emerald-200 text-emerald-900', 'check-circle'],
        'warning' => ['bg-amber-50 border-amber-200 text-amber-900', 'alert-triangle'],
        'error' => ['bg-rose-50 border-rose-200 text-rose-900', 'x-circle'],
    ];

    [$classes, $icon] = $styles[$type] ?? $styles['info'];
@endphp

<div
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border p-4 '.$classes]) }}
    @if ($dismissible) data-flash @endif
    role="{{ $type === 'error' ? 'alert' : 'status' }}"
>
    <x-icon :name="$icon" class="mt-0.5 h-5 w-5" />

    <div class="flex-1 text-sm">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            data-dismiss
            class="hidden rounded-lg p-1 transition hover:bg-black/5"
            aria-label="Tutup pemberitahuan"
        >
            <x-icon name="x" class="h-4 w-4" />
        </button>
    @endif
</div>
