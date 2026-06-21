@php
    $followDescription = 'No team followed yet.';
    $follows = $group->follow_collection;
    if ($follows->isNotEmpty()) {
        $followDescription = $follows->count().' team'.($follows->count() === 1 ? '' : 's').' followed';
    }
@endphp

<x-layouts.app
    mainHeading="{{ $group->name }}"
    mainDescription="{{ $followDescription }}"
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
                        @if ($follows->isEmpty())
                            <p class="text-sm text-gray-500">No teams followed yet.</p>
                        @else
                            <ul class="space-y-2">
                                @foreach ($follows as $follow)
                                    <li class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                        <p class="font-semibold text-gray-900">{{ $follow->team->display_name }}</p>
                                        <p class="text-gray-500">{{ $follow->sport_display }}</p>
                                    </li>
                                @endforeach
                            </ul>
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
                        {{ $group->members_count }}
                        <span class="font-normal text-gray-400">/ {{ $group->member_limit }}</span>
                    </p>
                </div>
                <div class="px-6 py-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Invite code</p>
                    <p class="mt-1 font-mono text-sm font-semibold tracking-widest text-gray-900">{{ $group->invite_code }}</p>
                </div>
            </div>
        </x-groups.section-card>

        {{-- My Players --}}
        <x-groups.section-card overflowClass="overflow-visible" title="My players" description="Manage your players.">
            @if ($playerCount > 0)
                <div class="space-y-4">
                    <x-tables.full-width
                        :headers="['Player Name', 'Created', 'Actions']"
                        :rows="$memberPlayers"
                        :columns="[
                            'player_name',
                            fn ($row) => $row->created_at?->format('M d, Y'),
                        ]"
                        :rowActions="[
                            [
                                'label' => 'Edit',
                                'route' => 'groups.members.players.edit',
                                'routeParams' => ['group' => $group, 'member' => $currentMember, 'player' => 'ulid'],
                            ],
                            [
                                'label' => 'Delete',
                                'type' => 'form',
                                'route' => 'groups.members.players.destroy',
                                'routeParams' => ['group' => $group, 'member' => $currentMember, 'player' => 'ulid'],
                                'confirm' => 'Are you sure you want to delete this player?',
                            ],
                        ]"
                        emptyTitle="No players match this filter"
                        emptyDescription="Try a different search term."
                    />

                    @if ($playerCount < $regularMemberPlayerLimit)
                        <div class="flex justify-end">
                            <x-buttons.nav-button
                                route="groups.members.players.create"
                                :params="['group' => $group, 'member' => $currentMember]"
                            >
                                Create player
                            </x-buttons.nav-button>
                        </div>
                    @endif
                </div>
            @else
                <x-empty-state
                    title="No players yet"
                    description="Create a player to start submitting score predictions."
                    buttonText="Create player"
                    buttonRoute="groups.members.players.create"
                    :buttonParams="['group' => $group, 'member' => $currentMember]"
                />
            @endif
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

        {{-- Predictions --}}
        {{-- TODO: wire up PlayerQueryService / PredictionQueryService for historical results --}}
        <x-groups.section-card title="Predictions & results" description="How your predictions compared to final scores.">
            <x-empty-state
                title="No results yet"
                description="Completed game results and your predictions will show here."
                :buttonText="null"
                :buttonRoute="null"
            />
        </x-groups.section-card>

    </div>
</x-layouts.app>
