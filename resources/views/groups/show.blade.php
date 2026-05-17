<x-layouts.app
    mainHeading="{{ $group->name }}"
    mainDescription="Group details and member information."
    :mainActions="collect([
        ['text' => 'Back to Dashboard', 'route' => 'dashboard'],
        $group->isAdminOrOwner(auth()->user()) ? [
            'text' => 'Manage Group',
            'route' => 'groups.edit',
            'params' => ['group' => $group]
        ] : null,
    ])->filter()->values()->toArray()"
>
    <div class="space-y-6">
        {{-- Group Info --}}
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Group Information</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Basic details about this group.</p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $group->name }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Owner</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $group->owner->name }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Invite Code</dt>
                        <dd class="mt-1 font-mono text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            {{ $group->invite_code }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Members</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            {{ $group->members->count() }} / {{ $group->member_limit }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Following Team</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                            @if ($group->isFollowingTeam())
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $group->follow->team->designation }}
                                </div>
                            @else
                                None
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Members --}}
        <div class="overflow-hidden bg-white shadow sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Members</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">People in this group.</p>
            </div>
            <ul role="list" class="divide-y divide-gray-200">
                @foreach ($group->members as $member)
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
                                @if ($member->isPending())
                                    <span
                                        class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800"
                                    >
                                        Pending Approval
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                    >
                                        Active
                                    </span>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-layouts.app>
