@props(['icon' => 'search', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'card flex flex-col items-center gap-3 px-6 py-14 text-center']) }}>
    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="h-7 w-7" />
    </span>
    <h3 class="text-lg font-semibold text-slate-800">{{ $title }}</h3>
    @if ($description)
        <p class="max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
