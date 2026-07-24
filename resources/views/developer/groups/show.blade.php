@php
    $tabs = [
        'details' => 'Details',
        'members' => 'Members',
        'players' => 'Players',
        'upcoming-games' => 'Prediction feed',
        'leaderboard' => 'Leaderboard',
        'raw-prediction-data' => 'Raw prediction data',
    ];

    $followDescription = 'No team followed yet.';
    if ($group->follows->isNotEmpty()) {
        $followDescription = $group->follows->count().' team'.($group->follows->count() === 1 ? '' : 's').' followed';
    }
@endphp

<x-layouts.app
    mainHeading="Developer group inspector: {{ $group->name }}"
    mainDescription="{{ $followDescription }}. View group data, member activity, and season results."
    :mainActions="[
        ['text' => 'Edit settings', 'route' => 'developer.groups.edit', 'params' => ['group' => $group->ulid]],
        ['text' => 'Add member', 'route' => 'developer.groups.members.create', 'params' => ['group' => $group->ulid]],
        ['text' => 'Follow team', 'route' => 'developer.groups.follow-team.create', 'params' => ['group' => $group->ulid]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'active' => true],
        ]"
    />

    <div class="mt-6">
        <div class="grid grid-cols-1 sm:hidden">
            <x-form.select
                id="group-tab-selector"
                name="group_tab_selector"
                label="Select a tab"
                labelClass="sr-only"
                :value="route('developer.groups.show', ['group' => $group->ulid, 'tab' => $activeTab])"
                :options="collect($tabs)->mapWithKeys(fn ($label, $key) => [route('developer.groups.show', ['group' => $group->ulid, 'tab' => $key]) => $label])->toArray()"
                containerClass="col-start-1 row-start-1"
                class="w-full appearance-none py-2 text-base focus:outline-indigo-600"
                aria-label="Select a tab"
                onchange="window.location.href = this.value"
            />
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
                message="Group identity"
                details="Primary identifiers and ownership details for this group."
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
                message="Group settings"
                details="Current mutable settings and capacity controls."
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
                        'label' => 'Follow Limit',
                        'value' => $group->follow_limit,
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
                        'label' => 'Follow Count',
                        'value' => $group->follows->count() . ' / ' . $group->follow_limit,
                    ],
                ]"
            />

            <x-model-viewer
                message="Metadata"
                details="Group record timestamps for creation and most recent update."
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

        <div class="mt-8">
            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Season policy settings</h2>
                    <p class="mt-1 text-sm text-gray-600">Optional prediction policies currently enabled per followed season.</p>
                </div>

                @if ($enabledGroupRulesBySeason === [])
                    <p class="text-sm text-gray-500">No followed seasons are configured for this group.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($enabledGroupRulesBySeason as $seasonRuleSet)
                            <div class="rounded-md border border-gray-200 p-4">
                                <p class="text-sm font-semibold text-gray-900">{{ $seasonRuleSet['season_name'] }}</p>

                                @if ($seasonRuleSet['rules'] === [])
                                    <p class="mt-2 text-sm text-gray-500">No optional group-level rules enabled for this season.</p>
                                @else
                                    <div class="mt-3 space-y-3">
                                        @foreach ($seasonRuleSet['rules'] as $rule)
                                            <div class="rounded-md border border-gray-200 p-4">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-900">{{ $rule->label() }}</p>
                                                        <p class="text-xs text-gray-500">{{ $rule->key() }}</p>
                                                    </div>
                                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-900">{{ $rule->scope()->value }}</span>
                                                </div>
                                                <p class="mt-3 text-sm text-gray-700">{{ $rule->description() }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Followed teams</h2>
                    <p class="mt-1 text-sm text-gray-600">The teams this group currently follows for predictions.</p>
                </div>

                <div class="mb-3 text-xs text-gray-500">
                    {{ $group->follows->count() }} of {{ $group->follow_limit }} follow slots used.
                </div>

                @if ($group->follows->isEmpty())
                    <p class="text-sm text-gray-500">No teams followed yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($group->follows as $follow)
                            <div class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $follow->team->display_name }}</p>
                                </div>
                                <form
                                    method="POST"
                                    action="{{ route('developer.groups.follow.destroy', [$group, $follow]) }}"
                                    class="ml-4 shrink-0"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <x-buttons.danger-button type="submit" confirm="Are you sure you want to unfollow this team?">
                                        Unfollow
                                    </x-buttons.danger-button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    @endif

    @if ($activeTab === 'members')
        <div class="mt-8">
            <div class="mb-4">
                <h2 class="text-lg font-semibold">Members</h2>
            </div>
            <x-tables.full-width
                heading="Members"
                description="Includes a dedicated view-as-user action for reproducing member-specific issues."
                :headers="['User', 'Role', 'Status', 'Joined', 'Actions']"
                :rows="$members"
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
                        'label' => 'View Group As User',
                        'type' => 'form',
                        'route' => 'developer.impersonation.start',
                        'routeParams' => ['user' => 'user.ulid', 'group' => $group->ulid],
                        'confirm' => 'Switch this browser session to this member and open this group?'
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

            <div class="mt-4 px-4 sm:px-6 lg:px-8">
                {{ $members?->links() }}
            </div>
        </div>
    @endif

    @if ($activeTab === 'players')
        <div class="mt-8">
            <h2 class="mb-4 text-lg font-semibold">Players</h2>
            <x-tables.full-width
                heading="Players"
                description="All group players with member ownership context."
                :headers="['Player Name', 'Member', 'Created', 'Actions']"
                :rows="$players"
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

            <div class="mt-4 px-4 sm:px-6 lg:px-8">
                {{ $players?->links() }}
            </div>
        </div>
    @endif

    @if ($activeTab === 'upcoming-games')
        <div class="mt-8">
            <h2 class="mb-4 text-lg font-semibold">Prediction feed</h2>

            <form method="GET" action="{{ route('developer.groups.show', $group) }}" class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <input type="hidden" name="tab" value="upcoming-games">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-inputs.select
                        id="prediction_player_filter"
                        name="player"
                        label="Player"
                        :value="$predictionFeedFilters['player']"
                        :options="$predictionFeedFilterOptions['players']"
                        placeholder="All players"
                    />

                    <x-inputs.select
                        id="prediction_member_filter"
                        name="member"
                        label="Member"
                        :value="$predictionFeedFilters['member']"
                        :options="$predictionFeedFilterOptions['members']"
                        placeholder="All members"
                    />

                    <div class="flex items-end gap-2">
                        <x-buttons.primary-button type="submit">Apply filters</x-buttons.primary-button>
                        <x-buttons.nav-button route="developer.groups.show" :params="['group' => $group->ulid, 'tab' => 'upcoming-games']">Clear</x-buttons.nav-button>
                    </div>
                </div>
            </form>

            <x-tables.full-width
                heading="Predictions"
                description="Recent prediction submissions for this group."
                :headers="['Player', 'Member', 'Game', 'Prediction', 'Submitted', 'Actions']"
                :rows="$predictions"
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
                        'route' => 'developer.groups.members.players.predictions.edit',
                        'routeParams' => ['group' => $group->ulid, 'member' => 'player.member.ulid', 'player' => 'player.ulid', 'prediction' => 'ulid'],
                    ],
                    [
                        'label' => 'Delete',
                        'type' => 'form',
                        'route' => 'developer.groups.members.players.predictions.destroy',
                        'routeParams' => ['group' => $group->ulid, 'member' => 'player.member.ulid', 'player' => 'player.ulid', 'prediction' => 'ulid'],
                        'confirm' => 'Are you sure you want to delete this prediction?'
                    ]
                ]"
            ></x-tables.full-width>

            <div class="mt-6 px-4 sm:px-6 lg:px-8">
                {{ $predictions->links() }}
            </div>
        </div>
    @endif

    @if ($activeTab === 'leaderboard')
        <div class="mt-8">
            @include('developer.groups.partials.season-results-debug', ['resultsMode' => 'leaderboard'])
        </div>
    @endif

    @if ($activeTab === 'raw-prediction-data')
        <div class="mt-8">
            @include('developer.groups.partials.season-results-debug', ['resultsMode' => 'raw-prediction-data'])
        </div>
    @endif
</x-layouts.app>
