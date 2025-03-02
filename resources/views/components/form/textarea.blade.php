@props(['name', 'label', 'rows' => 4, 'placeholder' => '', 'required' => false])

<div class="mb-4">
    <label for="{{ $name }}" class="block text-gray-700 font-semibold">{{ $label }}</label>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
              class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}>{{ old($name) }}</textarea>
    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>