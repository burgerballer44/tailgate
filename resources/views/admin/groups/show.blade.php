<x-layouts.app
    mainHeading="Group: {{ $group->name }}"
    mainDescription="Details of the group including members and players."
    :mainActions="[
        ['text' => 'Edit Group', 'route' => 'groups.edit', 'params' => ['group' => $group->ulid]],
        ['text' => 'Add Member', 'route' => 'groups.members.create', 'params' => ['group' => $group->ulid]],
        ['text' => 'Follow Team', 'route' => 'groups.follow-team.create', 'params' => ['group' => $group->ulid]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('groups.index')],
            ['text' => $group->name, 'active' => true],
        ]"
    />

    <x-model-viewer
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
                'label' => 'Member Limit',
                'value' => $group->member_limit,
            ],
            [
                'label' => 'Player Limit',
                'value' => $group->player_limit,
            ],
            [
                'label' => 'Invite Code',
                'value' => $group->invite_code,
            ],
            [
                'label' => 'Created At',
                'value' => $group->created_at?->format('F j, Y, g:i a') ?? 'N/A',
            ],
        ]"
    />

    {{-- Follow Section --}}
    @if ($group->follow && $group->follow->team && $group->follow->season)
        <div class="mt-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Following</h2>
                <form
                    method="POST"
                    action="{{ route('groups.follow.destroy', [$group, $group->follow]) }}"
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
                :fields="[
                    [
                        'label' => 'Team',
                        'value' => $group->follow->team->designation . ' (' . $group->follow->team->mascot . ')',
                    ],
                    [
                        'label' => 'Season',
                        'value' => $group->follow->season->name,
                    ],
                ]"
            />
        </div>
    @endif

    {{-- Members Section --}}
    <div class="mt-8">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Members</h2>
            <a href="{{ route('groups.members.create', $group) }}" class="btn btn-primary">Add Member</a>
        </div>
        <x-tables.full-width
            heading="Members"
            :headers="['User', 'Role', 'Joined', 'Actions']"
            :rows="$group->members"
            :columns="['user.name', 'role', 'created_at']"
            :rowActions="[
                [
                    'label' => 'Show',
                    'route' => 'groups.members.show',
                    'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
                ],
                [
                    'label' => 'Edit',
                    'route' => 'groups.members.edit',
                    'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
                ],
                [
                    'label' => 'Delete',
                    'type' => 'form',
                    'route' => 'groups.members.destroy',
                    'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
                    'confirm' => 'Are you sure you want to delete this member?'
                ]
            ]"
        ></x-tables.full-width>
    </div>

    {{-- Players Section --}}
    <div class="mt-8">
        <h2 class="mb-4 text-lg font-semibold">Players</h2>
        <x-tables.full-width
            heading="Players"
            :headers="['Player Name', 'Member', 'Created', 'Actions']"
            :rows="$group->players"
            :columns="['player_name', 'member.user.name', 'created_at']"
            :rowActions="[
                [
                    'label' => 'Show',
                    'route' => 'groups.members.players.show',
                    'routeParams' => ['group' => $group->ulid, 'member' => 'member.ulid', 'player' => 'ulid'],
                ],
                [
                    'label' => 'Edit',
                    'route' => 'groups.members.players.edit',
                    'routeParams' => ['group' => $group->ulid, 'member' => 'member.ulid', 'player' => 'ulid'],
                ],
                [
                    'label' => 'Delete',
                    'type' => 'form',
                    'route' => 'groups.members.players.destroy',
                    'routeParams' => ['group' => $group->ulid, 'member' => 'member.ulid', 'player' => 'ulid'],
                    'confirm' => 'Are you sure you want to delete this player?'
                ]
            ]"
        ></x-tables.full-width>
    </div>

    {{-- Scores Section --}}
    <div class="mt-8">
        <h2 class="mb-4 text-lg font-semibold">Recent Scores</h2>
        <x-tables.full-width
            heading="Scores"
            :headers="['Player', 'Member', 'Game', 'Prediction', 'Submitted', 'Actions']"
            :rows="$group->players->flatMap->scores->sortByDesc('created_at')->take(20)"
            :columns="[
                'player.player_name',
                'player.member.user.name',
                'game.homeTeam.name . \' vs \' . game.awayTeam.name',
                'home_team_prediction . \' - \' . away_team_prediction',
                'created_at'
            ]"
            :rowActions="[
                [
                    'label' => 'Edit',
                    'route' => 'groups.members.players.scores.edit',
                    'routeParams' => ['group' => $group->ulid, 'member' => 'player.member.ulid', 'player' => 'player.ulid', 'score' => 'ulid'],
                ],
                [
                    'label' => 'Delete',
                    'type' => 'form',
                    'route' => 'groups.members.players.scores.destroy',
                    'routeParams' => ['group' => $group->ulid, 'member' => 'player.member.ulid', 'player' => 'player.ulid', 'score' => 'ulid'],
                    'confirm' => 'Are you sure you want to delete this score?'
                ]
            ]"
        ></x-tables.full-width>
    </div>
</x-layouts.app>
