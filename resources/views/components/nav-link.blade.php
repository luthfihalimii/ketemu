@props(['href', 'active' => false, 'icon' => null])

<a
    href="{{ $href }}"
    @class([
        'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none',
        'bg-brand-50 text-brand-700' => $active,
        'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
    ])
    @if ($active) aria-current="page" @endif
>
    @if ($icon)
        <x-icon :name="$icon" class="h-4 w-4" />
    @endif
    {{ $slot }}
</a>
