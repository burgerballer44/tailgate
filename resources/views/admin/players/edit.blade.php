<x-layouts.app mainHeading="Edit Player" mainDescription="Edit the player details.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('groups.index')],
            ['text' => $group->name, 'url' => route('groups.show', $group)],
            ['text' => 'Members', 'url' => route('groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('groups.members.players.index', [$group, $member])],
            ['text' => $player->player_name, 'url' => route('groups.members.players.show', [$group, $member, $player])],
            ['text' => 'Edit', 'active' => true],
        ]"
    />

    <x-form.admin.player
        :player="$player"
        :group="$group"
        :member="$member"
        :action="route('groups.members.players.update', [$group, $member, $player])"
        :method="'PUT'"
    />
</x-layouts.app>
