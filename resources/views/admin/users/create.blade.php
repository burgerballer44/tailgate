<x-layouts.app
    mainHeading="Create User"
    mainDescription="Add a new user."
    :mainActions="[
        ['text' => 'Back to Users', 'route' => 'users.index'],
    ]"
>
    <x-form.admin.user
        :action="route('users.store')"
        :method="'POST'"
        :user="null"
        :statuses="$statuses"
        :roles="$roles"
    />
</x-layouts.app>
