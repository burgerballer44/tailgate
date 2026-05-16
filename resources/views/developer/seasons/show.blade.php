<x-layouts.app
    mainHeading="Season: {!! $season->name !!}"
    mainDescription="Details for season including name, sport, season type, and status."
    :mainActions="[
        ['text' => 'Back to Seasons', 'route' => 'developer.seasons.index'],
        ['text' => 'Edit Season', 'route' => 'developer.seasons.edit', 'params' => ['season' => $season]],
        ['text' => 'Import Games', 'route' => 'developer.seasons.import-games', 'params' => ['season' => $season]],
        ['text' => 'Add Game', 'route' => 'developer.seasons.games.create', 'params' => ['season' => $season]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('developer.seasons.index')],
            ['text' => $season->name, 'active' => true],
        ]"
    />

    @php
        $tabs = [
            'details' => 'Details',
            'games' => 'Games',
        ];
    @endphp

    <div class="mt-6">
        <div class="grid grid-cols-1 sm:hidden">
            <label for="season-tab-selector" class="sr-only">Select a tab</label>
            <select
                id="season-tab-selector"
                aria-label="Select a tab"
                class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-2 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600"
                onchange="window.location.href = this.value"
            >
                @foreach ($tabs as $key => $label)
                    <option
                        value="{{ route('developer.seasons.show', ['season' => $season->ulid, 'tab' => $key]) }}"
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
                            href="{{ route('developer.seasons.show', ['season' => $season->ulid, 'tab' => $key]) }}"
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
                message="Season identity"
                details="Core classification details for this season."
                tone="info"
                :fields="[
                    [
                        'label' => 'Name',
                        'value' => $season->name,
                    ],
                    [
                        'label' => 'Sport',
                        'value' => $season->sport_html_entity,
                    ],
                    [
                        'label' => 'Season Type',
                        'value' => $season->season_type,
                    ],
                    [
                        'label' => 'ULID',
                        'value' => $season->ulid,
                    ],
                    [
                        'label' => 'ID',
                        'value' => $season->id,
                    ],
                ]"
            />

            <x-model-viewer
                message="Activation state"
                details="Current active state for follow and feed behavior."
                tone="success"
                :fields="[
                    [
                        'label' => 'Active',
                        'value' => $season->active,
                    ],
                    [
                        'label' => 'Games Count',
                        'value' => $season->games_count,
                    ],
                ]"
            />

            <x-model-viewer
                message="Metadata"
                details="Record lifecycle context for this season."
                tone="neutral"
                :fields="[
                    [
                        'label' => 'Created At',
                        'value' => $season->created_at?->format('F j, Y, g:i a') ?? 'N/A',
                    ],
                    [
                        'label' => 'Updated At',
                        'value' => $season->updated_at?->format('F j, Y, g:i a') ?? 'N/A',
                    ],
                ]"
            />
        </div>
    @endif

    @if ($activeTab === 'games')
        <div class="mt-8">
            <x-form.query-filters>
                <input type="hidden" name="tab" value="games" />

                <x-form.select
                    name="home_team_id"
                    label="Home team"
                    :value="old('home_team_id', request()->input('home_team_id'))"
                    placeholder="All Home Teams"
                    :options="$teams"
                />

                <x-form.select
                    name="away_team_id"
                    label="Away team"
                    :value="old('away_team_id', request()->input('away_team_id'))"
                    placeholder="All Away Teams"
                    :options="$teams"
                />

                <x-form.select
                    name="start_time_tbd"
                    label="Start time finalized"
                    :value="old('start_time_tbd', request()->input('start_time_tbd'))"
                    :options="[
                        '' => 'All',
                        'false' => 'Finalized',
                        'true' => 'TBD',
                    ]"
                />
            </x-form.query-filters>

            <x-tables.full-width
                heading="Games"
                description="A list of all the games for this season including teams, scores, and date/time."
                :tableActions="[
                    ['route' => 'developer.seasons.import-games', 'routeParams' => ['season' => $season], 'text' => 'Import Games'],
                    ['route' => 'developer.seasons.games.create', 'routeParams' => ['season' => $season], 'text' => 'Add Game'],
                    ['route' => 'developer.seasons.games.index', 'routeParams' => ['season' => $season], 'text' => 'View All Games']
                ]"
                :headers="['Home Team', 'Away Team', 'Home Score', 'Away Score', 'Start Date Time', 'Start Time Finalized', 'Actions']"
                :rows="$games"
                :columns="[
                    'homeTeam.organization',
                    'awayTeam.organization',
                    'home_team_score',
                    'away_team_score',
                    fn ($row) => $row->start_date_time
                        ? rescue(fn () => \Illuminate\Support\Carbon::parse($row->start_date_time)->format('F j, Y, g:i a'), $row->start_date_time)
                        : null,
                    'start_time_tbd_html_entity',
                ]"
                :rowActions="[
                    [
                        'label' => 'Show',
                        'route' => 'developer.seasons.games.show',
                        'routeParams' => ['season' => $season->ulid, 'game' => 'ulid'],
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'developer.seasons.games.edit',
                        'routeParams' => ['season' => $season->ulid, 'game' => 'ulid'],
                    ],
                    [
                        'label' => 'Delete',
                        'type' => 'form',
                        'route' => 'developer.seasons.games.destroy',
                        'routeParams' => ['season' => $season->ulid, 'game' => 'ulid'],
                        'confirm' => 'Are you sure you want to delete this game?'
                    ]
                ]"
            ></x-tables.full-width>

            <div class="mt-4">
                {{ $games->links() }}
            </div>
        </div>
    @endif
</x-layouts.app>
