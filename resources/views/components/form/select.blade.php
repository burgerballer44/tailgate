@props([
    'name',
    'label',
    'options' => [],
    'required' => false,
])

<div class="min-w-0 flex-1">
    <label for="{{ $name }}" class="block font-semibold text-gray-700">{{ $label }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        class="w-full rounded-md border px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
        {{ $required ? 'required' : '' }}
    >
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" {{ request()->input($name, old($name)) == $value ? 'selected' : '' }}>
                {{ $text }}
            </option>
        @endforeach
    </select>
    @error($name)
        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
    @enderror
</div>
