<x-layouts.app
    mainHeading="Members of {{ $group->name }}"
    mainDescription="A list of all the members in this group."
    :mainActions="[
        ['text' => 'Add Member', 'route' => 'developer.groups.members.create', 'params' => ['group' => $group->ulid]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'url' => route('developer.groups.show', $group)],
            ['text' => 'Members', 'active' => true],
        ]"
    />

    {{-- table --}}
    <x-tables.full-width
        heading="Members"
        description="A list of all the members in this group."
        :tableActions="[
            ['route' => 'developer.groups.members.create', 'params' => ['group' => $group->ulid], 'text' => 'Add Member']
        ]"
        :headers="['User', 'Role', 'Status', 'Joined', 'Actions']"
        :rows="$members"
        :columns="['user.name', 'role', 'status', 'created_at']"
        :rowActions="[
            [
                'label' => 'Show',
                'route' => 'developer.groups.members.show',
                'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
            ],
            [
                'label' => 'Edit',
                'route' => 'developer.groups.members.edit',
                'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
            ],
            [
                'label' => 'View Group As User',
                'type' => 'form',
                'route' => 'developer.impersonation.start',
                'routeParams' => ['user' => 'user.ulid', 'group' => $group->ulid],
                'confirm' => 'Switch this browser session to this member and open this group?'
            ],
            [
                'label' => 'Delete',
                'type' => 'form',
                'route' => 'developer.groups.members.destroy',
                'routeParams' => ['group' => $group->ulid, 'member' => 'ulid'],
                'confirm' => 'Are you sure you want to delete this member?'
            ]
        ]"
    ></x-tables.full-width>

    <div class="mt-4">
        {{ $members->links() }}
    </div>
</x-layouts.app>
