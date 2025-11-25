<x-layouts.app
    mainHeading="Edit Team"
    mainDescription="Update team information."
    :mainActions="[
        ['text' => 'Back to Teams', 'route' => 'teams.index'],
    ]"
>
    <x-form.admin.team
        :action="route('teams.update', $team)"
        :method="'PUT'"
        :team="$team"
        :sports="$sports"
    ></x-form.admin.team>
</x-layouts.app>
