@props(['title', 'description' => null, 'icon' => null])

<header class="mb-6">
    <div class="flex items-start gap-3">
        @if ($icon)
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                <x-icon :name="$icon" class="h-5.5 w-5.5" />
            </span>
        @endif
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
            @endif
        </div>
    </div>

    @if (! $slot->isEmpty())
        <div class="mt-4 flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</header>
