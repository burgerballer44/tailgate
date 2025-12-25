<x-layouts.app mainHeading="Add Member to {{ $group->name }}" mainDescription="Add a new member to the group.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('admin.groups.index')],
            ['text' => $group->name, 'url' => route('admin.groups.show', $group)],
            ['text' => 'Members', 'url' => route('admin.groups.members.index', $group)],
            ['text' => 'Add Member', 'active' => true],
        ]"
    />

    <x-form.admin.member
        :member="null"
        :group="$group"
        :users="$users ?? collect()"
        :action="route('admin.groups.members.store', $group)"
        :method="'POST'"
    />
</x-layouts.app>
