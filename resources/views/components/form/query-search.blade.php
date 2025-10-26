@props([
    'label',
    'placeholder' => 'Search...',
])

<div class="min-w-0 flex-1">
    <label for="q" class="block font-semibold text-gray-700">{{ $label }}</label>
    <input
        id="q"
        name="q"
        type="text"
        value="{{ request('q') }}"
        placeholder="{{ $placeholder }}"
        class="col-start-1 row-start-1 block w-full rounded-md bg-white py-1.5 pr-10 pl-3 text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:pr-9 sm:text-sm/6 dark:bg-white/5 dark:text-white dark:outline-gray-500/50 dark:placeholder:text-gray-400/70 dark:focus:outline-blue-400"
    />
</div>
