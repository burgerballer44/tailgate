<x-layouts.app
    mainHeading="Create User"
    mainDescription="Add a new user."
    :mainActions="[
        ['text' => 'Back to Users', 'route' => 'developer.users.index'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Users', 'url' => route('developer.users.index')],
            ['text' => 'Create User', 'active' => true],
        ]"
    />
    <x-form.developer.user
        :action="route('developer.users.store')"
        :method="'POST'"
        :user="null"
        :statuses="$statuses"
        :roles="$roles"
    />
</x-layouts.app>
