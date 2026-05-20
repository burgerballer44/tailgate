<x-layouts.app
    mainHeading="{{ $group->name }}"
    mainDescription="{{ $group->isFollowingTeam() ? 'Following ' . $group->follow->team->display_name : 'No team followed yet.' }}"
    :mainActions="collect([
        ['text' => 'Back to dashboard', 'route' => 'dashboard'],
        $group->isAdminOrOwner(auth()->user()) ? [
            'text' => 'Manage group',
            'route' => 'groups.edit',
            'params' => ['group' => $group],
        ] : null,
    ])->filter()->values()->toArray()"
>
    <div class="space-y-6">

        {{-- At-a-glance stat strip --}}
        <x-groups.section-card title="Group snapshot" description="Quick status for this group.">
            <div class="-mx-6 -my-5 grid grid-cols-2 divide-x divide-y divide-gray-100 sm:grid-cols-4 sm:divide-y-0">
                <div class="px-6 py-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Following</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        @if ($group->isFollowingTeam())
                            {{ $group->follow->team->display_name }}
                        @else
                            <span class="font-normal text-gray-400">No team yet</span>
                        @endif
                    </p>
                </div>
                <div class="px-6 py-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Owner</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $group->owner->name }}</p>
                </div>
                <div class="px-6 py-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Members</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $group->members->count() }}
                        <span class="font-normal text-gray-400">/ {{ $group->member_limit }}</span>
                    </p>
                </div>
                <div class="px-6 py-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Invite code</p>
                    <p class="mt-1 font-mono text-sm font-semibold tracking-widest text-gray-900">{{ $group->invite_code }}</p>
                </div>
            </div>
        </x-groups.section-card>

        {{-- Upcoming Games --}}
        {{-- TODO: wire up GameQueryService to pull upcoming games for the followed team --}}
        <x-groups.section-card title="Upcoming games" description="Games open for score predictions.">
            <x-empty-state
                title="No upcoming games"
                description="Upcoming games for your followed team will appear here."
                :buttonText="null"
                :buttonRoute="null"
            />
        </x-groups.section-card>

        {{-- My Players & Predictions --}}
        {{-- TODO: wire up PlayerQueryService and ScoreCommandService per member --}}
        <x-groups.section-card title="My players &amp; predictions" description="Manage your players and submit score predictions per game.">
            <x-slot name="headerAction">
                {{-- TODO: replace with link to player creation once wired up --}}
                <span class="inline-flex items-center rounded-md bg-carolina/15 px-2.5 py-1 text-xs font-medium text-navy ring-1 ring-inset ring-carolina/30">
                    Coming soon
                </span>
            </x-slot>

            <x-empty-state
                title="No players yet"
                description="Create a player to start submitting score predictions."
                :buttonText="null"
                :buttonRoute="null"
            />
        </x-groups.section-card>

        {{-- Scores --}}
        {{-- TODO: wire up PlayerQueryService / ScoreQueryService for historical results --}}
        <x-groups.section-card title="Scores &amp; results" description="How your predictions compared to final scores.">
            <x-empty-state
                title="No results yet"
                description="Completed game results and your prediction scores will show here."
                :buttonText="null"
                :buttonRoute="null"
            />
        </x-groups.section-card>

    </div>
</x-layouts.app>
