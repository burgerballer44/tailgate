<nav class="bg-navy">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-3 flex h-16 items-center justify-between">
            <div class="flex items-center">
                <div class="shrink-0">
                    {{-- <img class="size-8" src="https://tailwindui.com/plus/img/logos/mark.svg?color=indigo&shade=500" alt="Your Company"> --}}
                </div>
                <div class="hidden md:block">
                    <div class="flex items-baseline space-x-4">
                        @auth
                            <x-navigation.nav-link route="dashboard">Dashboard</x-navigation.nav-link>
                            @if (Auth::user()->role === 'Admin')
                                <x-navigation.dropdown-nav-links
                                    label="Admin"
                                    buttonClass="text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium inline-flex items-center gap-x-1.5"
                                    linkClass="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                                    :items="[
                                        ['label' => 'Users', 'route' => 'users.index'],
                                        ['label' => 'Teams', 'route' => 'teams.index'],
                                        ['label' => 'Seasons', 'route' => 'seasons.index'],
                                        ['label' => 'Groups', 'route' => 'groups.index'],
                                    ]"
                                ></x-navigation.dropdown-nav-links>
                            @endif
                        @else
                            <x-navigation.nav-link route="home">Home</x-navigation.nav-link>
                        @endauth
                    </div>
                </div>
            </div>
            @auth
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                    <x-navigation.dropdown-nav-links
                        label="{{ Auth::user()->name }}"
                        align="end"
                        :items="[
                            ['label' => 'Profile', 'route' => 'profile.edit'],
                            ['label' => 'Log out', 'route' => 'logout', 'method' => 'POST'],
                        ]"
                    ></x-navigation.dropdown-nav-links>
                </div>
            @endauth
        </div>
    </div>
</nav>
