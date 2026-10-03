@props(['item', 'contain' => false, 'lazy' => true, 'src' => null])
@php
    $source = $src ?? ($item->photo_path ? app(\App\Services\ItemPhotoService::class)->url($item->photo_path) : null);
@endphp
@if ($source)
    <img src="{{ $source }}" alt="Foto {{ $item->title }}" loading="{{ $lazy ? 'lazy' : 'eager' }}" decoding="async" referrerpolicy="no-referrer" width="1600" height="1600" {{ $attributes->class(['h-full w-full', 'object-contain' => $contain, 'object-cover' => ! $contain]) }}>
@else
    <div class="flex h-full w-full flex-col items-center justify-center gap-2 bg-slate-100 text-slate-500">
        <x-icon name="package" class="h-8 w-8" />
        <span class="text-xs">Tidak ada foto</span>
    </div>
@endif
