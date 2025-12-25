@props([
    'label',
    'placeholder' => 'Search...',
    'error' => [],
])

<div class="min-w-0 flex-1">
    <x-inputs.input-label for="q" class="font-semibold" :value="$label" />
    <x-inputs.text-input
        id="q"
        name="q"
        type="text"
        value="{{ request('q') }}"
        placeholder="{{ $placeholder }}"
        class="block w-full rounded-md bg-white py-1.5 pr-10 pl-3 text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-300 focus:outline-2 focus:-outline-offset-2 sm:pr-9 sm:text-sm/6"
    />
    <x-inputs.input-error :messages="$error" class="mt-2" />
</div>
