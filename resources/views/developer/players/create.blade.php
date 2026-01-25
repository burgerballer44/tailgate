<x-layouts.app mainHeading="Add Player to {{ $member->user->name }}" mainDescription="Add a new player to the member.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'url' => route('developer.groups.show', $group)],
            ['text' => 'Members', 'url' => route('developer.groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('developer.groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('developer.groups.members.players.index', [$group, $member])],
            ['text' => 'Add Player', 'active' => true],
        ]"
    />

    <x-form.developer.player
        :player="null"
        :group="$group"
        :member="$member"
        :action="route('developer.groups.members.players.store', [$group, $member])"
        :method="'POST'"
    />
</x-layouts.app>
