@props(['status'])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '.$status->color()]) }}>{{ $status->label() }}</span>
