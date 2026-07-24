@props([
    'name',
    'label',
    'value' => null,
    'id' => null,
    'options' => [],
    'required' => false,
    'error' => [],
    'placeholder' => null,
    'placeholderDisabled' => false,
    'labelClass' => 'font-semibold',
    'containerClass' => 'min-w-0 flex-1',
])

@php
    $selectId = $id ?? $name;
@endphp

<div class="{{ $containerClass }}">
    <x-inputs.input-label for="{{ $selectId }}" :value="$label" class="{{ $labelClass }}" />

    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => 'focus:outline-carolina mt-1 block w-full rounded-md bg-white py-1.5 pr-10 pl-3 text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 sm:text-sm/6']) }}
        {{ $required ? 'required' : '' }}
    >
        @if ($placeholder)
            <option {{ $placeholderDisabled ? 'disabled' : '' }} {{ empty($value) ? 'selected' : '' }} value="">
                {{ $placeholder }}
            </option>
        @endif

        @if (count($options) > 0)
            @foreach ($options as $option => $text)
                <option value="{{ $option }}" {{ $value == $option ? 'selected' : '' }}>
                    {{ $text }}
                </option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>

    <x-inputs.input-error :messages="$error" class="mt-2" />
</div>