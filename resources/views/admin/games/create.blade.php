<x-layouts.app
    mainHeading="Create Game for {!! $season->name !!}"
    mainDescription="Add a new game to this season."
    :mainActions="[
        ['text' => 'Back to Games', 'route' => 'seasons.games.index', 'params' => ['season' => $season]],
    ]"
>
    <x-form.admin.game
        :action="route('seasons.games.store', $season)"
        :method="'POST'"
        :game="null"
        :season="$season"
        :teams="$teams"
    />
</x-layouts.app>
