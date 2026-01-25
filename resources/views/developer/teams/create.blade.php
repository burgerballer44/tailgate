<x-layouts.app
    mainHeading="Create Team"
    mainDescription="Add a new team."
    :mainActions="[
        ['text' => 'Back to Teams', 'route' => 'developer.teams.index'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Teams', 'url' => route('developer.teams.index')],
            ['text' => 'Create Team', 'active' => true],
        ]"
    />
    <x-form.developer.team
        :action="route('developer.teams.store')"
        :method="'POST'"
        :team="null"
        :sports="$sports"
        :types="$types"
    ></x-form.developer.team>
</x-layouts.app>
