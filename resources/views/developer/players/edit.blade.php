<x-layouts.app mainHeading="Edit Player" mainDescription="Edit the player details.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'url' => route('developer.groups.show', $group)],
            ['text' => 'Members', 'url' => route('developer.groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('developer.groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('developer.groups.members.players.index', [$group, $member])],
            ['text' => $player->player_name, 'url' => route('developer.groups.members.players.show', [$group, $member, $player])],
            ['text' => 'Edit', 'active' => true],
        ]"
    />

    <x-form.developer.player
        :player="$player"
        :group="$group"
        :member="$member"
        :action="route('developer.groups.members.players.update', [$group, $member, $player])"
        :method="'PUT'"
    />
</x-layouts.app>
