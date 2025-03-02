@props(['name', 'label', 'checked' => false])

<div class="mb-4 flex items-center">
    <input type="checkbox" id="{{ $name }}" name="{{ $name }}" value="1"
           class="mr-2" {{ old($name, $checked) ? 'checked' : '' }}>
    <label for="{{ $name }}" class="text-gray-700 font-semibold">{{ $label }}</label>
</div>