<x-layouts.app
    mainHeading="Manage {{ $group->name }}"
    mainDescription="Update group settings and manage members."
    :mainActions="[
        ['text' => 'View Group', 'route' => 'groups.show', 'params' => ['group' => $group]],
        ['text' => 'Back to Dashboard', 'route' => 'dashboard'],
    ]"
>
    <div class="space-y-6">
        {{-- Group Settings --}}
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Group Settings</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Update basic group information.</p>
            </div>
            <div class="px-4 py-4 sm:px-6">
                <form action="{{ route('groups.update', $group) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Group Name</label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $group->name) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            />
                            <x-inputs.input-error :messages="$errors->get('name')" />
                        </div>

                        <div class="flex justify-end">
                            <x-buttons.primary-button>Update Group</x-buttons.primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Follows --}}
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Followed Team</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage the team this group is following.</p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                @if ($group->isFollowingTeam())
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-900">
                                {{ $group->follow->team->designation }}
                            </div>
                            <div class="text-sm text-gray-500">{{ $group->follow->season->name }}</div>
                        </div>
                        <form
                            action="{{ route('groups.follow.destroy', ['group' => $group, 'follow' => $group->follow]) }}"
                            method="POST"
                            class="inline"
                        >
                            @csrf
                            @method('DELETE')
                            <x-buttons.danger-button
                                type="submit"
                                confirm="Are you sure you want to unfollow this team?"
                            >
                                Unfollow Team
                            </x-buttons.danger-button>
                        </form>
                    </div>
                @else
                    <div>
                        <p class="mb-4 text-sm text-gray-500">No team followed yet.</p>
                        <x-buttons.primary-button>
                            <a href="{{ route('groups.follow-team.create', $group) }}">Follow a Team</a>
                        </x-buttons.primary-button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Members --}}
        <div class="bg-white shadow sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Members</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage group members and join requests.</p>
            </div>
            <ul role="list" class="divide-y divide-gray-200">
                @foreach ($group->members as $member)
                    @php
                        $dropdownItems = [];
                        if ($member->isPending()) {
                            $dropdownItems[] = ['label' => 'Approve', 'href' => route('groups.approve-member', [$group, $member]), 'method' => 'POST'];
                            $dropdownItems[] = ['label' => 'Reject', 'href' => route('groups.reject-member', [$group, $member]), 'method' => 'POST', 'confirm' => 'Are you sure you want to reject this join request?'];
                        } elseif ($member->canBeRemovedBy(request()->user())) {
                            $dropdownItems[] = ['label' => 'Remove', 'href' => route('groups.remove-member', [$group, $member]), 'method' => 'DELETE', 'confirm' => 'Are you sure you want to remove this member?'];
                        }
                    @endphp

                    <li>
                        <div class="{{ $member->isPending() ? 'bg-yellow-50' : '' }} px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-300"
                                        >
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ substr($member->user->name, 0, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $member->user->name }}</div>
                                        <div class="text-sm text-gray-500">
                                            @if ($member->isPending())
                                                Requested to join
                                            @else
                                                {{ $member->role }}
                                                @if ($member->isOwner())
                                                    (Owner)
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if (! empty($dropdownItems))
                                    <x-tables.row-actions-dropdown :items="$dropdownItems" />
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-layouts.app>
