<x-layouts.app mainHeading="Add Player to {{ $member->user->name }}" mainDescription="Add a new player to the member.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('groups.index')],
            ['text' => $group->name, 'url' => route('groups.show', $group)],
            ['text' => 'Members', 'url' => route('groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('groups.members.players.index', [$group, $member])],
            ['text' => 'Add Player', 'active' => true],
        ]"
    />

    <x-form.admin.player
        :player="null"
        :group="$group"
        :member="$member"
        :action="route('groups.members.players.store', [$group, $member])"
        :method="'POST'"
    />
</x-layouts.app>
