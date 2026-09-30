@props(['label' => null, 'name', 'options' => [], 'selected' => null, 'placeholder' => 'Pilih salah satu', 'hint' => null, 'required' => false, 'groups' => null])

@php
    $id = $attributes->get('id') ?? 'field-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $hasError = $errors->has($name);
    $current = old($name, $selected);
@endphp

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if ($label)
        <label for="{{ $id }}" class="label">
            {{ $label }}
            @if ($required)
                <span class="text-rose-600" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'field appearance-none bg-[length:1rem] pr-10']) }}
    >
        <option value="">{{ $placeholder }}</option>
        @if ($groups)
            @foreach ($groups as $groupLabel => $groupOptions)
                <optgroup label="{{ $groupLabel }}">
                    @foreach ($groupOptions as $value => $text)
                        <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $text }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        @else
            @foreach ($options as $value => $text)
                <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $text }}</option>
            @endforeach
        @endif
    </select>

    @if ($hint)
        <p class="help">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-rose-600">
            <x-icon name="alert-triangle" class="h-4 w-4" />
            {{ $message }}
        </p>
    @enderror
</div>
