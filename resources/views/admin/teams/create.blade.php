<x-layouts.app
    mainHeading="Create Team"
    mainDescription="Add a new team."
    :mainActions="[
        ['text' => 'Back to Teams', 'route' => 'admin.teams.index'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Teams', 'url' => route('admin.teams.index')],
            ['text' => 'Create Team', 'active' => true],
        ]"
    />
    <x-form.admin.team
        :action="route('admin.teams.store')"
        :method="'POST'"
        :team="null"
        :sports="$sports"
        :types="$types"
    ></x-form.admin.team>
</x-layouts.app>
