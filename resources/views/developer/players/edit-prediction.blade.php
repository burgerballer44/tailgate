<x-layouts.app mainHeading="Edit Prediction" mainDescription="Edit the prediction.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'url' => route('developer.groups.show', $group)],
            ['text' => 'Members', 'url' => route('developer.groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('developer.groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('developer.groups.members.players.index', [$group, $member])],
            ['text' => $player->player_name, 'url' => route('developer.groups.members.players.show', [$group, $member, $player])],
            ['text' => 'Edit Prediction', 'active' => true],
        ]"
    />

    <x-form.developer.prediction
        :prediction="$prediction"
        :player="$player"
        :group="$group"
        :member="$member"
        :games="collect()"
        :action="route('developer.groups.members.players.predictions.update', [$group, $member, $player, $prediction])"
        :method="'PUT'"
    />
</x-layouts.app>