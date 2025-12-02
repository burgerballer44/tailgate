<x-layouts.app mainHeading="Add Member to {{ $group->name }}" mainDescription="Add a new member to the group.">
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'url' => route('groups.index')],
            ['text' => $group->name, 'url' => route('groups.show', $group)],
            ['text' => 'Members', 'url' => route('groups.members.index', $group)],
            ['text' => 'Add Member', 'active' => true],
        ]"
    />

    <x-form.admin.member
        :member="null"
        :group="$group"
        :users="$users ?? collect()"
        :action="route('groups.members.store', $group)"
        :method="'POST'"
    />
</x-layouts.app>
