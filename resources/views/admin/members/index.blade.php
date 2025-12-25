<x-layouts.app
    mainHeading="Members of {{ $group->name }}"
    mainDescription="A list of all the members in this group."
    :mainActions="[
        ['text' => 'Add Member', 'route' => 'admin.groups.members.create', 'params' => ['group' => $group->ulid]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Members', 'active' => true],
        ]"
    />

    {{-- table --}}
    <x-tables.full-width
        heading="Members"
        description="A list of all the members in this group."
        :tableActions="[
            ['route' => 'admin.groups.members.create', 'params' => ['group' => $group->ulid], 'text' => 'Add Member']
        ]"
        :headers="['User', 'Role', 'Joined', 'Actions']"
        :rows="$members"
        :columns="['user.name', 'role', 'created_at']"
        :rowActions="[
            [
                'label' => 'Show',
                'route' => 'admin.groups.members.show',
                'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
            ],
            [
                'label' => 'Edit',
                'route' => 'admin.groups.members.edit',
                'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
            ],
            [
                'label' => 'Delete',
                'type' => 'form',
                'route' => 'admin.groups.members.destroy',
                'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
                'confirm' => 'Are you sure you want to delete this member?'
            ]
        ]"
    ></x-tables.full-width>

    <div class="mt-4">
        {{ $members->links() }}
    </div>
</x-layouts.app>
