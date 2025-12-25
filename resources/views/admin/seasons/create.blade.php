<x-layouts.app
    mainHeading="Create Season"
    mainDescription="Add a new season."
    :mainActions="[
        ['text' => 'Back to Seasons', 'route' => 'admin.seasons.index'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('admin.seasons.index')],
            ['text' => 'Create Season', 'active' => true],
        ]"
    />
    <x-form.admin.season
        :action="route('admin.seasons.store')"
        :method="'POST'"
        :season="null"
        :sports="$sports"
        :seasonTypes="$seasonTypes"
    />
</x-layouts.app>
