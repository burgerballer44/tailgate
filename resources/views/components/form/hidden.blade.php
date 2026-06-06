@props([
    'name',
    'value' => null,
])

<input type="hidden" name="{{ $name }}" value="{{ $value }}" />