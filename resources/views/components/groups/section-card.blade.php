@props([
    'title' => '',
    'description' => '',
    'overflowClass' => 'overflow-hidden',
])

<div class="{{ $overflowClass }} rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-4">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
            @if ($description)
                <p class="mt-0.5 text-xs text-gray-500">{{ $description }}</p>
            @endif
        </div>

        @isset($headerAction)
            <div class="shrink-0">
                {{ $headerAction }}
            </div>
        @endisset
    </div>

    <div class="px-6 py-5">
        {{ $slot }}
    </div>
</div>
