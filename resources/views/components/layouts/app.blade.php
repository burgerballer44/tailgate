@props([
    'mainHeading' => '',
    'mainDescription' => '',
    'mainActions' => [],
])

<x-layouts.html-header></x-layouts.html-header>

<body class="h-full bg-gray-100">
    <div class="min-h-full">
        {{-- navigation bar --}}
        <x-navigation.navigation></x-navigation.navigation>

        {{-- top bar --}}
        <div class="bg-white shadow-sm">
            <header class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                {{-- left side text --}}
                <div>
                    <h2 class="text-xl font-semibold">{{ $mainHeading }}</h2>
                    <p class="mt-1 text-sm text-gray-700">{{ $mainDescription }}</p>
                </div>

                {{-- right side actions --}}
                <div class="flex space-x-3">
                    @foreach ($mainActions as $mainAction)
                        <a
                            href="{{ route($mainAction['route'], $mainAction['params'] ?? []) }}"
                            class="bg-carolina hover:bg-navy focus-visible:outline-navy rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                        >
                            {{ $mainAction['text'] }}
                        </a>
                    @endforeach
                </div>
            </header>
        </div>

        {{-- main body --}}
        <main>
            <div id="app" class="mx-auto max-w-7xl p-4 sm:px-6 lg:px-8">
                <x-layouts.partials.flash-alert></x-layouts.partials.flash-alert>

                {{ $slot }}
            </div>
        </main>
    </div>
</body>

<x-layouts.html-footer></x-layouts.html-footer>
