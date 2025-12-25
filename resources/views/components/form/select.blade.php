@props([
    'name',
    'label',
    'value',
    'options' => [],
    'required' => false,
    'error' => [],
    'placeholder' => null,
    'placeholderDisabled' => false,
])

<div class="min-w-0 flex-1">
    <x-inputs.input-label for="{{ $name }}" class="font-semibold" :value="$label" />
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        class="focus:outline-carolina mt-1 block w-full rounded-md bg-white py-1.5 pr-10 pl-3 text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 sm:text-sm/6"
        {{ $required ? 'required' : '' }}
    >
        @if ($placeholder)
            <option {{ $placeholderDisabled ? 'disabled' : '' }} {{ empty($value) ? 'selected' : '' }} value="">
                {{ $placeholder }}
            </option>
        @endif

        @foreach ($options as $option => $text)
            <option value="{{ $option }}" {{ $value == $option ? 'selected' : '' }}>
                {{ $text }}
            </option>
        @endforeach
    </select>
    <x-inputs.input-error :messages="$error" class="mt-2" />
</div>
