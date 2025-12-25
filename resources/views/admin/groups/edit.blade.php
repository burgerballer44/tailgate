<x-layouts.app mainHeading="Edit Group" mainDescription="Edit the group details.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Edit', 'active' => true],
        ]"
    />

    <x-form.admin.group :group="$group" :users="$users" :action="route('admin.groups.update', $group)" :method="'PUT'" />
</x-layouts.app>
