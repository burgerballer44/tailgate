<x-layouts.app mainHeading="Edit Group" mainDescription="Edit the group details.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('developer.groups.index')],
            ['text' => $group->name, 'url' => route('developer.groups.show', $group)],
            ['text' => 'Edit', 'active' => true],
        ]"
    />

    <x-form.developer.group
        :group="$group"
        :users="$users"
        :groupPolicies="$groupPolicies"
        :action="route('developer.groups.update', $group)"
        :method="'PUT'"
    />
</x-layouts.app>
