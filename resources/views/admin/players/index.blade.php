<x-layouts.app
    mainHeading="Players of {{ $member->user?->name ?? 'Unknown' }}"
    mainDescription="A list of all the players for this member."
    :mainActions="[
        ['text' => 'Add Player', 'route' => 'admin.groups.members.players.create', 'params' => ['group' => $group->ulid, 'member' => $member->ulid]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Members', 'url' => route('admin.groups.members.index', $group)],
            ['text' => $member->user?->name ?? 'Unknown', 'url' => route('admin.groups.members.show', [$group, $member])],
            ['text' => 'Players', 'active' => true],
        ]"
    />

    {{-- table --}}
    <x-tables.full-width
        heading="Players"
        description="A list of all the players for this member."
        :tableActions="[
            ['route' => 'admin.groups.members.players.create', 'params' => ['group' => $group->ulid, 'member' => $member->ulid], 'text' => 'Add Player']
        ]"
        :headers="['Player Name', 'Created', 'Actions']"
        :rows="$players"
        :columns="['player_name', 'created_at']"
        :rowActions="[
            [
                'label' => 'Show',
                'route' => 'admin.groups.members.players.show',
                'routeParams' => ['group' => $group->ulid, 'member' => $member->ulid, 'player' => 'ulid'],
            ],
            [
                'label' => 'Edit',
                'route' => 'admin.groups.members.players.edit',
                'routeParams' => ['group' => $group->ulid, 'member' => $member->ulid, 'player' => 'ulid'],
            ],
            [
                'label' => 'Delete',
                'type' => 'form',
                'route' => 'admin.groups.members.players.destroy',
                'routeParams' => ['group' => $group->ulid, 'member' => $member->ulid, 'player' => 'ulid'],
                'confirm' => 'Are you sure you want to delete this player?'
            ]
        ]"
    ></x-tables.full-width>

    <div class="mt-4">
        {{ $players->links() }}
    </div>
</x-layouts.app>
