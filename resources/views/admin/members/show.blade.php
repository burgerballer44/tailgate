<x-layouts.app
    mainHeading="Member: {{ $member->user?->name ?? 'Unknown' }}"
    mainDescription="Details of the member including players."
    :mainActions="[
        ['text' => 'Edit Member', 'route' => 'admin.groups.members.edit', 'params' => ['group' => $group->ulid, 'member' => $member->ulid]],
        ['text' => 'Add Player', 'route' => 'admin.groups.members.players.create', 'params' => ['group' => $group->ulid, 'member' => $member->ulid]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Members', 'url' => route('admin.groups.members.index', $group)],
            ['text' => $member->user?->name ?? 'Unknown', 'active' => true],
        ]"
    />

    <x-model-viewer
        :fields="[
            [
                'label' => 'User',
                'value' => $member->user?->name ?? 'Unknown',
            ],
            [
                'label' => 'Role',
                'value' => $member->role,
            ],
            [
                'label' => 'Joined',
                'value' => $member->created_at?->format('F j, Y, g:i a') ?? 'N/A',
            ],
        ]"
    />

    {{-- Players Section --}}
    <div class="mt-8">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Players</h2>
            <a href="{{ route('admin.groups.members.players.create', [$group, $member]) }}" class="btn btn-primary">
                Add Player
            </a>
        </div>
        <x-tables.full-width
            heading="Players"
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
    </div>
</x-layouts.app>
