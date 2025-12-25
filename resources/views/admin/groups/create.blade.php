<x-layouts.app mainHeading="Create Group" mainDescription="Create a new group.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => 'Create', 'active' => true],
        ]"
    />

    <x-form.admin.group :group="null" :users="$users" :action="route('admin.groups.store')" :method="'POST'" />
</x-layouts.app>
