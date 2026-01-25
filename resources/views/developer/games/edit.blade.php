<x-layouts.app
    mainHeading="Edit Game for {!! $season->name !!}"
    mainDescription="Update the details of this game."
    :mainActions="[
        ['text' => 'Back to Games', 'route' => 'developer.seasons.games.index', 'params' => ['season' => $season]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('developer.seasons.index')],
            ['text' => $season->name, 'url' => route('developer.seasons.show', $season)],
            ['text' => 'Games', 'url' => route('developer.seasons.games.index', $season)],
            ['text' => 'Edit Game', 'active' => true],
        ]"
    />
    <x-form.developer.game
        :action="route('developer.seasons.games.update', ['season' => $season, 'game' => $game])"
        :method="'PUT'"
        :game="$game"
        :season="$season"
        :teams="$teams"
    />
</x-layouts.app>
