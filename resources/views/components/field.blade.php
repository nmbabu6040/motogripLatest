@props(['name', 'label', 'type' => 'text', 'value' => null, 'autocomplete' => null])
@php
    $dot = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = trim(str_replace(['[', ']'], ['_', ''], $name), '_');
@endphp
<div class="mb-4">
    <label for="{{ $id }}" class="block text-sm font-semibold mb-1">{{ $label }}</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($dot, $value) }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        {{ $attributes->merge(['class' => 'w-full rounded-control border border-steel px-3 py-2']) }}>
    @error($dot)
        <p class="mt-1 text-sm text-red-800">{{ $message }}</p>
    @enderror
</div>
