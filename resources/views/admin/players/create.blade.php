<x-layouts.app mainHeading="Add Player to {{ $member->user->name }}" mainDescription="Add a new player to the member.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Members', 'url' => route('admin.groups.members.index', $group)],
            ['text' => $member->user->name, 'url' => route('admin.groups.members.show', [$group, $member])],
            ['text' => 'Players', 'url' => route('admin.groups.members.players.index', [$group, $member])],
            ['text' => 'Add Player', 'active' => true],
        ]"
    />

    <x-form.admin.player
        :player="null"
        :group="$group"
        :member="$member"
        :action="route('admin.groups.members.players.store', [$group, $member])"
        :method="'POST'"
    />
</x-layouts.app>
