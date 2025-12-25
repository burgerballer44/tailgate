<x-layouts.app mainHeading="Follow Team for {{ $group->name }}" mainDescription="Choose a team and season to follow.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Follow Team', 'active' => true],
        ]"
    />

    <x-form.admin.follow-team
        :group="$group"
        :teams="$teams"
        :seasons="$seasons"
        :action="route('admin.groups.follow-team', $group)"
        :method="'POST'"
    />
</x-layouts.app>
