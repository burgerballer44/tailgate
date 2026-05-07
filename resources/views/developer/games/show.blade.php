<x-layouts.app
    mainHeading="Game Details for {!! $season->name !!}"
    mainDescription="Details for this game including teams, scores, and date/time."
    :mainActions="[
        ['text' => 'Back to Games', 'route' => 'developer.seasons.games.index', 'params' => ['season' => $season]],
        ['text' => 'Back to Season', 'route' => 'developer.seasons.show', 'params' => ['season' => $season]],
        ['text' => 'Edit Game', 'route' => 'developer.seasons.games.edit', 'params' => ['season' => $season, 'game' => $game]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('developer.seasons.index')],
            ['text' => $season->name, 'url' => route('developer.seasons.show', $season)],
            ['text' => 'Games', 'url' => route('developer.seasons.games.index', $season)],
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
                'label' => 'Start Date Time',
                'value' => $game->start_date_time,
            ],
            [
                'label' => 'Start Time TBD',
                'value' => ($game->start_time_tbd ? \App\Models\HtmlEntity::QUESTION_MARK : \App\Models\HtmlEntity::CHECK_MARK)->character(),
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
