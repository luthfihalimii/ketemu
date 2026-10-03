@props(['type' => 'success'])

@php
    $styles = [
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        'error' => 'bg-rose-50 border-rose-200 text-rose-800',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
        'info' => 'bg-sky-50 border-sky-200 text-sky-800',
    ];

    $icons = [
        'success' => 'check-circle',
        'error' => 'x-circle',
        'warning' => 'alert-triangle',
        'info' => 'info',
    ];
@endphp

<div class="mx-auto w-full max-w-7xl px-4 pt-3" aria-label="Pemberitahuan">
    @if (session('status'))
        <div
            data-flash
            class="flex items-start gap-3 rounded-xl border p-4 shadow-md {{ $styles['success'] }}"
            role="status"
        >
            <x-icon name="check-circle" class="mt-0.5 h-5 w-5" />
            <p class="flex-1 text-sm font-medium">{{ session('status') }}</p>
            <button type="button" data-dismiss class="hidden rounded-lg p-1 hover:bg-black/5" aria-label="Tutup">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif

    @if (isset($errors) && collect($errors->getBags())->contains(fn ($bag) => $bag->any()))
        <div
            data-flash
            class="mt-2 flex items-start gap-3 rounded-xl border p-4 shadow-md {{ $styles['error'] }}"
            role="alert"
        >
            <x-icon name="x-circle" class="mt-0.5 h-5 w-5" />
            <div class="flex-1 text-sm font-medium">
                <p class="font-semibold">Ada yang perlu diperbaiki.</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach (collect($errors->getBags())->flatMap(fn ($bag) => $bag->all())->unique() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" data-dismiss class="hidden rounded-lg p-1 hover:bg-black/5" aria-label="Tutup">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif
</div>
