@props(['href', 'icon' => null, 'active' => false])

<a
    href="{{ $href }}"
    @class([
        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
        'bg-brand-50 text-brand-700' => $active,
        'text-slate-700 hover:bg-slate-100' => ! $active,
    ])
    @if ($active) aria-current="page" @endif
>
    @if ($icon)
        <x-icon :name="$icon" class="h-5 w-5" />
    @endif
    {{ $slot }}
</a>
