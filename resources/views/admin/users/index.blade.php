<x-layouts.app
    mainHeading="Users"
    mainDescription="A list of all the users including their name, title, email and role."
    :mainActions="[
        ['text' => 'Add User', 'route' => 'users.create'],
    ]"
>
    {{-- query --}}
    <x-form.query-filters>
        <x-form.query-search label="Search by name or email" :error="$errors->get('q')" />

        <x-form.select
            name="status"
            label="Status"
            :value="old('status', request()->input('status'))"
            placeholder="All Statuses"
            :options="$statuses->mapWithKeys(fn($status) => [$status => ucfirst($status)])->toArray()"
        />

        <x-form.select
            name="role"
            label="Role"
            :value="old('role', request()->input('role'))"
            placeholder="All Roles"
            :options="$roles->mapWithKeys(fn($role) => [$role => ucfirst($role)])->toArray()"
        />
    </x-form.query-filters>

    {{-- table --}}
    <x-tables.full-width
        heading="Users"
        description="A list of all the users including their name, title, email and role."
        :tableActions="[
            ['route' => 'users.create', 'text' => 'Add User']
        ]"
        :headers="['Name', 'Email', 'Status', 'Role', 'Created', 'Actions']"
        :rows="$users"
        :columns="['name', 'email', 'status', 'role', 'created_at']"
        :rowActions="[
            [
                'label' => 'Show',
                'route' => 'users.show',
                'routeParams' => ['user' => 'ulid'],
            ],
            [
                'label' => 'Edit',
                'route' => 'users.edit',
                'routeParams' => ['user' => 'ulid'],
            ],
            [
                'label' => 'Delete',
                'type' => 'form',
                'route' => 'users.destroy',
                'routeParams' => ['user' => 'ulid'],
                'confirm' => 'Are you sure you want to delete this user?'
            ]
        ]"
    ></x-tables.full-width>
</x-layouts.app>
