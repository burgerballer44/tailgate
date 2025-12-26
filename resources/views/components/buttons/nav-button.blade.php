@props([
    'route' => '',
    'params' => [],
])

<a href="{{ route($route, $params) }}">
    <button
        type="button"
        {{ $attributes->merge(['class' => 'bg-carolina hover:bg-navy cursor-pointer text-white font-semibold py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2']) }}
    >
        {{ $slot }}
    </button>
</a>
