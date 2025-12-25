<x-layouts.app
    mainHeading="Player: {{ $player->player_name }}"
    mainDescription="Details of the player."
    :mainActions="[
        ['text' => 'Edit Player', 'route' => 'admin.groups.members.players.edit', 'params' => ['group' => $group->ulid, 'member' => $member->ulid, 'player' => $player->ulid]],
        ['text' => 'Submit Score', 'route' => 'admin.groups.members.players.submit-score.create', 'params' => ['group' => $group->ulid, 'member' => $member->ulid, 'player' => $player->ulid]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Members', 'url' => route('admin.groups.members.index', $group)],
            ['text' => $member->user?->name ?? 'Unknown', 'url' => route('admin.groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('admin.groups.members.players.index', [$group, $member])],
            ['text' => $player->player_name, 'active' => true],
        ]"
    />

    <x-model-viewer
        :fields="[
            [
                'label' => 'Player Name',
                'value' => $player->player_name,
            ],
            [
                'label' => 'Member',
                'value' => $player->member?->user?->name ?? 'Unknown',
            ],
            [
                'label' => 'Created At',
                'value' => $player->created_at?->format('F j, Y, g:i a') ?? 'N/A',
            ],
        ]"
    />

    {{-- Scores Section --}}
    <div class="mt-8">
        <h2 class="mb-4 text-lg font-semibold">Scores</h2>
        <x-tables.full-width
            heading="Scores"
            :headers="['Game', 'Home Prediction', 'Away Prediction', 'Submitted At', 'Actions']"
            :rows="$scores"
            :columns="['game.homeTeam.name . \' vs \' . game.awayTeam.name', 'home_team_prediction', 'away_team_prediction', 'created_at']"
            :rowActions="[
                [
                    'label' => 'Edit',
                    'route' => 'admin.groups.members.players.scores.edit',
                    'routeParams' => ['group' => $group->ulid, 'member' => $member->ulid, 'player' => $player->ulid, 'score' => 'ulid'],
                ],
                [
                    'label' => 'Delete',
                    'type' => 'form',
                    'route' => 'admin.groups.members.players.scores.destroy',
                    'routeParams' => ['group' => $group->ulid, 'member' => $member->ulid, 'player' => $player->ulid, 'score' => 'ulid'],
                    'confirm' => 'Are you sure you want to delete this score?'
                ]
            ]"
        ></x-tables.full-width>

        <div class="mt-4">
            {{ $scores->links() }}
        </div>
    </div>
</x-layouts.app>
