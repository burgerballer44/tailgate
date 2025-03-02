@props(['name', 'label', 'required' => false])

<div class="mb-4">
    <label for="{{ $name }}" class="block text-gray-700 font-semibold">{{ $label }}</label>
    <input type="file" id="{{ $name }}" name="{{ $name }}"
           class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
           {{ $required ? 'required' : '' }}>
    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>