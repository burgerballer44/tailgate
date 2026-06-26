<x-groups.section-card overflowClass="overflow-visible" title="My players" description="Manage your players.">
    @if ($playerCount > 0)
        <div class="space-y-4">
            <x-tables.full-width
                :headers="['Player Name', 'Created', 'Actions']"
                :rows="$memberPlayers"
                :columns="[
                    'player_name',
                    fn ($row) => $row->created_at?->format('M d, Y'),
                ]"
                :rowActions="[
                    [
                        'label' => 'Edit',
                        'route' => 'groups.members.players.edit',
                        'routeParams' => ['group' => $group, 'member' => $currentMember, 'player' => 'ulid'],
                    ],
                    [
                        'label' => 'Delete',
                        'type' => 'form',
                        'route' => 'groups.members.players.destroy',
                        'routeParams' => ['group' => $group, 'member' => $currentMember, 'player' => 'ulid'],
                        'confirm' => 'Are you sure you want to delete this player?',
                    ],
                ]"
                emptyTitle="No players match this filter"
                emptyDescription="Try a different search term."
            />

            @if ($playerCount < $regularMemberPlayerLimit)
                <div class="flex justify-end">
                    <x-buttons.nav-button
                        route="groups.members.players.create"
                        :params="['group' => $group, 'member' => $currentMember]"
                    >
                        Create player
                    </x-buttons.nav-button>
                </div>
            @endif
        </div>
    @else
        <x-empty-state
            title="No players yet"
            description="Create a player to start submitting score predictions."
            buttonText="Create player"
            buttonRoute="groups.members.players.create"
            :buttonParams="['group' => $group, 'member' => $currentMember]"
        />
    @endif
</x-groups.section-card>
