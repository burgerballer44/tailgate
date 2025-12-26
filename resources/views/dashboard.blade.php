<x-layouts.app
    mainHeading="Dashboard"
    mainDescription="Welcome back, {{ $user->name }}! Manage your groups and predictions here."
>
    <div class="space-y-6">
        {{-- Groups --}}
        @if ($groups->isNotEmpty())
            <div class="overflow-hidden bg-white shadow sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Your Groups</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage your groups and view upcoming games.</p>
                </div>
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach ($groups as $group)
                        <li>
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-300"
                                            >
                                                <span class="text-sm font-medium text-gray-700">
                                                    {{ substr($group->name, 0, 2) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $group->name }}</div>
                                            <div class="text-sm text-gray-500">Owner: {{ $group->owner->name }}</div>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                            View Details
                                        </a>
                                        <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                            Manage
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            {{-- No Groups --}}
            <div
                class="flex flex-col items-center justify-center space-y-4 rounded-lg border-2 border-dashed border-gray-300 p-6"
            >
                <h3 class="text-lg font-medium text-gray-900">No Groups Yet</h3>
                <p class="text-sm text-gray-500">You haven't created or joined any groups yet.</p>
                <div class="flex space-x-4">
                    <x-buttons.nav-button route="groups.create">Create Group</x-buttons.nav-button>
                    <x-buttons.nav-button route="groups.join" color="green">
                        Join Group By Invite Code
                    </x-buttons.nav-button>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
