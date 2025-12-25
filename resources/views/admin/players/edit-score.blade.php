<x-layouts.app mainHeading="Edit Score" mainDescription="Edit the score prediction.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Members', 'url' => route('admin.groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('admin.groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('admin.groups.members.players.index', [$group, $member])],
            ['text' => $player->player_name, 'url' => route('admin.groups.members.players.show', [$group, $member, $player])],
            ['text' => 'Edit Score', 'active' => true],
        ]"
    />

    <x-form.admin.score
        :score="$score"
        :player="$player"
        :group="$group"
        :member="$member"
        :games="collect()"
        :action="route('admin.groups.members.players.scores.update', [$group, $member, $player, $score])"
        :method="'PUT'"
    />
</x-layouts.app>
