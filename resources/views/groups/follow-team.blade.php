<x-layouts.app mainHeading="Follow Team for {{ $group->name }}" mainDescription="Choose a team to follow.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => $group->name, 'url' => route('groups.show', $group)],
            ['text' => 'Follow Team', 'active' => true],
        ]"
    />

    <x-form.follow-team
        :group="$group"
        :teams="$teams"
        :action="route('groups.follow-team', $group)"
        :method="'POST'"
    />
</x-layouts.app>
