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
                            <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                            <x-nav-link href="{{ route('users') }}" :active="request()->routeIs('users')">Users</x-nav-link>
                            <x-nav-link href="{{ route('teams') }}" :active="request()->routeIs('teams')">Teams</x-nav-link>
                            <x-nav-link href="{{ route('seasons') }}" :active="request()->routeIs('seasons')">Seasns</x-nav-link>
                            <x-nav-link href="{{ route('groups') }}" :active="request()->routeIs('groups')">Groups</x-nav-link>
                        @else
                            <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-nav-link>
                        @endauth
                    </div>
                </div>
            </div>
            @auth
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                    <x-nav-link href="{{ route('profile.edit') }}" :active="request()->routeIs('profile')">Profile</x-nav-link>
                    <x-nav-link href="{{ route('logout') }}" :active="request()->routeIs('profile')">Log Out</x-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>