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
                    'value' => $season->sport,
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
                    'value' => $season->games()->count(),
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

    <div class="mt-8">
        <x-form.query-filters>
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
                label="Start time status"
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
</x-layouts.app>
