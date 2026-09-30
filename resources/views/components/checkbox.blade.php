@props(['name', 'label', 'hint' => null, 'required' => false, 'checked' => false])

@php
    $id = $attributes->get('id') ?? 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
@endphp

<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 bg-white p-4 transition hover:bg-slate-50 has-checked:border-brand-500 has-checked:ring-2 has-checked:ring-brand-500/30">
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $id }}"
            value="1"
            @checked(old($name, $checked))
            class="mt-0.5 h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
            {{ $attributes->except(['class', 'id']) }}
        >
        <span>
            <span class="block text-sm font-semibold text-slate-800">
                {{ $label }}
                @if ($required)
                    <span class="text-rose-600" aria-hidden="true">*</span>
                @endif
            </span>
            @if ($hint)
                <span class="mt-1 block text-sm text-slate-500">{{ $hint }}</span>
            @endif
        </span>
    </label>

    @error($name)
        <p class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-rose-600">
            <x-icon name="alert-triangle" class="h-4 w-4" />
            {{ $message }}
        </p>
    @enderror
</div>
