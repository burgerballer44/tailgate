<x-layouts.app
    mainHeading="Edit Team"
    mainDescription="Update team information."
    :mainActions="[
        ['text' => 'Back to Teams', 'route' => 'developer.teams.index'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Teams', 'url' => route('developer.teams.index')],
            ['text' => $team->designation, 'url' => route('developer.teams.show', $team)],
            ['text' => 'Edit Team', 'active' => true],
        ]"
    />
    <x-form.developer.team
        :action="route('developer.teams.update', $team)"
        :method="'PUT'"
        :team="$team"
        :sports="$sports"
        :types="$types"
    ></x-form.developer.team>
</x-layouts.app>
