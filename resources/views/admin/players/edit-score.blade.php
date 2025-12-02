<x-layouts.app mainHeading="Edit Score" mainDescription="Edit the score prediction.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('groups.index')],
            ['text' => $group->name, 'url' => route('groups.show', $group)],
            ['text' => 'Members', 'url' => route('groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('groups.members.players.index', [$group, $member])],
            ['text' => $player->player_name, 'url' => route('groups.members.players.show', [$group, $member, $player])],
            ['text' => 'Edit Score', 'active' => true],
        ]"
    />

    <x-form.admin.score
        :score="$score"
        :player="$player"
        :group="$group"
        :member="$member"
        :games="collect()"
        :action="route('groups.members.players.scores.update', [$group, $member, $player, $score])"
        :method="'PUT'"
    />
</x-layouts.app>
