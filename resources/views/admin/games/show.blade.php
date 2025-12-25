<x-layouts.app
    mainHeading="Game Details for {!! $season->name !!}"
    mainDescription="Details for this game including teams, scores, and date/time."
    :mainActions="[
        ['text' => 'Back to Games', 'route' => 'admin.seasons.games.index', 'params' => ['season' => $season]],
        ['text' => 'Back to Season', 'route' => 'admin.seasons.show', 'params' => ['season' => $season]],
        ['text' => 'Edit Game', 'route' => 'admin.seasons.games.edit', 'params' => ['season' => $season, 'game' => $game]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('admin.seasons.index')],
            ['text' => $season->name, 'url' => route('admin.seasons.show', $season)],
            ['text' => 'Games', 'url' => route('admin.seasons.games.index', $season)],
            ['text' => 'Game Details', 'active' => true],
        ]"
    />
    <x-model-viewer
        :fields="[
            [
                'label' => 'Home Team',
                'value' => $game->homeTeam->organization,
            ],
            [
                'label' => 'Away Team',
                'value' => $game->awayTeam->organization,
            ],
            [
                'label' => 'Home Team Score',
                'value' => $game->home_team_score ?? 'Not set',
            ],
            [
                'label' => 'Away Team Score',
                'value' => $game->away_team_score ?? 'Not set',
            ],
            [
                'label' => 'Start Date',
                'value' => $game->start_date,
            ],
            [
                'label' => 'Start Time',
                'value' => $game->start_time,
            ],
            [
                'label' => 'Created At',
                'value' => $game->created_at->format('F j, Y, g:i a'),
            ],
            [
                'label' => 'Updated At',
                'value' => $game->updated_at->format('F j, Y, g:i a'),
            ],
        ]"
    />
</x-layouts.app>
