<x-layouts.app
    mainHeading="Users"
    mainDescription="A list of all the users including their name, title, email and role."
    :mainActions="[
        ['text' => 'Add User', 'route' => 'users.create'],
    ]"
>
    <x-tables.full-width
        heading="Users"
        description="A list of all the users including their name, title, email and role."
        :tableActions="[
            ['route' => 'users.create', 'text' => 'Add User']
        ]"
        :headers="['Name', 'Email', 'Status', 'Role']"
        :rows="$users"
        :columns="['name','email','status','role']"
        :rowActions="[
            [
                'label' => 'Edit',
                'route' => 'users.edit',
                'routeParams' => ['user' => 'id'],
                // 'permission' => 'update', 
            ],
            [
                'label' => 'Delete',
                'route' => 'users.destroy',
                'routeParams' => ['user' => 'id'],
                // 'permission' => 'delete', 
                'confirm' => 'Are you sure you want to delete this user?'
            ]
        ]"
    ></x-tables.full-width>
</x-layouts.app>
