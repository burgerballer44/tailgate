@props([
    'title' => '',
    'description' => '',
])

<div class="grid grid-cols-1 gap-x-8 gap-y-10 border-b border-gray-900/10 pb-12 md:grid-cols-3">
    <div>
        <h2 class="text-base/7 font-semibold text-gray-900">{{ $title }}</h2>
        <p class="mt-1 text-sm/6 text-gray-600">{{ $description }}</p>
    </div>

    <div class="">
        {{ $slot }}
    </div>
</div>
