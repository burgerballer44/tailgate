<x-layouts.app
    mainHeading="Create Team"
    mainDescription="Add a new team."
    :mainActions="[
        ['text' => 'Back to Teams', 'route' => 'teams.index'],
    ]"
>
    <x-form.admin.team
        :action="route('teams.store')"
        :method="'POST'"
        :team="null"
        :sports="$sports"
    ></x-form.admin.team>
</x-layouts.app>
