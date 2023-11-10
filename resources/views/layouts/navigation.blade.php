<nav class="bg-carolina">
    <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
        <div class="relative flex h-16 items-center justify-between">
            <div class="flex flex-1 items-center justify-center sm:items-stretch sm:justify-start">
                <div class="sm:ml-6 sm:block">
                    <div class="flex space-x-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white aria-current=\'page\'' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-md px-3 py-2 text-sm font-medium" aria-current="page">Dashboard</a>
                            <a href="{{ route('teams.index') }}" class="{{ request()->routeIs('teams.index') ? 'bg-gray-900 text-white aria-current=\'page\'' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-md px-3 py-2 text-sm font-medium" aria-current="page">Teams</a>
                        @else
                            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'bg-gray-900 text-white aria-current=\'page\'' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} rounded-md px-3 py-2 text-sm font-medium" aria-current="page">Home</a>
                        @endauth
                    </div>
                </div>
            </div>
            @auth
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                    <a href="{{ route('profile.edit') }}" class="text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium">Profile</a>
                </div>
            @endauth
        </div>
    </div>
</nav>