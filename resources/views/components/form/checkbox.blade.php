@props([
    'name',
    'label',
    'id' => null,
    'value' => '1',
    'checked' => false,
    'includeHidden' => false,
    'hiddenValue' => '0',
    'labelClass' => 'font-semibold',
])

@php
    $inputId = $id ?? $name;
@endphp

<div class="flex items-center gap-2">
    @if ($includeHidden)
        <input type="hidden" name="{{ $name }}" value="{{ $hiddenValue }}" />
    @endif

    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="checkbox"
        value="{{ $value }}"
        class="text-navy-600 focus:ring-navy-500 rounded border-gray-300 shadow-sm"
        @checked($checked)
    />

    <x-inputs.input-label for="{{ $inputId }}" :value="$label" class="{{ $labelClass }}" />
</div>