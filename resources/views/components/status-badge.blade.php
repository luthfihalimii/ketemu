@props(['status'])

@php
    $status = $status instanceof \App\Enums\ItemStatus
        ? $status
        : \App\Enums\ItemStatus::from($status);
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '.$status->color(),
]) }}>
    <x-icon :name="$status->icon()" class="h-3.5 w-3.5" />
    {{ $status->label() }}
</span>
