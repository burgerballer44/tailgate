<x-layouts.app
    mainHeading="Edit Game for {!! $season->name !!}"
    mainDescription="Update the details of this game."
    :mainActions="[
        ['text' => 'Back to Games', 'route' => 'seasons.games.index', 'params' => ['season' => $season]],
    ]"
>
    <x-form.admin.game
        :action="route('seasons.games.update', ['season' => $season, 'game' => $game])"
        :method="'PUT'"
        :game="$game"
        :season="$season"
        :teams="$teams"
    />
</x-layouts.app>
