<x-layouts.app
    mainHeading="Game Details for {!! $season->name !!}"
    mainDescription="Details for this game including teams, scores, and date/time."
    :mainActions="[
        ['text' => 'Back to Games', 'route' => 'seasons.games.index', 'params' => ['season' => $season]],
        ['text' => 'Back to Season', 'route' => 'seasons.show', 'params' => ['season' => $season]],
        ['text' => 'Edit Game', 'route' => 'seasons.games.edit', 'params' => ['season' => $season, 'game' => $game]],
    ]"
>
    <x-model-viewer
        :fields="[
            [
                'label' => 'Home Team',
                'value' => $game->homeTeam->full_name,
            ],
            [
                'label' => 'Away Team',
                'value' => $game->awayTeam->full_name,
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
