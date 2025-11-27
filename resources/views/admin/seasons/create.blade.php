<x-layouts.app
    mainHeading="Create Season"
    mainDescription="Add a new season."
    :mainActions="[
        ['text' => 'Back to Seasons', 'route' => 'seasons.index'],
    ]"
>
    <x-form.admin.season
        :action="route('seasons.store')"
        :method="'POST'"
        :season="null"
        :sports="$sports"
        :seasonTypes="$seasonTypes"
    />
</x-layouts.app>
