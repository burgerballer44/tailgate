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
                        <x-buttons.nav-button :route="$mainAction['route']" :params="$mainAction['params'] ?? []">
                            {{ $mainAction['text'] }}
                        </x-buttons.nav-button>
                    @endforeach
                </div>
            </header>
        </div>

        {{-- main body --}}
        <main>
            <div id="app" class="mx-auto max-w-7xl p-4 sm:px-6 lg:px-8">
                @if (session()->has('impersonator_user_id'))
                    <div class="mb-4 rounded-lg border-2 border-amber-400 bg-amber-100 p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold tracking-wide text-amber-900 uppercase">Developer view-as mode</p>
                                <p class="mt-1 text-lg font-semibold text-amber-950">
                                    Viewing the app as {{ session('impersonated_user_name', auth()->user()?->name) }}
                                </p>
                                <p class="mt-1 text-sm text-amber-900">
                                    Permissions and page behavior now match the impersonated user.
                                </p>
                            </div>

                            <form method="POST" action="{{ route('developer.impersonation.stop') }}">
                                @csrf
                                <x-buttons.primary-button type="submit">Return to my developer account</x-buttons.primary-button>
                            </form>
                        </div>
                    </div>
                @endif

                <x-layouts.partials.flash-alert></x-layouts.partials.flash-alert>

                {{ $slot }}
            </div>
        </main>
    </div>
</body>

<x-layouts.html-footer></x-layouts.html-footer>
