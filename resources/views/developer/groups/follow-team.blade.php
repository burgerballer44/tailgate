<x-layouts.app mainHeading="Follow Team for {{ $group->name }}" mainDescription="Choose a team and season to follow.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'url' => route('developer.groups.show', $group)],
            ['text' => 'Follow Team', 'active' => true],
        ]"
    />

    <x-form.developer.follow-team
        :group="$group"
        :teams="$teams"
        :seasons="$seasons"
        :action="route('developer.groups.follow-team', $group)"
        :method="'POST'"
    />
</x-layouts.app>
