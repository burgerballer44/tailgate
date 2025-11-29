<x-layouts.app
    mainHeading="Create Season"
    mainDescription="Add a new season."
    :mainActions="[
        ['text' => 'Back to Seasons', 'route' => 'seasons.index'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('seasons.index')],
            ['text' => 'Create Season', 'active' => true],
        ]"
    />
    <x-form.admin.season
        :action="route('seasons.store')"
        :method="'POST'"
        :season="null"
        :sports="$sports"
        :seasonTypes="$seasonTypes"
    />
</x-layouts.app>
