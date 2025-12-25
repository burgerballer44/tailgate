<x-layouts.app
    mainHeading="Edit Team"
    mainDescription="Update team information."
    :mainActions="[
        ['text' => 'Back to Teams', 'route' => 'teams.index'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Teams', 'url' => route('teams.index')],
            ['text' => $team->designation, 'url' => route('teams.show', $team)],
            ['text' => 'Edit Team', 'active' => true],
        ]"
    />
    <x-form.admin.team
        :action="route('teams.update', $team)"
        :method="'PUT'"
        :team="$team"
        :sports="$sports"
        :types="$types"
    ></x-form.admin.team>
</x-layouts.app>
