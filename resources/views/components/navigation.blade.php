<nav class="bg-navy">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center">
                <div class="shrink-0">
                    {{-- <img class="size-8" src="https://tailwindui.com/plus/img/logos/mark.svg?color=indigo&shade=500" alt="Your Company"> --}}
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        @auth
                            <x-nav-link route='dashboard'>Dashboard</x-nav-link>
                            <x-nav-link route='users.index'>Users</x-nav-link>
                            <x-nav-link route='teams.index'>Teams</x-nav-link>
                            <x-nav-link route='seasons.index'>Seasons</x-nav-link>
                            <x-nav-link route='groups.index'>Groups</x-nav-link>
                        @else
                            <x-nav-link route='home'>Home</x-nav-link>
                        @endauth
                    </div>
                </div>
            </div>
            @auth
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                    <x-nav-link route='profile.edit'>Profile</x-nav-link>
                    <x-nav-link route='logout'>Log Out</x-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>