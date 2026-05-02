<x-layouts.app
    mainHeading="Dashboard"
    mainDescription="Welcome back, {{ $user->name }}! Manage your groups and predictions here."
>
    <div class="space-y-6">
        {{-- Groups --}}
        @if ($groups->isNotEmpty())
            <div class="bg-white shadow sm:rounded-md">
                {{-- Groups Section Header --}}
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6 dark:border-white/10">
                    <div class="-mt-4 -ml-4 flex flex-wrap items-center justify-between sm:flex-nowrap">
                        <div class="mt-4 ml-4">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Your Groups</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Manage your groups and view upcoming games.
                            </p>
                        </div>
                        <div class="mt-4 ml-4 flex shrink-0 space-x-3">
                            <x-buttons.nav-button route="groups.create">Create Group</x-buttons.nav-button>
                            <x-buttons.nav-button route="groups.join">Join Group</x-buttons.nav-button>
                        </div>
                    </div>
                </div>
                {{-- Groups List --}}
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach ($groups as $group)
                        <li>
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                @if ($user->canAccessGroup($group))
                                                    <a href="{{ route('groups.show', $group) }}">
                                                        {{ $group->name }}
                                                    </a>
                                                @else
                                                    {{ $group->name }}
                                                @endif
                                            </div>
                                            @if (! $user->canAccessGroup($group))
                                                <div class="text-sm text-yellow-600">Membership Pending Approval</div>
                                            @endif

                                            {{-- <div class="text-sm text-gray-500">Owner: {{ $group->owner->name }}</div> --}}
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        @if ($user->canAccessGroup($group))
                                            <x-tables.row-actions-dropdown
                                                :items="[
                                                    ['label' => 'View Group', 'href' => route('groups.show', $group)],
                                                ]"
                                            />
                                        @endif
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
