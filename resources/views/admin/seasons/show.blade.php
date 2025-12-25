<x-layouts.app
    mainHeading="Season: {!! $season->name !!}"
    mainDescription="Details for season including name, sport, season type, and dates."
    :mainActions="[
        ['text' => 'Back to Seasons', 'route' => 'seasons.index'],
        ['text' => 'Edit Season', 'route' => 'seasons.edit', 'params' => ['season' => $season]],
        ['text' => 'Add Game', 'route' => 'seasons.games.create', 'params' => ['season' => $season]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('seasons.index')],
            ['text' => $season->name, 'active' => true],
        ]"
    />
    <x-model-viewer
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
                'label' => 'Season Start',
                'value' => $season->season_start,
            ],
            [
                'label' => 'Season End',
                'value' => $season->season_end,
            ],
            [
                'label' => 'Active',
                'value' => $season->active ? 'Yes' : 'No',
            ],
            [
                'label' => 'Active Date',
                'value' => $season->active_date->format('F j, Y, g:i a'),
            ],
            [
                'label' => 'Inactive Date',
                'value' => $season->inactive_date->format('F j, Y, g:i a'),
            ],
            [
                'label' => 'Created At',
                'value' => $season->created_at->format('F j, Y, g:i a'),
            ],
            [
                'label' => 'Updated At',
                'value' => $season->updated_at->format('F j, Y, g:i a'),
            ],
        ]"
    />

    <div class="mt-8">
        <x-tables.full-width
            heading="Games"
            description="A list of all the games for this season including teams, scores, and date/time."
            :tableActions="[
                ['route' => 'seasons.games.create', 'routeParams' => ['season' => $season], 'text' => 'Add Game'],
                ['route' => 'seasons.games.index', 'routeParams' => ['season' => $season], 'text' => 'View All Games']
            ]"
            :headers="['Home Team', 'Away Team', 'Home Score', 'Away Score', 'Date', 'Time', 'Actions']"
            :rows="$games"
            :columns="['homeTeam.organization', 'awayTeam.organization', 'home_team_score', 'away_team_score', 'start_date', 'start_time']"
            :rowActions="[
                [
                    'label' => 'Show',
                    'route' => 'seasons.games.show',
                    'routeParams' => ['season' => $season->ulid, 'game' => 'ulid'],
                ],
                [
                    'label' => 'Edit',
                    'route' => 'seasons.games.edit',
                    'routeParams' => ['season' => $season->ulid, 'game' => 'ulid'],
                ],
                [
                    'label' => 'Delete',
                    'type' => 'form',
                    'route' => 'seasons.games.destroy',
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
