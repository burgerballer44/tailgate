<x-layouts.app mainHeading="Follow Team for {{ $group->name }}" mainDescription="Choose a team to follow.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'url' => route('developer.groups.show', $group)],
            ['text' => 'Follow Team', 'active' => true],
        ]"
    />

    @if ($group->follows()->count() >= $group->follow_limit)
        <div class="rounded-lg bg-white p-6 text-sm text-gray-600 shadow-md">
            This group has reached its follow limit ({{ $group->follow_limit }}).
            Remove an existing follow to add another team.
        </div>
    @else
        <x-form.developer.follow-team
            :group="$group"
            :teams="$teams"
            :action="route('developer.groups.follow-team', $group)"
            :method="'POST'"
        />
    @endif
</x-layouts.app>
