<x-layouts.app
    mainHeading="Create Game for {!! $season->name !!}"
    mainDescription="Add a new game to this season."
    :mainActions="[
        ['text' => 'Back to Games', 'route' => 'seasons.games.index', 'params' => ['season' => $season]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('seasons.index')],
            ['text' => $season->name, 'url' => route('seasons.show', $season)],
            ['text' => 'Games', 'url' => route('seasons.games.index', $season)],
            ['text' => 'Create Game', 'active' => true],
        ]"
    />
    <x-form.admin.game
        :action="route('seasons.games.store', $season)"
        :method="'POST'"
        :game="null"
        :season="$season"
        :teams="$teams"
    />
</x-layouts.app>
