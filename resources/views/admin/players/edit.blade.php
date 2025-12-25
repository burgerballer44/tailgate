<x-layouts.app mainHeading="Edit Player" mainDescription="Edit the player details.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Members', 'url' => route('admin.groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('admin.groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('admin.groups.members.players.index', [$group, $member])],
            ['text' => $player->player_name, 'url' => route('admin.groups.members.players.show', [$group, $member, $player])],
            ['text' => 'Edit', 'active' => true],
        ]"
    />

    <x-form.admin.player
        :player="$player"
        :group="$group"
        :member="$member"
        :action="route('admin.groups.members.players.update', [$group, $member, $player])"
        :method="'PUT'"
    />
</x-layouts.app>
