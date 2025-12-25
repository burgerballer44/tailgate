<x-layouts.app
    mainHeading="Create Game for {!! $season->name !!}"
    mainDescription="Add a new game to this season."
    :mainActions="[
        ['text' => 'Back to Games', 'route' => 'admin.seasons.games.index', 'params' => ['season' => $season]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('admin.seasons.index')],
            ['text' => $season->name, 'url' => route('admin.seasons.show', $season)],
            ['text' => 'Games', 'url' => route('admin.seasons.games.index', $season)],
            ['text' => 'Create Game', 'active' => true],
        ]"
    />
    <x-form.admin.game
        :action="route('admin.seasons.games.store', $season)"
        :method="'POST'"
        :game="null"
        :season="$season"
        :teams="$teams"
    />
</x-layouts.app>
