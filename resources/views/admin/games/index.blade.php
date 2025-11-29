<x-layouts.app
    mainHeading="Games for {!! $season->name !!}"
    mainDescription="A list of all the games for this season including teams, scores, and date/time."
    :mainActions="[
        ['text' => 'Back to Season', 'route' => 'seasons.show', 'params' => ['season' => $season]],
        ['text' => 'Add Game', 'route' => 'seasons.games.create', 'params' => ['season' => $season]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('seasons.index')],
            ['text' => $season->name, 'url' => route('seasons.show', $season)],
            ['text' => 'Games', 'active' => true],
        ]"
    />
    {{-- table --}}
    <x-tables.full-width
        heading="Games"
        description="A list of all the games for this season including teams, scores, and date/time."
        :tableActions="[
            ['route' => 'seasons.games.create', 'routeParams' => ['season' => $season], 'text' => 'Add Game']
        ]"
        :headers="['Home Team', 'Away Team', 'Home Score', 'Away Score', 'Date', 'Time', 'Actions']"
        :rows="$games"
        :columns="['homeTeam.full_name', 'awayTeam.full_name', 'home_team_score', 'away_team_score', 'start_date', 'start_time']"
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
</x-layouts.app>
