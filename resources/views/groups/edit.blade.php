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
                            <button
                                type="submit"
                                class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                            >
                                Update Group
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Pending Join Requests --}}
        @php
            $pendingMembers = $group->members->where('status', \App\Models\MemberStatus::PENDING->value);
        @endphp

        @if ($pendingMembers->isNotEmpty())
            <div class="overflow-hidden bg-white shadow sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Pending Join Requests</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Review and approve new member requests.</p>
                </div>
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach ($pendingMembers as $member)
                        <li>
                            <div class="px-4 py-4 sm:px-6">
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
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $member->user->name }}
                                            </div>
                                            <div class="text-sm text-gray-500">Requested to join</div>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <form
                                            action="{{ route('groups.approve-member', [$group, $member]) }}"
                                            method="POST"
                                            class="inline"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-3 py-2 text-sm leading-4 font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none"
                                            >
                                                Approve
                                            </button>
                                        </form>
                                        <form
                                            action="{{ route('groups.reject-member', [$group, $member]) }}"
                                            method="POST"
                                            class="inline"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-3 py-2 text-sm leading-4 font-medium text-white hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                            >
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Current Members --}}
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Current Members</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage existing group members.</p>
            </div>
            <ul role="list" class="divide-y divide-gray-200">
                @foreach ($group->members->where('status', \App\Models\MemberStatus::APPROVED->value) as $member)
                    <li>
                        <div class="px-4 py-4 sm:px-6">
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
                                        <div class="text-sm text-gray-500">{{ $member->role }}</div>
                                    </div>
                                </div>
                                @if ($group->owner_id !== $member->user_id && $group->admin->count() > 1)
                                    <form action="#" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-3 py-2 text-sm leading-4 font-medium text-white hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-layouts.app>
