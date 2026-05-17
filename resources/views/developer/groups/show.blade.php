<x-layouts.app
    mainHeading="Group: {{ $group->name }}"
    mainDescription="Details of the group including members and players."
    :mainActions="[
        ['text' => 'Edit Group', 'route' => 'developer.groups.edit', 'params' => ['group' => $group->ulid]],
        ['text' => 'Add Member', 'route' => 'developer.groups.members.create', 'params' => ['group' => $group->ulid]],
        ['text' => 'Follow Team', 'route' => 'developer.groups.follow-team.create', 'params' => ['group' => $group->ulid]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'active' => true],
        ]"
    />

    @php
        $tabs = [
            'details' => 'Details',
            'members' => 'Members',
            'players' => 'Players',
            'scores' => 'Scores',
        ];
    @endphp

    <div class="mt-6">
        <div class="grid grid-cols-1 sm:hidden">
            <label for="group-tab-selector" class="sr-only">Select a tab</label>
            <select
                id="group-tab-selector"
                aria-label="Select a tab"
                class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-2 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                onchange="window.location.href = this.value"
            >
                @foreach ($tabs as $key => $label)
                    <option
                        value="{{ route('developer.groups.show', ['group' => $group->ulid, 'tab' => $key]) }}"
                        @selected($activeTab === $key)
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <svg
                viewBox="0 0 16 16"
                fill="currentColor"
                aria-hidden="true"
                class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end fill-gray-500"
            >
                <path
                    d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z"
                    clip-rule="evenodd"
                    fill-rule="evenodd"
                />
            </svg>
        </div>

        <div class="hidden sm:block">
            <div class="border-b border-gray-200">
                <nav aria-label="Tabs" class="-mb-px flex space-x-8">
                    @foreach ($tabs as $key => $label)
                        @php
                            $isActive = $activeTab === $key;
                        @endphp

                        <a
                            href="{{ route('developer.groups.show', ['group' => $group->ulid, 'tab' => $key]) }}"
                            @class([
                                'inline-flex items-center border-b-2 px-1 py-4 text-sm font-medium',
                                'border-navy text-navy' => $isActive,
                                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => ! $isActive,
                            ])
                            @if($isActive) aria-current="page" @endif
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>
    </div>

    @if ($activeTab === 'details')
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-model-viewer
                message="Group information"
                details="Primary identifiers and ownership details."
                tone="info"
                :fields="[
                    [
                        'label' => 'Name',
                        'value' => $group->name,
                    ],
                    [
                        'label' => 'Owner',
                        'value' => $group->owner?->name ?? 'N/A',
                    ],
                    [
                        'label' => 'Owner ID',
                        'value' => $group->owner_id,
                    ],
                    [
                        'label' => 'Invite Code',
                        'value' => $group->invite_code,
                    ],
                    [
                        'label' => 'ULID',
                        'value' => $group->ulid,
                    ],
                    [
                        'label' => 'ID',
                        'value' => $group->id,
                    ],
                ]"
            />

            <x-model-viewer
                message="Limits and related records"
                details="Capacity and relationship summary for this group."
                tone="success"
                :fields="[
                    [
                        'label' => 'Member Limit',
                        'value' => $group->member_limit,
                    ],
                    [
                        'label' => 'Player Limit',
                        'value' => $group->player_limit,
                    ],
                    [
                        'label' => 'Member Count',
                        'value' => $group->members_count,
                    ],
                    [
                        'label' => 'Player Count',
                        'value' => $group->players_count,
                    ],
                    [
                        'label' => 'Following Team',
                        'value' => $group->follow ? 'Yes' : 'No',
                    ],
                ]"
            />

            <x-model-viewer
                message="Metadata"
                details="Lifecycle context for this group record."
                tone="neutral"
                :fields="[
                    [
                        'label' => 'Created At',
                        'value' => $group->created_at?->format('F j, Y, g:i a') ?? 'N/A',
                    ],
                    [
                        'label' => 'Updated At',
                        'value' => $group->updated_at?->format('F j, Y, g:i a') ?? 'N/A',
                    ],
                ]"
            />
        </div>

        @if ($group->follow && $group->follow->team)
            <div class="mt-8">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Following</h2>
                    <form
                        method="POST"
                        action="{{ route('developer.groups.follow.destroy', [$group, $group->follow]) }}"
                        class="inline"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to unfollow this team?')"
                        >
                            Unfollow Team
                        </button>
                    </form>
                </div>
                <x-model-viewer
                    message="Current follow"
                    details="Team this group is currently following."
                    tone="warning"
                    :fields="[
                        [
                            'label' => 'Team',
                            'value' => $group->follow->team->designation,
                        ],
                    ]"
                />
            </div>
        @endif
    @endif

    @if ($activeTab === 'members')
        <div class="mt-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Members</h2>
                <a href="{{ route('developer.groups.members.create', $group) }}" class="btn btn-primary">Add Member</a>
            </div>
            <x-tables.full-width
                heading="Members"
                :headers="['User', 'Role', 'Status', 'Joined', 'Actions']"
                :rows="$group->members"
                :columns="[
                    'user.name',
                    'role',
                    'status',
                    fn ($row) => $row->created_at?->format('Y-m-d H:i:a'),
                ]"
                :rowActions="[
                    [
                        'label' => 'Show',
                        'route' => 'developer.groups.members.show',
                        'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'developer.groups.members.edit',
                        'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
                    ],
                    [
                        'label' => 'Delete',
                        'type' => 'form',
                        'route' => 'developer.groups.members.destroy',
                        'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
                        'confirm' => 'Are you sure you want to delete this member?'
                    ]
                ]"
            ></x-tables.full-width>
        </div>
    @endif

    @if ($activeTab === 'players')
        <div class="mt-8">
            <h2 class="mb-4 text-lg font-semibold">Players</h2>
            <x-tables.full-width
                heading="Players"
                :headers="['Player Name', 'Member', 'Created', 'Actions']"
                :rows="$group->players"
                :columns="[
                    'player_name',
                    'member.user.name',
                    fn ($row) => $row->created_at?->format('Y-m-d H:i:a'),
                ]"
                :rowActions="[
                    [
                        'label' => 'Show',
                        'route' => 'developer.groups.members.players.show',
                        'routeParams' => ['group' => $group->ulid, 'member' => 'member.ulid', 'player' => 'ulid'],
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'developer.groups.members.players.edit',
                        'routeParams' => ['group' => $group->ulid, 'member' => 'member.ulid', 'player' => 'ulid'],
                    ],
                    [
                        'label' => 'Delete',
                        'type' => 'form',
                        'route' => 'developer.groups.members.players.destroy',
                        'routeParams' => ['group' => $group->ulid, 'member' => 'member.ulid', 'player' => 'ulid'],
                        'confirm' => 'Are you sure you want to delete this player?'
                    ]
                ]"
            ></x-tables.full-width>
        </div>
    @endif

    @if ($activeTab === 'scores')
        <div class="mt-8">
            <h2 class="mb-4 text-lg font-semibold">Recent Scores</h2>
            <x-tables.full-width
                heading="Scores"
                :headers="['Player', 'Member', 'Game', 'Prediction', 'Submitted', 'Actions']"
                :rows="$scores"
                :columns="[
                    'player.player_name',
                    'player.member.user.name',
                    'game.homeTeam.name . \' vs \' . game.awayTeam.name',
                    'home_team_prediction . \' - \' . away_team_prediction',
                    fn ($row) => $row->created_at?->format('Y-m-d H:i:a'),
                ]"
                :rowActions="[
                    [
                        'label' => 'Edit',
                        'route' => 'developer.groups.members.players.scores.edit',
                        'routeParams' => ['group' => $group->ulid, 'member' => 'player.member.ulid', 'player' => 'player.ulid', 'score' => 'ulid'],
                    ],
                    [
                        'label' => 'Delete',
                        'type' => 'form',
                        'route' => 'developer.groups.members.players.scores.destroy',
                        'routeParams' => ['group' => $group->ulid, 'member' => 'player.member.ulid', 'player' => 'player.ulid', 'score' => 'ulid'],
                        'confirm' => 'Are you sure you want to delete this score?'
                    ]
                ]"
            ></x-tables.full-width>

            <div class="mt-6 px-4 sm:px-6 lg:px-8">
                {{ $scores->links() }}
            </div>
        </div>
    @endif
</x-layouts.app>
