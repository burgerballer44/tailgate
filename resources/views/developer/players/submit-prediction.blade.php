<x-layouts.app
    mainHeading="Submit Prediction for {{ $player->player_name }}"
    mainDescription="Submit a prediction for a game."
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'url' => route('developer.groups.show', $group)],
            ['text' => 'Members', 'url' => route('developer.groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('developer.groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('developer.groups.members.players.index', [$group, $member])],
            ['text' => $player->player_name, 'url' => route('developer.groups.members.players.show', [$group, $member, $player])],
            ['text' => 'Submit Prediction', 'active' => true],
        ]"
    />

    <x-form.developer.prediction
        :prediction="null"
        :player="$player"
        :group="$group"
        :member="$member"
        :games="$games"
        :action="route('developer.groups.members.players.submit-prediction', [$group, $member, $player])"
        :method="'POST'"
    />
</x-layouts.app>