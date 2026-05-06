<x-layouts.app
    mainHeading="Users"
    mainDescription="A list of all the users including their name, title, email and role."
    :mainActions="[
        ['text' => 'Add User', 'route' => 'developer.users.create'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Users', 'active' => true],
        ]"
    />
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
        description="A list of all the users with basic information."
        :tableActions="[
            ['route' => 'developer.users.create', 'text' => 'Add User']
        ]"
        :headers="['Name', 'Status', 'Role', 'Verified', 'Last Login', 'Created', 'Actions']"
        :rows="$users"
            :columns="[
                'name',
                'status',
                'role',
                fn ($row) => $row->hasVerifiedEmail()
                    ? \App\Models\HtmlEntity::CHECK_MARK->character()
                    : \App\Models\HtmlEntity::RED_X->character(),
                fn ($row) => $row->last_login_at?->diffForHumans(),
                fn ($row) => $row->created_at->format('Y-M-d H:i:a'),
            ]"
        :rowActions="[
            [
                'label' => 'Show',
                'route' => 'developer.users.show',
                'routeParams' => ['user' => 'ulid'],
            ],
            [
                'label' => 'Edit',
                'route' => 'developer.users.edit',
                'routeParams' => ['user' => 'ulid'],
            ],
            [
                'label' => 'Delete',
                'type' => 'form',
                'route' => 'developer.users.destroy',
                'routeParams' => ['user' => 'ulid'],
                'confirm' => 'Are you sure you want to delete this user?'
            ]
        ]"
    ></x-tables.full-width>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</x-layouts.app>
