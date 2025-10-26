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
                            <x-navigation.nav-link route="users.index">Users</x-navigation.nav-link>
                            <x-navigation.nav-link route="teams.index">Teams</x-navigation.nav-link>
                            <x-navigation.nav-link route="seasons.index">Seasons</x-navigation.nav-link>
                            <x-navigation.nav-link route="groups.index">Groups</x-navigation.nav-link>
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
