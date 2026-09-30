@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'submit',
    'icon' => null,
    'iconTrailing' => null,
    'block' => false,
])

@php
    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-xs hover:bg-brand-700 focus-visible:ring-brand-500 active:bg-brand-800',
        'secondary' => 'bg-white text-slate-700 border border-slate-300 shadow-xs hover:bg-slate-50 focus-visible:ring-brand-500',
        'success' => 'bg-emerald-600 text-white shadow-xs hover:bg-emerald-700 focus-visible:ring-emerald-500',
        'danger' => 'bg-rose-600 text-white shadow-xs hover:bg-rose-700 focus-visible:ring-rose-500',
        'ghost' => 'text-slate-600 hover:bg-slate-100 focus-visible:ring-brand-500',
        'link' => 'text-brand-700 underline-offset-4 hover:underline focus-visible:ring-brand-500',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm gap-1.5',
        'md' => 'px-4 py-2.5 text-sm gap-2',
        'lg' => 'px-5 py-3 text-base gap-2',
    ];

    $classes = 'inline-flex items-center justify-center rounded-xl font-semibold transition
        focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none
        disabled:cursor-not-allowed disabled:opacity-60 '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md']).' '
        .($block ? 'w-full' : '');

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if ($icon)
        <x-icon :name="$icon" class="h-4 w-4" />
    @endif
    {{ $slot }}
    @if ($iconTrailing)
        <x-icon :name="$iconTrailing" class="h-4 w-4" />
    @endif
</{{ $tag }}>
