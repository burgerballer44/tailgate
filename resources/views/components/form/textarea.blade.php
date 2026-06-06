@props([
    'name',
    'label',
    'value' => '',
    'required' => false,
    'rows' => 4,
    'placeholder' => null,
])

<div class="min-w-0 flex-1">
    <x-inputs.input-label for="{{ $name }}" class="font-semibold" :value="$label" />
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        class="focus:outline-carolina mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 sm:text-sm/6"
        {{ $required ? 'required' : '' }}
        @if ($placeholder)
            placeholder="{{ $placeholder }}"
        @endif
    >{{ $value }}</textarea>
</div>