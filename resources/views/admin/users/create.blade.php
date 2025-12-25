<x-layouts.app
    mainHeading="Create User"
    mainDescription="Add a new user."
    :mainActions="[
        ['text' => 'Back to Users', 'route' => 'admin.users.index'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Users', 'url' => route('admin.users.index')],
            ['text' => 'Create User', 'active' => true],
        ]"
    />
    <x-form.admin.user
        :action="route('admin.users.store')"
        :method="'POST'"
        :user="null"
        :statuses="$statuses"
        :roles="$roles"
    />
</x-layouts.app>
