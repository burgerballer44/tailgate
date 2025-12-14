@props([
    'title',
    'description',
    'buttonText',
    'buttonRoute',
])

<div class="text-center">
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        aria-hidden="true"
        class="mx-auto size-12 text-gray-400 dark:text-gray-500"
    >
        <path
            d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"
            stroke-width="2"
            vector-effect="non-scaling-stroke"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    <div class="mt-6">
        @if ($buttonRoute && $buttonText)
            <x-buttons.nav-button :route="$buttonRoute">
                {{ $buttonText }}
            </x-buttons.nav-button>
        @endif
    </div>
</div>
