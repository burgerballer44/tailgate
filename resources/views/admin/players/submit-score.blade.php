<x-layouts.app
    mainHeading="Submit Score for {{ $player->player_name }}"
    mainDescription="Submit a score prediction for a game."
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Members', 'url' => route('admin.groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('admin.groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('admin.groups.members.players.index', [$group, $member])],
            ['text' => $player->player_name, 'url' => route('admin.groups.members.players.show', [$group, $member, $player])],
            ['text' => 'Submit Score', 'active' => true],
        ]"
    />

    <x-form.admin.score
        :score="null"
        :player="$player"
        :group="$group"
        :member="$member"
        :games="$games"
        :action="route('admin.groups.members.players.submit-score', [$group, $member, $player])"
        :method="'POST'"
    />
</x-layouts.app>
